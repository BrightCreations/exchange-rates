<?php

namespace BrightCreations\ExchangeRates\Contracts;

use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use Illuminate\Support\Collection;

interface ExchangeRateServiceInterface
{

    /**
     * Check if the service supports historical exchange rates
     *
     * @return bool
     */
    public function isSupportHistoricalExchangeRate(): bool;

    /**
     * Store exchange rates in the database
     *
     * @param string $currency_code
     * 
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeExchangeRates(string $currency_code): Collection;

    /**
     * Store exchange rates for multiple currencies
     *
     * @param array $currencies_codes
     * 
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeBulkExchangeRatesForMultipleCurrencies(array $currencies_codes): Collection;

    /**
     * Get exchange rates from the database
     *
     * @param string $currency_code
     * 
     * @return Collection
     */
    public function getExchangeRates(string $currency_code): Collection;

    /**
     * Get all exchange rates from the database
     * 
     * @return Collection
     */
    public function getAllExchangeRates(): Collection;
}
