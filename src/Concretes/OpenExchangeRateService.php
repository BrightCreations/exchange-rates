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
 * OpenExchangeRateService is a service that provides exchange rate data from the Open Exchange Rates API.
 *
 * @author Bright Creations <kareem.shaaban@brightcreations.com>
 * @license MIT
 *
 * Example response for `GET https://openexchangerates.org/api/latest.json?app_id=YOUR-APP-ID` endpoint:
 * ```json
{
    "disclaimer": "https://openexchangerates.org/terms/",
    "license": "https://openexchangerates.org/license/",
    "timestamp": 1449877801,
    "base": "USD",
    "rates": {
        "AED": 3.672538,
        "AFN": 66.809999,
        ...
    }
}
 * ```
 *
 * Example response for `GET https://openexchangerates.org/api/historical/2020-01-01.json?app_id=YOUR-APP-ID&base=USD` endpoint:
 * ```json
{
    "disclaimer": "Usage subject to terms: https://openexchangerates.org/terms",
    "license": "https://openexchangerates.org/license",
    "timestamp": 1341936000,
    "base": "USD",
    "rates": {
        "AED": 3.672914,
        "AFN": 48.337601,
        ...
    }
}
 * ```
 */
class OpenExchangeRateService extends BaseExchangeRateService implements ExchangeRateServiceInterface, HistoricalSupportExchangeRateServiceInterface
{
    use CollectableResponse,
        TimeLoggable;

    public function __construct(
        private PendingRequest $http,
        private CurrencyExchangeRateRepositoryInterface $currencyExchangeRateRepository,
    ) {
        $this->http->baseUrl(Config::get('exchange-rates.services.open_exchange_rate.base_url'))
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Token '.Config::get('exchange-rates.services.open_exchange_rate.app_id'),
            ])
            ->throw();
    }

    /**
     * Store exchange rates in the database
     *
     *
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeExchangeRates(string $currency_code): Collection
    {
        // Get response from the API
        $response = $this->logTime(function () use ($currency_code) {
            return $this->collectResponse($this->http->get("/latest.json?base=$currency_code"));
        });

        // Update the database
        $this->currencyExchangeRateRepository->updateExchangeRates(
            $response->get('base'),
            $response->get('rates'),
        );
        $this->currencyExchangeRateRepository->updateExchangeRatesHistory(
            $response->get('base'),
            $response->get('rates'),
            Carbon::createFromTimestamp($response->get('timestamp')),
        );

        // Construct models
        return CurrencyExchangeRate::constructFromExchangeRatesDto(
            new ExchangeRatesDto(
                $response->get('base'),
                $response->get('rates'),
            )
        );
    }

    /**
     * Get exchange rates from the database
     */
    public function getExchangeRates(string $currency_code): Collection
    {
        return $this->currencyExchangeRateRepository->getExchangeRates($currency_code);
    }

    /**
     * Get all exchange rates from the database
     */
    public function getAllExchangeRates(): Collection
    {
        return $this->currencyExchangeRateRepository->getAllExchangeRates();
    }

    /**
     * Store historical exchange rates in the database
     *
     *
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function storeHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection
    {
        // Get response from the API
        $year = $date_time->format('Y');
        $month = $date_time->format('m');
        $day = $date_time->format('d');
        logger("year: $year, month: $month, day: $day");
        $response = $this->logTime(function () use ($currency_code, $year, $month, $day) {
            return $this->collectResponse($this->http->get("/historical/$year-$month-$day.json?base=$currency_code"));
        });

        // Update the database
        $this->currencyExchangeRateRepository->updateExchangeRatesHistory(
            $response->get('base'),
            $response->get('rates'),
            $date_time,
        );

        // Construct models
        return CurrencyExchangeRateHistory::constructFromHistoricalExchangeRatesDto(
            new HistoricalExchangeRatesDto(
                $response->get('base'),
                $response->get('rates'),
                $date_time,
            )
        );
    }

    /**
     * Get historical exchange rates from the database
     *
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

    /**
     * Store exchange rates for multiple currencies
     *
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
                    return $this->collectResponse($this->http->get("/latest.json?base=$currency_code"));
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
                $response->get('base'),
                $response->get('rates'),
            );
            $historical_dtos[] = new HistoricalExchangeRatesDto(
                $response->get('base'),
                $response->get('rates'),
                Carbon::createFromTimestamp($response->get('timestamp')),
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
     * Store historical exchange rates for multiple currencies
     *
     * @param  HistoricalBaseCurrencyDto[]  $historical_base_currencies
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
                    $year = $date_time->format('Y');
                    $month = $date_time->format('m');
                    $day = $date_time->format('d');

                    return $this->collectResponse($this->http->get("/historical/$year-$month-$day.json?base=$base_currency_code"));
                });
                $responses->push($response);
            }

            return $responses;
        });

        // Construct DTOs
        $historical_dtos = [];
        foreach ($responses as $response) {
            $historical_dtos[] = new HistoricalExchangeRatesDto(
                $response->get('base'),
                $response->get('rates'),
                Carbon::createFromTimestamp($response->get('timestamp')),
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
}
