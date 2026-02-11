<?php

namespace BrightCreations\ExchangeRates\Facades;

use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
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
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool updateExchangeRates(string $base_currency_code, array $exchange_rates, ?string $provider = null)
 * @method static bool updateBulkExchangeRates(ExchangeRatesDto[] $exchange_rates, ?string $provider = null)
 * @method static bool updateExchangeRatesHistory(string $base_currency_code, array $exchange_rates, CarbonInterface $date_time, ?string $provider = null)
 * @method static bool updateBulkExchangeRatesHistory(HistoricalExchangeRatesDto[] $historical_exchange_rates, ?string $provider = null)
 * @method static Collection<CurrencyExchangeRate> getAllExchangeRates()
 * @method static Collection<CurrencyExchangeRate> getExchangeRates(string $base_currency_code)
 * @method static Collection<string, Collection<CurrencyExchangeRate>> getBulkExchangeRates(array $base_currency_codes)
 * @method static CurrencyExchangeRate getExchangeRate(string $base_currency_code, string $target_currency_code)
 * @method static Collection<string, CurrencyExchangeRate> getBulkExchangeRate(CurrenciesPairDto[] $currencies_pairs)
 * @method static Collection<CurrencyExchangeRateHistory> getHistoricalExchangeRates(string $base_currency_code, CarbonInterface $date_time)
 * @method static Collection<string, Collection<CurrencyExchangeRateHistory>> getBulkHistoricalExchangeRates(HistoricalBaseCurrencyDto[] $historical_base_currencies)
 * @method static CurrencyExchangeRateHistory getHistoricalExchangeRate(string $base_currency_code, string $target_currency_code, CarbonInterface $date_time)
 * @method static Collection<string, CurrencyExchangeRateHistory> getBulkHistoricalExchangeRate(HistoricalCurrenciesPairDto[] $historical_currencies_pairs)
 */
class ExchangeRateRepository extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CurrencyExchangeRateRepositoryInterface::class;
    }
}
