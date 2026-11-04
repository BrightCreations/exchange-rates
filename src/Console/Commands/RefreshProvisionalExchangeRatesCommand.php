<?php

namespace BrightCreations\ExchangeRates\Console\Commands;

use BrightCreations\ExchangeRates\Concretes\FallbackExchangeRateService;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Replaces provisional (period-average) historical rates — stored when every daily provider failed
 * for a date — with daily rates once a daily provider can serve that date. The daily rates are
 * stored under the same date_time, so they overwrite the provisional rows for every target they cover.
 *
 * @author Bright Creations <kareem.shaaban@brightcreations.com>
 * @license MIT
 */
class RefreshProvisionalExchangeRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rates:refresh-provisional
                            {--days=30 : Only refresh provisional rates dated within this many days (0 = no limit)}
                            {--limit=100 : Maximum number of currency/date combinations to refresh per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replace provisional (period-average) historical exchange rates with daily rates when a daily provider has them';

    public function handle(): int
    {
        $provisionalProviders = CurrencyExchangeRateHistory::provisionalProviders();
        $dailyServices = array_values(array_filter(
            (array) Config::get('exchange-rates.fallback_order', []),
            fn (string $serviceClass) => ! in_array(class_basename($serviceClass), $provisionalProviders, true)
        ));

        if ($provisionalProviders === [] || $dailyServices === []) {
            $this->info('Nothing to refresh: no provisional providers, or no daily provider in the fallback order.');

            return self::SUCCESS;
        }

        $days = max(0, (int) $this->option('days'));
        $limit = max(1, (int) $this->option('limit'));

        $query = CurrencyExchangeRateHistory::onlyProvisional()
            ->select(['base_currency_code', 'date_time'])
            ->distinct()
            ->orderBy('date_time', 'desc')
            ->limit($limit);

        if ($days > 0) {
            $query->where('date_time', '>=', Carbon::now()->subDays($days)->startOfDay());
        }

        $entries = $query->get();

        if ($entries->isEmpty()) {
            $this->info('No provisional exchange rates to refresh.');

            return self::SUCCESS;
        }

        $fallback = new FallbackExchangeRateService;
        $fallback->setFallbackServices($dailyServices);

        $refreshed = 0;
        $failed = 0;

        foreach ($entries as $entry) {
            $dateTime = Carbon::parse($entry->date_time);

            try {
                $fallback->storeHistoricalExchangeRates($entry->base_currency_code, $dateTime);
                $refreshed++;
            } catch (\Throwable $e) {
                $failed++;
                Log::info("exchange-rates:refresh-provisional: {$entry->base_currency_code} on {$dateTime->toDateTimeString()} is still provisional: {$e->getMessage()}");
            }
        }

        $this->info("Refreshed {$refreshed} of {$entries->count()} provisional currency/date combinations ({$failed} still provisional).");

        return self::SUCCESS;
    }
}
