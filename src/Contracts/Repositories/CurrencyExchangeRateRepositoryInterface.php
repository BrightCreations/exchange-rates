<?php

namespace BrightCreations\ExchangeRates\Contracts\Repositories;

use BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Interface CurrencyExchangeRateRepository
 * @package App\Models\Repository
 */
interface CurrencyExchangeRateRepositoryInterface
{
    /**
     * Update exchange rates in the database
     *
     * @param string $base_currency_code
     * @param array $exchange_rates
     * 
     * @return bool
     */
    public function updateExchangeRates(string $base_currency_code, array $exchange_rates): bool;

    /**
     * Update bulk exchange rates in the database
     *
     * @param ExchangeRatesDto[] $exchange_rates
     * 
     * @return bool
     */
    public function updateBulkExchangeRates(array $exchange_rates): bool;

    /**
     * Update exchange rates history in the database
     *
     * @param string $base_currency_code
     * @param array $exchange_rates
     * @param CarbonInterface $date_time
     * 
     * @return bool
     */
    public function updateExchangeRatesHistory(string $base_currency_code, array $exchange_rates, CarbonInterface $date_time): bool;

    /**
     * Update bulk exchange rates history in the database
     *
     * @param HistoricalExchangeRatesDto[] $historical_exchange_rates
     * 
     * @return bool
     */
    public function updateBulkExchangeRatesHistory(array $historical_exchange_rates): bool;

    /**
     * Get all exchange rates from the database
     * 
     * @return Collection
     */
    public function getAllExchangeRates(): Collection;

    /**
     * Get exchange rates from the database
     *
     * @param string $base_currency_code
     * 
     * @return Collection<CurrencyExchangeRate>
     */
    public function getExchangeRates(string $base_currency_code): Collection;

    /**
     * Get bulk exchange rates from the database
     *
     * @param string[] $base_currency_codes
     * 
     * @return Collection<string, Collection<CurrencyExchangeRate>>
     */
    public function getBulkExchangeRates(array $base_currency_codes): Collection;

    /**
     * Get exchange rate from the database
     * 
     * @param string $base_currency_code
     * @param string $target_currency_code
     * 
     * @return CurrencyExchangeRate
     * @throws ModelNotFoundException
     */
    public function getExchangeRate(string $base_currency_code, string $target_currency_code): CurrencyExchangeRate;

    /**
     * Get bulk exchange rate by currencies pair from the database
     * 
     * @param CurrenciesPairDto[] $currencies_pairs
     * 
     * @return Collection<string, CurrencyExchangeRate> The key is the currencies pair string in the format "BASE_TARGET"
     */
    public function getBulkExchangeRate(array $currencies_pairs): Collection;

    /**
     * Get historical exchange rates from the database
     *
     * @param string $base_currency_code
     * @param CarbonInterface $date_time
     * 
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function getHistoricalExchangeRates(string $base_currency_code, CarbonInterface $date_time): Collection;

    /**
     * Get bulk historical exchange rate by currencies pair from the database
     * 
     * @param HistoricalBaseCurrencyDto[] $historical_base_currencies
     * 
     * @return Collection<string, Collection<CurrencyExchangeRateHistory>> The key is the base currency code concatenated with the date time in the format "BASE_YYYY-MM-DD"
     */
    public function getBulkHistoricalExchangeRates(array $historical_base_currencies): Collection;

    /**
     * Get historical exchange rate from the database
     *
     * @param string $base_currency_code
     * @param string $target_currency_code
     * @param CarbonInterface $date_time
     * 
     * @return CurrencyExchangeRateHistory
     * @throws ModelNotFoundException
     */
    public function getHistoricalExchangeRate(string $base_currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory;

    /**
     * Get bulk historical exchange rate by currencies pair from the database
     * 
     * @param HistoricalCurrenciesPairDto[] $historical_currencies_pairs
     * 
     * @return Collection<string, CurrencyExchangeRateHistory> The key is the currencies pair string in the format "BASE_TARGET_YYYY-MM-DD"
     */ 
    public function getBulkHistoricalExchangeRate(array $historical_currencies_pairs): Collection;
}
