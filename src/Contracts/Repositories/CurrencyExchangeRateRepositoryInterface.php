<?php

namespace Brights\ExchangeRates\Contracts\Repositories;

use Brights\ExchangeRates\Models\CurrencyExchangeRate;
use Brights\ExchangeRates\Models\CurrencyExchangeRateHistory;
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
     * Get exchange rates from the database
     *
     * @param string $base_currency_code
     * 
     * @return Collection
     */
    public function getExchangeRates(string $base_currency_code): Collection;

    /**
     * Get all exchange rates from the database
     * 
     * @return Collection
     */
    public function getAllExchangeRates(): Collection;

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
     * Get historical exchange rates from the database
     *
     * @param string $base_currency_code
     * @param CarbonInterface $date_time
     * 
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function getHistoricalExchangeRates(string $base_currency_code, CarbonInterface $date_time): Collection;

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
}
