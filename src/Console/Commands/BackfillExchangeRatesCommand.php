<?php

namespace BrightCreations\ExchangeRates\Console\Commands;

use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Concretes\FallbackExchangeRateService;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillExchangeRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rates:backfill
                            {--currency=* : Currencies to backfill (default: USD,EUR,GBP)}
                            {--start-year= : Start year for backfill (default: current year - 5)}
                            {--end-year= : End year for backfill (default: current year)}
                            {--service= : Specific service to use (default: fallback)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill historical exchange rates for specified currencies and years';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateServiceInterface $exchangeRateService): int
    {
        $this->info('🔄 Starting exchange rates backfill...');

        // Get parameters
        $currencies = $this->option('currency') ?: ['USD', 'EUR', 'GBP'];
        $endYear = $this->option('end-year') ?: Carbon::now()->year;
        $startYear = $this->option('start-year') ?: ($endYear - 5);
        $specificService = $this->option('service');

        // Validate years
        if ($startYear > $endYear) {
            $this->error('Start year cannot be greater than end year');
            return self::FAILURE;
        }

        $this->info("📅 Backfilling from {$startYear} to {$endYear}");
        $this->info("💱 Currencies: " . implode(', ', $currencies));

        // Determine service to use
        $service = $specificService
            ? app($specificService)
            : $exchangeRateService;

        if (!($service instanceof HistoricalSupportExchangeRateServiceInterface)) {
            $this->error('Selected service does not support historical exchange rates');
            return self::FAILURE;
        }

        // Log which service is being used
        $serviceName = class_basename($service);
        $this->info("🔌 Using service: {$serviceName}");

        if ($service instanceof FallbackExchangeRateService) {
            $fallbackOrder = array_map(
                fn($class) => class_basename($class),
                $service->getFallbackServices()
            );
            $this->info("🔄 Fallback order: " . implode(' → ', $fallbackOrder));
        }

        // Build historical DTOs for all years
        $historicalDtos = [];
        $years = range($startYear, $endYear);

        foreach ($currencies as $currency) {
            foreach ($years as $year) {
                $historicalDtos[] = new HistoricalBaseCurrencyDto(
                    $currency,
                    Carbon::create($year, 1, 1)
                );
            }
        }

        $totalOperations = count($historicalDtos);
        $this->info("📊 Total operations: {$totalOperations}");

        // Progress bar
        $progressBar = $this->output->createProgressBar($totalOperations);
        $progressBar->start();

        $successCount = 0;
        $failureCount = 0;
        $startTime = microtime(true);

        // Process in batches by year for efficiency
        $batchedByYear = collect($historicalDtos)->groupBy(fn($dto) => $dto->getDateTime()->year);

        foreach ($batchedByYear as $year => $yearDtos) {
            try {
                Log::info("Backfilling exchange rates for year {$year}", [
                    'currencies' => array_map(fn($dto) => $dto->getBaseCurrencyCode(), $yearDtos->toArray()),
                    'service' => $serviceName,
                ]);

                $results = $service->storeBulkHistoricalExchangeRatesForMultipleCurrencies($yearDtos->toArray());

                $count = $results->count();
                $successCount += $count;

                // Update progress
                $progressBar->advance(count($yearDtos));

                Log::info("Successfully backfilled {$count} rates for year {$year}");
            } catch (\Exception $e) {
                $failureCount += count($yearDtos);
                $progressBar->advance(count($yearDtos));

                Log::error("Failed to backfill year {$year}: {$e->getMessage()}", [
                    'currencies' => array_map(fn($dto) => $dto->getBaseCurrencyCode(), $yearDtos->toArray()),
                    'error' => $e->getMessage(),
                ]);

                $this->newLine();
                $this->warn("⚠️  Failed year {$year}: {$e->getMessage()}");
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Calculate metrics
        $duration = microtime(true) - $startTime;
        $durationFormatted = number_format($duration, 2);

        // Summary
        $this->info('✅ Backfill completed!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Operations', $totalOperations],
                ['Successful', $successCount],
                ['Failed', $failureCount],
                ['Duration', "{$durationFormatted}s"],
                ['Service Used', $serviceName],
            ]
        );

        // Log final metrics
        Log::info('Exchange rates backfill completed', [
            'total_operations' => $totalOperations,
            'successful' => $successCount,
            'failed' => $failureCount,
            'duration_seconds' => $duration,
            'service' => $serviceName,
            'currencies' => $currencies,
            'year_range' => "{$startYear}-{$endYear}",
        ]);

        // If using fallback, log which service was used
        if ($service instanceof FallbackExchangeRateService) {
            $currentService = $service->getCurrentService();
            if ($currentService) {
                $actualService = class_basename($currentService);
                $this->info("🎯 Actual service used: {$actualService}");

                Log::info('Fallback service resolution', [
                    'resolved_to' => $actualService,
                    'fallback_order' => $service->getFallbackServices(),
                ]);
            }
        }

        return $failureCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
