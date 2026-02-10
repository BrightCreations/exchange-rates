<?php

namespace BrightCreations\ExchangeRates\Concretes;

use BrightCreations\ExchangeRates\Concretes\Helpers\WorldBankExchangeRateHelper;
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
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * WorldBankExchangeRateApiService is a service that provides exchange rate data from the World Bank Exchange Rate API.
 *
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
        TimeLoggable,
        WorldBankExchangeRateHelper;

    private const INDICATOR_CODE = 'PA.NUS.FCRF';

    private const PER_PAGE = 1000;

    private const CACHE_TTL = 86400; // 24 hours (daily data from World Bank)

    public function __construct(
        private PendingRequest $http,
        private CurrencyExchangeRateRepositoryInterface $currencyExchangeRateRepository,
    ) {
        $this->http->baseUrl(Config::get('exchange-rates.services.world_bank_exchange_rate.base_url'))
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->throw();
    }

    /**
     * Fetch World Bank data for a specific year with caching.
     *
     * @param  int  $year  The year to fetch data for
     * @return array The World Bank API response
     */
    private function fetchWorldBankData(int $year): array
    {
        $cacheKey = "world_bank_exchange_rates_{$year}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year) {
            $indicator = self::INDICATOR_CODE;
            $perPage = self::PER_PAGE;

            // Fetch first page
            $response = $this->http->get("/country/all/indicator/$indicator?date=$year&format=json&per_page=$perPage");
            $data = $response->json();

            // Check if pagination is needed
            if (isset($data[0]) && ($data[0]['pages'] ?? 1) > 1) {
                // Fetch all pages
                $data = $this->fetchAllPages($data, function (int $page) use ($indicator, $year, $perPage) {
                    $response = $this->http->get("/country/all/indicator/$indicator?date=$year&format=json&per_page=$perPage&page=$page");

                    return $response->json();
                });
            }

            return $data;
        });
    }

    /**
     * Store exchange rates in the database
     *
     *
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeExchangeRates(string $currency_code): Collection
    {
        $year = Carbon::now()->format('Y');

        // Get response from the API (cached)
        $worldBankData = $this->logTime(function () use ($year) {
            return $this->fetchWorldBankData((int) $year);
        });

        // Extract exchange rates for the requested currency
        $exchangeRates = $this->extractExchangeRatesForCurrency($currency_code, $worldBankData);

        if (empty($exchangeRates)) {
            logger()->warning("No exchange rates found for currency {$currency_code} from World Bank");

            return collect();
        }

        // Get last update date from metadata
        $lastUpdated = $worldBankData[0]['lastupdated'] ?? null;
        $timestamp = $lastUpdated ? Carbon::parse($lastUpdated) : Carbon::now();

        // Update the database
        $this->currencyExchangeRateRepository->updateExchangeRates(
            $currency_code,
            $exchangeRates,
            $this->getProviderName(),
        );
        $this->currencyExchangeRateRepository->updateExchangeRatesHistory(
            $currency_code,
            $exchangeRates,
            $timestamp,
            $this->getProviderName(),
        );

        // Construct models
        return CurrencyExchangeRate::constructFromExchangeRatesDto(
            new ExchangeRatesDto(
                $currency_code,
                $exchangeRates,
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
     * Note: World Bank provides yearly data, so we use the year from the date_time
     *
     *
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function storeHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection
    {
        $year = $date_time->format('Y');

        // Get response from the API (cached)
        $worldBankData = $this->logTime(function () use ($year) {
            return $this->fetchWorldBankData((int) $year);
        });

        // Extract exchange rates for the requested currency
        $exchangeRates = $this->extractExchangeRatesForCurrency($currency_code, $worldBankData);

        if (empty($exchangeRates)) {
            logger()->warning("No historical exchange rates found for currency {$currency_code} from World Bank for year {$year}");

            return collect();
        }

        // Get last update date from metadata or use provided date
        $lastUpdated = $worldBankData[0]['lastupdated'] ?? null;
        $timestamp = $lastUpdated ? Carbon::parse($lastUpdated) : $date_time;

        // Update the database
        $this->currencyExchangeRateRepository->updateExchangeRatesHistory(
            $currency_code,
            $exchangeRates,
            $timestamp,
            $this->getProviderName(),
        );

        // Construct models
        return CurrencyExchangeRateHistory::constructFromHistoricalExchangeRatesDto(
            new HistoricalExchangeRatesDto(
                $currency_code,
                $exchangeRates,
                $timestamp,
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
        return $this->currencyExchangeRateRepository->getHistoricalExchangeRates($currency_code, $date_time);
    }

    /**
     * Get a specific historical exchange rate from the database
     */
    public function getHistoricalExchangeRate(string $currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory
    {
        return $this->currencyExchangeRateRepository->getHistoricalExchangeRate($currency_code, $target_currency_code, $date_time);
    }

    /**
     * Store exchange rates for multiple currencies
     * This is optimized to fetch World Bank data once and compute rates for all currencies
     *
     *
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeBulkExchangeRatesForMultipleCurrencies(array $currencies_codes): Collection
    {
        $year = Carbon::now()->format('Y');

        // Get response from the API (cached) - fetch once for all currencies
        $worldBankData = $this->logTime(function () use ($year) {
            return $this->fetchWorldBankData((int) $year);
        });

        // Extract exchange rates for all requested currencies
        $multiBaseRates = $this->extractExchangeRatesForMultipleCurrencies($currencies_codes, $worldBankData);

        if (empty($multiBaseRates)) {
            logger()->warning('No exchange rates found for currencies from World Bank');

            return collect();
        }

        // Get last update date from metadata
        $lastUpdated = $worldBankData[0]['lastupdated'] ?? null;
        $timestamp = $lastUpdated ? Carbon::parse($lastUpdated) : Carbon::now();

        // Construct DTOs
        $dtos = [];
        $historicalDtos = [];
        foreach ($multiBaseRates as $baseCurrency => $rates) {
            $dtos[] = new ExchangeRatesDto($baseCurrency, $rates);
            $historicalDtos[] = new HistoricalExchangeRatesDto($baseCurrency, $rates, $timestamp);
        }

        // Update the database
        $this->currencyExchangeRateRepository->updateBulkExchangeRates($dtos, $this->getProviderName());
        $this->currencyExchangeRateRepository->updateBulkExchangeRatesHistory($historicalDtos, $this->getProviderName());

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
        // Group by year for efficient fetching
        $currenciesByYear = [];
        foreach ($historical_base_currencies as $historicalBaseCurrency) {
            $year = $historicalBaseCurrency->getDateTime()->format('Y');
            $currenciesByYear[$year][] = $historicalBaseCurrency;
        }

        $historicalDtos = [];

        foreach ($currenciesByYear as $year => $historicalCurrencies) {
            // Get response from the API (cached)
            $worldBankData = $this->logTime(function () use ($year) {
                return $this->fetchWorldBankData((int) $year);
            });

            // Get last update date from metadata
            $lastUpdated = $worldBankData[0]['lastupdated'] ?? null;
            $timestamp = $lastUpdated ? Carbon::parse($lastUpdated) : Carbon::create((int) $year);

            // Extract currency codes
            $currencyCodes = array_map(
                fn ($hbc) => $hbc->getBaseCurrencyCode(),
                $historicalCurrencies
            );

            // Extract exchange rates for all requested currencies
            $multiBaseRates = $this->extractExchangeRatesForMultipleCurrencies($currencyCodes, $worldBankData);

            foreach ($multiBaseRates as $baseCurrency => $rates) {
                $historicalDtos[] = new HistoricalExchangeRatesDto($baseCurrency, $rates, $timestamp);
            }
        }

        if (empty($historicalDtos)) {
            logger()->warning('No historical exchange rates found from World Bank');

            return collect();
        }

        // Update the database
        $this->currencyExchangeRateRepository->updateBulkExchangeRatesHistory($historicalDtos, $this->getProviderName());

        // Construct models
        $data = collect();
        foreach ($historicalDtos as $dto) {
            $data->push(CurrencyExchangeRateHistory::constructFromHistoricalExchangeRatesDto($dto));
        }

        return $data->flatten()->groupBy(['base_currency_code', fn ($item) => $item->date_time->format('Y-m-d')]);
    }
}
