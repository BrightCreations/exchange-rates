<?php

namespace BrightCreations\ExchangeRates\Facades;

use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isSupportHistoricalExchangeRate()
 * @method static Collection<CurrencyExchangeRate> storeExchangeRates(string $currency_code)
 * @method static Collection<CurrencyExchangeRate> storeBulkExchangeRatesForMultipleCurrencies(array $currencies_codes)
 * @method static Collection<CurrencyExchangeRate> getExchangeRates(string $currency_code)
 * @method static Collection<CurrencyExchangeRate> getAllExchangeRates()
 */
class ExchangeRate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ExchangeRateServiceInterface::class;
    }
}
