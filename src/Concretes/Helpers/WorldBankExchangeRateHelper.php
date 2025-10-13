<?php

namespace BrightCreations\ExchangeRates\Concretes\Helpers;

use Illuminate\Support\Collection;
use PragmaRX\Countries\Package\Countries;

/**
 * WorldBankExchangeRateHelper is a helper trait that provides methods to help with World Bank Exchange Rate API.
 * 
 * @package BrightCreations\ExchangeRates\Concretes\Helpers
 * @author Bright Creations <kareem.shaaban@brightcreations.com>
 * @license MIT
 * 
 * Example response for `GET https://api.worldbank.org/v2/country/all/indicator/PA.NUS.FCRF?date=2024&format=json&per_page=1000` endpoint:
 * 
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
trait WorldBankExchangeRateHelper
{
    /**
     * Manual overrides for ISO3 to currency code mapping.
     * Used for special cases, territories, or when pragmarx/countries data is incomplete/incorrect.
     * 
     * @var array<string, string>
     */
    protected array $iso3ToCurrencyOverrides = [
        // Euro area - prefer EUR for aggregates
        'EMU' => 'EUR',

        // Special territories and cases
        'TCA' => 'USD', // Turks and Caicos Islands
        'VIR' => 'USD', // Virgin Islands (U.S.)
        'TLS' => 'USD', // Timor-Leste uses USD

        // Kosovo uses EUR (not in all datasets)
        'XKX' => 'EUR',

        // Add other overrides as needed
    ];

    /**
     * ISO3 codes that represent aggregates/regions, not countries.
     * These should be filtered out when building currency exchange rates.
     * 
     * @var array<string>
     */
    protected array $aggregateRegionCodes = [
        'AFE',
        'AFW',
        'ARB',
        'CSS',
        'CEB',
        'EAR',
        'EAS',
        'EAP',
        'TEA',
        'ECS',
        'ECA',
        'TEC',
        'EUU',
        'FCS',
        'HPC',
        'IBD',
        'IBT',
        'IDB',
        'IDX',
        'IDA',
        'LTE',
        'LCN',
        'LAC',
        'TLA',
        'LDC',
        'LMY',
        'LIC',
        'LMC',
        'MEA',
        'MNA',
        'TMN',
        'MIC',
        'NAC',
        'INX',
        'OED',
        'OSS',
        'PSS',
        'PST',
        'PRE',
        'SST',
        'SAS',
        'TSA',
        'SSF',
        'SSA',
        'TSS',
        'UMC',
        'WLD',
        'XD',
        'XF',
        'XH',
        'XI',
        'XG',
        'ZT',
        'ZJ',
        'ZH',
        'ZI',
        'Z4',
        'Z7',
        'XC',
        'V2',
        'V3',
        'V4',
        'T2',
        'T3',
        'T4',
        'T5',
        'T6',
        'T7',
        'B8',
        'S1',
        'S2',
        'S3',
        'S4',
        'F1',
        '1A',
        '1W',
        '4E',
        '6D',
        '6X',
        '7E',
        '8S',
        'A4',
        'A5',
        'A9',
        'C4',
        'C5',
        'C6',
        'C7',
        'C8',
        'C9',
        'D2',
        'D3',
        'D4',
        'D5',
        'D6',
        'D7',
        'D8',
        'D9',
        'EU',
        'M1',
        'M2',
        'N6',
        'OE',
        'R6',
        'XL',
        'XM',
        'XN',
        'XO',
        'XP',
        'XQ',
        'XT',
        'XU',
        'V1',
    ];

    /**
     * Priority mapping for countries that use multiple currencies.
     * Maps ISO3 to the primary/preferred currency code.
     * 
     * @var array<string, string>
     */
    protected array $multiCurrencyPriority = [
        'CUB' => 'CUP', // Cuba: CUP over CUC
        'PAL' => 'ILS', // Palestine: ILS (also uses JOD, EGP in practice)
        'ZWE' => 'ZWL', // Zimbabwe: prefer ZWL despite multi-currency economy
        // Add more as discovered
    ];

    /**
     * Map ISO3 country code to ISO4217 currency code.
     * 
     * @param string $iso3Code The ISO3 country code
     * @return string|null The ISO4217 currency code, or null if not found/aggregate
     */
    protected function mapCountryToCurrency(string $iso3Code): ?string
    {
        // Check if this is an aggregate/region
        if (in_array($iso3Code, $this->aggregateRegionCodes, true)) {
            return null;
        }

        // Check overrides first
        if (isset($this->iso3ToCurrencyOverrides[$iso3Code])) {
            return $this->iso3ToCurrencyOverrides[$iso3Code];
        }

        // Check multi-currency priority
        if (isset($this->multiCurrencyPriority[$iso3Code])) {
            return $this->multiCurrencyPriority[$iso3Code];
        }

        try {
            // Use pragmarx/countries to get currency
            $countries = new Countries();
            $country = $countries->where('cca3', $iso3Code)->first();

            if (!$country) {
                return null;
            }

            // Get currencies - returns collection of currency objects
            $currencies = $country->currencies ?? null;

            if (!$currencies) {
                return null;
            }

            // Convert to array if it's an object
            if (is_object($currencies)) {
                $currencies = json_decode(json_encode($currencies), true);
            }

            // Get the first currency code (most countries have one)
            $currencyCodes = array_values($currencies);

            if (empty($currencyCodes)) {
                return null;
            }

            // Return the first (usually only) currency
            return $currencyCodes[0];
        } catch (\Exception $e) {
            // Log error and return null
            logger()->warning("Failed to map country $iso3Code to currency", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if an ISO3 code represents an aggregate/region rather than a country.
     * 
     * @param string $iso3Code The ISO3 code to check
     * @return bool True if it's an aggregate, false otherwise
     */
    protected function isAggregateRegion(string $iso3Code): bool
    {
        return in_array($iso3Code, $this->aggregateRegionCodes, true);
    }

    /**
     * Add or update a currency mapping override.
     * 
     * @param string $iso3Code The ISO3 country code
     * @param string $currencyCode The ISO4217 currency code
     * @return void
     */
    public function addCurrencyOverride(string $iso3Code, string $currencyCode): void
    {
        $this->iso3ToCurrencyOverrides[$iso3Code] = $currencyCode;
    }

    /**
     * Parse World Bank API response and build USD-anchored currency rates.
     * Filters out aggregates, nulls, and maps countries to currencies.
     * 
     * @param array $worldBankResponse Raw API response from World Bank
     * @return array<string, float> Map of currency code => rate (LCU per USD)
     */
    protected function parseWorldBankResponseToUsdRates(array $worldBankResponse): array
    {
        $usdRates = [];

        // World Bank response format: [metadata, [data items]]
        if (!isset($worldBankResponse[1]) || !is_array($worldBankResponse[1])) {
            return $usdRates;
        }

        $dataItems = $worldBankResponse[1];

        foreach ($dataItems as $item) {
            // Skip if no country code
            if (!isset($item['countryiso3code'])) {
                continue;
            }

            $iso3Code = $item['countryiso3code'];
            $value = $item['value'] ?? null;

            // Skip null values
            if ($value === null) {
                continue;
            }

            // Skip aggregates/regions
            if ($this->isAggregateRegion($iso3Code)) {
                continue;
            }

            // Map country to currency
            $currencyCode = $this->mapCountryToCurrency($iso3Code);

            if ($currencyCode === null) {
                continue;
            }

            // Store the rate (LCU per USD)
            // If multiple countries use same currency, we'll handle in aggregation policy
            if (!isset($usdRates[$currencyCode])) {
                $usdRates[$currencyCode] = (float) $value;
            } else {
                // Currency collision - apply aggregation policy
                $usdRates[$currencyCode] = $this->aggregateCurrencyRate(
                    $currencyCode,
                    $usdRates[$currencyCode],
                    (float) $value,
                    $iso3Code
                );
            }
        }

        // Ensure USD = 1.0
        $usdRates['USD'] = 1.0;

        return $usdRates;
    }

    /**
     * Aggregate currency rates when multiple countries share the same currency.
     * Uses priority-based selection or averaging based on policy.
     * 
     * @param string $currencyCode The currency code
     * @param float $existingRate The existing rate for this currency
     * @param float $newRate The new rate from another country
     * @param string $newCountryIso3 The ISO3 code of the new country
     * @return float The aggregated rate
     */
    protected function aggregateCurrencyRate(
        string $currencyCode,
        float $existingRate,
        float $newRate,
        string $newCountryIso3
    ): float {
        // Priority countries for specific currencies (more reliable data sources)
        $priorityCountries = [
            'EUR' => ['EMU', 'DEU', 'FRA'], // Prefer Euro area aggregate, then Germany, then France
            'XOF' => ['SEN'], // West African CFA franc - prefer Senegal
            'XAF' => ['CMR'], // Central African CFA franc - prefer Cameroon
            'XCD' => ['ATG'], // East Caribbean dollar - prefer Antigua
            'AUD' => ['AUS'], // Australian dollar - prefer Australia over territories
            'NZD' => ['NZL'], // New Zealand dollar - prefer NZ
            'USD' => ['USA'], // US dollar - prefer USA
            'GBP' => ['GBR'], // British pound - prefer UK
            'DKK' => ['DNK'], // Danish krone - prefer Denmark
            'NOK' => ['NOR'], // Norwegian krone - prefer Norway
        ];

        // Check if we should use the new rate based on priority
        if (
            isset($priorityCountries[$currencyCode]) &&
            in_array($newCountryIso3, $priorityCountries[$currencyCode], true)
        ) {
            return $newRate;
        }

        // Otherwise, keep the existing rate (first occurrence wins)
        // This is a simple policy - could be enhanced with averaging or other strategies
        return $existingRate;
    }

    /**
     * Fetch all pages from World Bank API if paginated.
     * 
     * @param array $initialResponse The first page response
     * @param callable $fetchPage Callback to fetch a specific page: function(int $page): array
     * @return array Combined data from all pages
     */
    protected function fetchAllPages(array $initialResponse, callable $fetchPage): array
    {
        $allData = [];

        // Extract pagination info
        if (!isset($initialResponse[0])) {
            return $initialResponse;
        }

        $metadata = $initialResponse[0];
        $totalPages = $metadata['pages'] ?? 1;
        $currentPage = $metadata['page'] ?? 1;

        // Add first page data
        if (isset($initialResponse[1])) {
            $allData = array_merge($allData, $initialResponse[1]);
        }

        // Fetch remaining pages if any
        for ($page = $currentPage + 1; $page <= $totalPages; $page++) {
            $pageResponse = $fetchPage($page);

            if (isset($pageResponse[1]) && is_array($pageResponse[1])) {
                $allData = array_merge($allData, $pageResponse[1]);
            }
        }

        // Return in same format as original response
        return [
            $metadata,
            $allData,
        ];
    }

    /**
     * Compute cross-currency exchange rates from USD-anchored rates.
     * 
     * Given rates in USD (e.g., EUR = 0.92 USD, GBP = 0.78 USD),
     * compute rates for a specific base currency.
     * 
     * Example: If base is EUR:
     * - USD→EUR rate = 0.92 (from World Bank)
     * - EUR→GBP = GBP_per_USD / EUR_per_USD = 0.78 / 0.92 = 0.8478
     * 
     * @param string $baseCurrency The base currency code
     * @param array<string, float> $usdRates Map of currency => rate in USD
     * @return array<string, float> Map of currency => rate relative to base
     */
    protected function computeCrossCurrencyRates(string $baseCurrency, array $usdRates): array
    {
        $crossRates = [];

        // Get the base currency's USD rate
        $baseUsdRate = $usdRates[$baseCurrency] ?? null;

        if ($baseUsdRate === null || $baseUsdRate == 0) {
            // Cannot compute cross rates without base currency rate
            return $crossRates;
        }

        // Compute cross rates for all currencies
        foreach ($usdRates as $targetCurrency => $targetUsdRate) {
            if ($targetCurrency === $baseCurrency) {
                // Base to base is always 1
                $crossRates[$targetCurrency] = 1.0;
            } else {
                // Cross rate = target_USD_rate / base_USD_rate
                // This gives us: 1 base_currency = X target_currency
                $crossRates[$targetCurrency] = $targetUsdRate / $baseUsdRate;
            }
        }

        return $crossRates;
    }

    /**
     * Build exchange rates for multiple base currencies from USD-anchored rates.
     * 
     * @param array<string, float> $usdRates Map of currency => USD rate
     * @param array $baseCurrencies List of base currencies to compute rates for
     * @return array<string, array<string, float>> Map of base => [target => rate]
     */
    protected function buildMultiBaseCurrencyRates(array $usdRates, array $baseCurrencies): array
    {
        $multiBaseRates = [];

        foreach ($baseCurrencies as $baseCurrency) {
            $multiBaseRates[$baseCurrency] = $this->computeCrossCurrencyRates($baseCurrency, $usdRates);
        }

        return $multiBaseRates;
    }

    /**
     * Extract exchange rates for a specific currency from World Bank response.
     * This is the main method that orchestrates parsing and conversion.
     * 
     * @param string $currency_code The base currency code
     * @param array $worldBankResponse The World Bank API response
     * @return array<string, float> Exchange rates with base currency
     */
    public function extractExchangeRatesForCurrency(string $currency_code, array $worldBankResponse): array
    {
        // Step 1: Parse World Bank response to get USD-anchored rates
        $usdRates = $this->parseWorldBankResponseToUsdRates($worldBankResponse);

        if (empty($usdRates)) {
            return [];
        }

        // Step 2: Compute cross-currency rates for the requested base
        $exchangeRates = $this->computeCrossCurrencyRates($currency_code, $usdRates);

        return $exchangeRates;
    }

    /**
     * Extract exchange rates for multiple base currencies from World Bank response.
     * More efficient than calling extractExchangeRatesForCurrency multiple times.
     * 
     * @param array $currencyCodes List of base currency codes
     * @param array $worldBankResponse The World Bank API response
     * @return array<string, array<string, float>> Map of base => [target => rate]
     */
    public function extractExchangeRatesForMultipleCurrencies(array $currencyCodes, array $worldBankResponse): array
    {
        // Step 1: Parse World Bank response to get USD-anchored rates (do this once)
        $usdRates = $this->parseWorldBankResponseToUsdRates($worldBankResponse);

        if (empty($usdRates)) {
            return [];
        }

        // Step 2: Compute cross-currency rates for all requested bases
        $multiBaseRates = $this->buildMultiBaseCurrencyRates($usdRates, $currencyCodes);

        return $multiBaseRates;
    }

    /**
     * Get available currencies from a World Bank response.
     * 
     * @param array $worldBankResponse The World Bank API response
     * @return array<string> List of available currency codes
     */
    public function getAvailableCurrencies(array $worldBankResponse): array
    {
        $usdRates = $this->parseWorldBankResponseToUsdRates($worldBankResponse);
        return array_keys($usdRates);
    }
}
