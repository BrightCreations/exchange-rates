<?php

namespace BrightCreations\ExchangeRates\Contracts;

use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use Illuminate\Support\Collection;

interface ExchangeRateServiceInterface
{
    /**
     * Check if the service supports historical exchange rates
     */
    public function isSupportHistoricalExchangeRate(): bool;

    /**
     * Store exchange rates in the database
     *
     *
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeExchangeRates(string $currency_code): Collection;

    /**
     * Store exchange rates for multiple currencies
     *
     *
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeBulkExchangeRatesForMultipleCurrencies(array $currencies_codes): Collection;

    /**
     * Get exchange rates from the database
     */
    public function getExchangeRates(string $currency_code): Collection;

    /**
     * Get all exchange rates from the database
     */
    public function getAllExchangeRates(): Collection;
}
