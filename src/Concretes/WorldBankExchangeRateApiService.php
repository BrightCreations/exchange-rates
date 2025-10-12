<?php

namespace BrightCreations\ExchangeRates\Concretes;

use BrightCreations\ExchangeRates\Contracts\BaseExchangeRateService;
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Traits\CollectableResponse;
use BrightCreations\ExchangeRates\Traits\TimeLoggable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Config;
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use Illuminate\Support\Collection;
use Carbon\CarbonInterface;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;
use Carbon\Carbon;

/**
 * WorldBankExchangeRateApiService is a service that provides exchange rate data from the World Bank Exchange Rate API.
 * 
 * @package BrightCreations\ExchangeRates\Concretes
 * @author Bright Creations <kareem.shaaban@brightcreations.com>
 * @license MIT
 * 
 * Example response for `GET https://api.worldbank.org/v2/country/all/indicator/PA.NUS.FCRF?date=2024&format=json&per_page=1000` endpoint:
 * ```json
[
    {
        "page": 1,
        "pages": 266,
        "per_page": 1,
        "total": 266,
        "sourceid": "2",
        "lastupdated": "2025-10-07"
    },
    [
        {
            "indicator": {
                "id": "PA.NUS.FCRF",
                "value": "Official exchange rate (LCU per US$, period average)"
            },
            "country": {
                "id": "ZH",
                "value": "Africa Eastern and Southern"
            },
            "countryiso3code": "AFE",
            "date": "2024",
            "value": null,
            "unit": "",
            "obs_status": "",
            "decimal": 2
        }
    ]
]
 * ```
 */
class WorldBankExchangeRateApiService extends BaseExchangeRateService implements ExchangeRateServiceInterface, HistoricalSupportExchangeRateServiceInterface
{
    use CollectableResponse,
        TimeLoggable;

    private const INDICATOR_CODE = 'PA.NUS.FCRF';
    private const PER_PAGE = 1000;

    public function __construct(
        private PendingRequest $http,
        private CurrencyExchangeRateRepositoryInterface $currencyExchangeRateRepository,
    ) {
        $this->http->baseUrl(Config::get('exchange-rates.services.world_bank_exchange_api.base_url'))
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
        $year = Carbon::now()->format('Y');
        $indicator = self::INDICATOR_CODE;
        $per_page = self::PER_PAGE;

        // Get response from the API
        $response = $this->logTime(function () use ($currency_code, $year, $indicator, $per_page) {
            return $this->collectResponse($this->http->get("/country/all/indicator/$indicator?date=$year&format=json&per_page=$per_page"));
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
     *
     * @param string $currency_code
     * 
     * @return Collection
     */
    public function getExchangeRates(string $currency_code): Collection
    {
        return collect();
    }

    /**
     * Get all exchange rates from the database
     * 
     * @return Collection
     */
    public function getAllExchangeRates(): Collection
    {
        return collect();
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
        return collect();
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
        return collect();
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
        return new CurrencyExchangeRateHistory();
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
        return collect();
    }

    /**
     * Store historical exchange rates for multiple currencies
     *
     * @param array $historical_base_currencies
     * 
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function storeBulkHistoricalExchangeRatesForMultipleCurrencies(array $historical_base_currencies): Collection
    {
        return collect();
    }
}
