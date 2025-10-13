<?php

namespace BrightCreations\ExchangeRates\Contracts;

use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface HistoricalSupportExchangeRateServiceInterface
{
    /**
     * Store historical exchange rates in the database
     *
     *
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function storeHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection;

    /**
     * Store historical exchange rates for multiple currencies
     *
     * @param  HistoricalBaseCurrencyDto[]  $historical_base_currencies
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function storeBulkHistoricalExchangeRatesForMultipleCurrencies(array $historical_base_currencies): Collection;

    /**
     * Get historical exchange rates from the database
     *
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function getHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection;

    /**
     * Get a specific historical exchange rate from the database
     */
    public function getHistoricalExchangeRate(string $currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory;
}
