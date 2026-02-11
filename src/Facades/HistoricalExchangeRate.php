<?php

namespace BrightCreations\ExchangeRates\Facades;

use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection<CurrencyExchangeRateHistory> storeHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time)
 * @method static Collection<CurrencyExchangeRateHistory> storeBulkHistoricalExchangeRatesForMultipleCurrencies(HistoricalBaseCurrencyDto[] $historical_base_currencies)
 * @method static Collection<CurrencyExchangeRateHistory> getHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time)
 * @method static CurrencyExchangeRateHistory getHistoricalExchangeRate(string $currency_code, string $target_currency_code, CarbonInterface $date_time)
 */
class HistoricalExchangeRate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return HistoricalSupportExchangeRateServiceInterface::class;
    }
}
