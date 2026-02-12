<?php

namespace BrightCreations\ExchangeRates\Contracts\Repositories;

use BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto;
use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Interface CurrencyExchangeRateRepository
 */
interface CurrencyExchangeRateRepositoryInterface
{
    /**
     * Update exchange rates in the database
     */
    public function updateExchangeRates(string $base_currency_code, array $exchange_rates, ?string $provider = null): bool;

    /**
     * Update bulk exchange rates in the database
     *
     * @param  ExchangeRatesDto[]  $exchange_rates
     */
    public function updateBulkExchangeRates(array $exchange_rates, ?string $provider = null): bool;

    /**
     * Update exchange rates history in the database
     */
    public function updateExchangeRatesHistory(string $base_currency_code, array $exchange_rates, CarbonInterface $date_time, ?string $provider = null): bool;

    /**
     * Update bulk exchange rates history in the database
     *
     * @param  HistoricalExchangeRatesDto[]  $historical_exchange_rates
     */
    public function updateBulkExchangeRatesHistory(array $historical_exchange_rates, ?string $provider = null): bool;

    /**
     * Get all exchange rates from the database
     */
    public function getAllExchangeRates(): Collection;

    /**
     * Get exchange rates from the database
     *
     *
     * @return Collection<CurrencyExchangeRate>
     */
    public function getExchangeRates(string $base_currency_code): Collection;

    /**
     * Get bulk exchange rates from the database
     *
     * @param  string[]  $base_currency_codes
     * @return Collection<string, Collection<CurrencyExchangeRate>>
     */
    public function getBulkExchangeRates(array $base_currency_codes): Collection;

    /**
     * Get exchange rate from the database
     *
     *
     * @throws ModelNotFoundException
     */
    public function getExchangeRate(string $base_currency_code, string $target_currency_code): CurrencyExchangeRate;

    /**
     * Get bulk exchange rate by currencies pair from the database
     *
     * @param  CurrenciesPairDto[]  $currencies_pairs
     * @return Collection<string, CurrencyExchangeRate> The key is the currencies pair string in the format "BASE_TARGET"
     */
    public function getBulkExchangeRate(array $currencies_pairs): Collection;

    /**
     * Get historical exchange rates from the database
     *
     *
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function getHistoricalExchangeRates(string $base_currency_code, CarbonInterface $date_time): Collection;

    /**
     * Get bulk historical exchange rate by currencies pair from the database
     *
     * @param  HistoricalBaseCurrencyDto[]  $historical_base_currencies
     * @return Collection<string, Collection<CurrencyExchangeRateHistory>> The key is the base currency code concatenated with the date time in the format "BASE_YYYY-MM-DD"
     */
    public function getBulkHistoricalExchangeRates(array $historical_base_currencies): Collection;

    /**
     * Get historical exchange rate from the database
     *
     *
     * @throws ModelNotFoundException
     */
    public function getHistoricalExchangeRate(string $base_currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory;

    /**
     * Get bulk historical exchange rate by currencies pair from the database
     *
     * @param  HistoricalCurrenciesPairDto[]  $historical_currencies_pairs
     * @return Collection<string, CurrencyExchangeRateHistory> The key is the currencies pair string in the format "BASE_TARGET_YYYY-MM-DD"
     */
    public function getBulkHistoricalExchangeRate(array $historical_currencies_pairs): Collection;

    /**
     * Get the last historical rate on or before the given date for a currency pair.
     */
    public function getPreviousHistoricalRate(string $base_currency_code, string $target_currency_code, CarbonInterface $target_date): ?CurrencyExchangeRateHistory;

    /**
     * Get the first historical rate on or after the given date for a currency pair.
     */
    public function getNextHistoricalRate(string $base_currency_code, string $target_currency_code, CarbonInterface $target_date): ?CurrencyExchangeRateHistory;

    /**
     * Get the two historical exchange rate records that bound the requested date for a given currency pair.
     *
     * It should return at most two models (empty if not possible):
     * - d1, r1: last rate on or before $targetDate
     * - d2, r2: first rate on or after $targetDate
     *
     * If one of the bounds does not exist or both bounds are the same record, an empty collection MUST be returned.
     *
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function getBoundingHistoricalRates(string $base_currency_code, string $target_currency_code, CarbonInterface $target_date): Collection;
}
