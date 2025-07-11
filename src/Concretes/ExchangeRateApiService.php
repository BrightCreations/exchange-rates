<?php

namespace BrightCreations\ExchangeRates\Concretes;

use BrightCreations\ExchangeRates\Contracts\BaseExchangeRateService;
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
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

/**
 * ExchangeRateApiService is a service that provides exchange rate data from the Exchange Rate API.
 * 
 * @package BrightCreations\ExchangeRates\Concretes
 * @author Bright Creations <kareem.shaaban@brightcreations.com>
 * @license MIT
 * 
 * Example response for `GET https://v6.exchangerate-api.com/v6/YOUR-API-KEY/history/USD/YEAR/MONTH/DAY` endpoint:
 * ```json
{
    "result":"success",
    "documentation":"https://www.exchangerate-api.com/docs",
    "terms_of_use":"https://www.exchangerate-api.com/terms",
    "year":2015,
    "month":2,
    "day":22,
    "base_code":"USD",
    "conversion_rates":{
        "AUD":1.4196,
        "BRL":4.0003,
        ....
    }
}
 * ```
 * 
 * Example response for `GET https://v6.exchangerate-api.com/v6/YOUR-API-KEY/latest/USD` endpoint:
 * ```json
{
	"result": "success",
	"documentation": "https://www.exchangerate-api.com/docs",
	"terms_of_use": "https://www.exchangerate-api.com/terms",
	"time_last_update_unix": 1585267200,
	"time_last_update_utc": "Fri, 27 Mar 2020 00:00:00 +0000",
	"time_next_update_unix": 1585353700,
	"time_next_update_utc": "Sat, 28 Mar 2020 00:00:00 +0000",
	"base_code": "USD",
	"conversion_rates": {
		"USD": 1,
		"AUD": 1.4817,
        ....
	}
}
 * ```
 */
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
            ])
            ->throw();
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
        // Get response from the API
        $response = $this->logTime(function () use ($currency_code) {
            return $this->collectResponse($this->http->get("/latest/$currency_code"));
        });

        // Update the database
        $this->currencyExchangeRateRepository->updateExchangeRates(
            $response->get('base_code'),
            $response->get('conversion_rates'),
        );
        $this->currencyExchangeRateRepository->updateExchangeRatesHistory(
            $response->get('base_code'),
            $response->get('conversion_rates'),
            Carbon::createFromTimestamp($response->get('time_last_update_unix')),
        );

        // Construct models
        return CurrencyExchangeRate::constructFromExchangeRatesDto(
            new ExchangeRatesDto(
                $response->get('base_code'),
                $response->get('conversion_rates'),
            )
        );
    }

    /**
     * Store exchange rates for multiple currencies
     *
     * @param array $currencies_codes
     * 
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeBulkExchangeRatesForMultipleCurrencies(array $currencies_codes): Collection
    {
        // Get responses from the API
        $responses = $this->logTime(function () use ($currencies_codes) {
            $responses = collect();
            foreach ($currencies_codes as $currency_code) {
                $response = $this->logTime(function () use ($currency_code) {
                    return $this->collectResponse($this->http->get("/latest/$currency_code"));
                });
                $responses->push($response);
            }
            return $responses;
        });

        // Construct DTOs
        $dtos = [];
        $historical_dtos = [];
        foreach ($responses as $response) {
            $dtos[] = new ExchangeRatesDto(
                $response->get('base_code'),
                $response->get('conversion_rates'),
            );
            $historical_dtos[] = new HistoricalExchangeRatesDto(
                $response->get('base_code'),
                $response->get('conversion_rates'),
                Carbon::createFromTimestamp($response->get('time_last_update_unix')),
            );
        }

        // Update the database
        $this->currencyExchangeRateRepository->updateBulkExchangeRates($dtos);
        $this->currencyExchangeRateRepository->updateBulkExchangeRatesHistory($historical_dtos);

        // Construct models
        $data = collect();
        foreach ($dtos as $dto) {
            $data->push(CurrencyExchangeRate::constructFromExchangeRatesDto($dto));
        }
        return $data->flatten()->groupBy('base_currency_code');
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
        // Get response from the API
        $year = $date_time->year;
        $month = $date_time->month;
        $day = $date_time->day;
        $response = $this->logTime(function () use ($currency_code, $year, $month, $day) {
            return $this->collectResponse($this->http->get("/history/$currency_code/$year/$month/$day"));
        });

        // Update the database
        $this->currencyExchangeRateRepository->updateExchangeRatesHistory(
            $response->get('base_code'),
            $response->get('conversion_rates'),
            $date_time,
        );

        // Construct models
        return CurrencyExchangeRateHistory::constructFromHistoricalExchangeRatesDto(
            new HistoricalExchangeRatesDto(
                $response->get('base_code'),
                $response->get('conversion_rates'),
                $date_time,
            )
        );
    }

    /**
     * Store historical exchange rates for multiple currencies
     *
     * @param HistoricalBaseCurrencyDto[] $historical_base_currencies
     * 
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function storeBulkHistoricalExchangeRatesForMultipleCurrencies(array $historical_base_currencies): Collection
    {
        // Get responses from the API
        $responses = $this->logTime(function () use ($historical_base_currencies) {
            $responses = collect();
            foreach ($historical_base_currencies as $historical_base_currency) {
                $response = $this->logTime(function () use ($historical_base_currency) {
                    $base_currency_code = $historical_base_currency->getBaseCurrencyCode();
                    $date_time = $historical_base_currency->getDateTime();
                    $year = $date_time->year;
                    $month = $date_time->month;
                    $day = $date_time->day;
                    return $this->collectResponse($this->http->get("/history/$base_currency_code/$year/$month/$day"));
                });
                $responses->push($response);
            }
            return $responses;
        });

        // Construct DTOs
        $historical_dtos = [];
        foreach ($responses as $response) {
            $date_time = Carbon::create($response->get('year'), $response->get('month'), $response->get('day'));
            $historical_dtos[] = new HistoricalExchangeRatesDto(
                $response->get('base_code'),
                $response->get('conversion_rates'),
                $date_time,
            );
        }

        // Update the database
        $this->currencyExchangeRateRepository->updateBulkExchangeRatesHistory($historical_dtos);

        // Construct models
        $data = collect();
        foreach ($historical_dtos as $dto) {
            $data->push(CurrencyExchangeRateHistory::constructFromHistoricalExchangeRatesDto($dto));
        }
        return $data->flatten()->groupBy(['base_currency_code', fn ($item) => $item->date_time->format('Y-m-d')]);
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
