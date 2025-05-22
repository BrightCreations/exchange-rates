<?php

namespace BrightCreations\ExchangeRates\Concretes;

use BrightCreations\ExchangeRates\Contracts\BaseExchangeRateService;
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use BrightCreations\ExchangeRates\Traits\CollectableResponse;
use BrightCreations\ExchangeRates\Traits\TimeLoggable;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class ExchangeRateApiService extends BaseExchangeRateService implements ExchangeRateServiceInterface, HistoricalSupportExchangeRateServiceInterface
{
    use CollectableResponse,
        TimeLoggable;

    public function __construct(
        private PendingRequest $http,
        private CurrencyExchangeRateRepositoryInterface $currencyExchangeRateRepository,
    ) {
        $this->http->baseUrl(Config::get('exchange-rates.services.exchange_rate_api.base_url'))
            ->withHeaders([
                'Accept' => 'application/json'
            ]);
    }

    /**
     * Store exchange rates in the database
     *
     * @param string $currency_code
     * 
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeExchangeRates(string $currency_code): Collection
    {
        $response = $this->collectResponse($this->http->get("/latest/$currency_code"));
        $this->currencyExchangeRateRepository->updateExchangeRates(
            $response->get('base_code'),
            $response->get('conversion_rates'),
        );
        $this->currencyExchangeRateRepository->updateExchangeRatesHistory(
            $response->get('base_code'),
            $response->get('conversion_rates'),
            Carbon::createFromTimestamp($response->get('time_last_update_unix')),
        );
        $data = collect();
        foreach ($response->get('conversion_rates') as $code => $rate) {
            $record = new CurrencyExchangeRate([
                'base_currency_code'    => $currency_code,
                'target_currency_code'  => $code,
                'exchange_rate'         => $rate,
                'last_update_date'      => Carbon::now(),
            ]);
            $data->push($record);
        }
        return $data;
    }

    /**
     * Get exchange rates from the database
     *
     * @param string $currency_code
     * 
     * @return Collection
     */
    public function getExchangeRates(string $currency_code): Collection
    {
        return $this->currencyExchangeRateRepository->getExchangeRates($currency_code);
    }

    /**
     * Get all exchange rates from the database
     * 
     * @return Collection
     */
    public function getAllExchangeRates(): Collection
    {
        return $this->currencyExchangeRateRepository->getAllExchangeRates();
    }

    /**
     * Store historical exchange rates in the database
     *
     * @param string $currency_code
     * @param CarbonInterface $date_time
     * 
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function storeHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection
    {
        $year = $date_time->year;
        $month = $date_time->month;
        $day = $date_time->day;
        $response = $this->logTime(function () use ($currency_code, $year, $month, $day) {
            return $this->collectResponse($this->http->get("/history/$currency_code/$year/$month/$day"));
        });
        $this->currencyExchangeRateRepository->updateExchangeRatesHistory(
            $response->get('base_code'),
            $response->get('conversion_rates'),
            $date_time,
        );
        $data = collect();
        foreach ($response->get('conversion_rates') as $code => $rate) {
            $record = new CurrencyExchangeRateHistory([
                'base_currency_code'    => $currency_code,
                'target_currency_code'  => $code,
                'exchange_rate'         => $rate,
                'date_time'             => $date_time,
                'last_update_date'      => Carbon::now(),
            ]);
            $data->push($record);
        }
        return $data;
    }

    /**
     * Get historical exchange rates from the database
     *
     * @param string $currency_code
     * @param CarbonInterface $date_time
     * 
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function getHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection
    {
        $historicalExchangeRates = $this->currencyExchangeRateRepository->getHistoricalExchangeRates($currency_code, $date_time);
        if ($historicalExchangeRates->isEmpty()) {
            return $this->storeHistoricalExchangeRates($currency_code, $date_time);
        }
        return $historicalExchangeRates;
    }

    /**
     * Get a specific historical exchange rate from the database
     *
     * @param string $currency_code
     * @param string $target_currency_code
     * @param CarbonInterface $date_time
     * 
     * @return CurrencyExchangeRateHistory
     */
    public function getHistoricalExchangeRate(string $currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory
    {
        try {
            return $this->currencyExchangeRateRepository->getHistoricalExchangeRate($currency_code, $target_currency_code, $date_time);
        } catch (ModelNotFoundException $e) {
            return $this->storeHistoricalExchangeRates($currency_code, $date_time)
                ->where('target_currency_code', $target_currency_code)
                ->firstOrFail();
        }
    }
}
