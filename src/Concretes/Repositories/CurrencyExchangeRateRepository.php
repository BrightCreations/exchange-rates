<?php

namespace BrightCreations\ExchangeRates\Concretes\Repositories;

use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CurrencyExchangeRateRepository implements CurrencyExchangeRateRepositoryInterface
{

    public function updateExchangeRates(string $base_currency_code, array $exchange_rates): bool
    {
        $dataToInsert = [];
        foreach ($exchange_rates as $code => $rate) {
            $dataToInsert[] = [
                'base_currency_code' => $base_currency_code,
                'target_currency_code' => $code,
                'exchange_rate' => $rate,
                'last_update_date' => Carbon::now(),
            ];
        }
        return DB::table(CurrencyExchangeRate::$tablename)->upsert(
            $dataToInsert,
            ['base_currency_code', 'target_currency_code'],
            ['exchange_rate', 'last_update_date'],
        );
    }

    public function updateBulkExchangeRates(array $exchange_rates): bool
    {
        $dataToInsert = [];
        foreach ($exchange_rates as $exchange_rate) {
            foreach ($exchange_rate->getExchangeRates() as $code => $rate) {
                $dataToInsert[] = [
                    'base_currency_code' => $exchange_rate->getBaseCurrencyCode(),
                    'target_currency_code' => $code,
                    'exchange_rate' => $rate,
                    'last_update_date' => Carbon::now(),
                ];
            }
        }

        return DB::table(CurrencyExchangeRate::$tablename)->upsert(
            $dataToInsert,
            ['base_currency_code', 'target_currency_code'],
            ['exchange_rate', 'last_update_date'],
        );
    }

    public function updateExchangeRatesHistory(string $base_currency_code, array $exchange_rates, CarbonInterface $date_time): bool
    {
        $dataToInsert = [];
        foreach ($exchange_rates as $code => $rate) {
            $dataToInsert[] = [
                'base_currency_code'    => $base_currency_code,
                'target_currency_code'  => $code,
                'exchange_rate'         => $rate,
                'date_time'             => $date_time,
                'last_update_date'      => Carbon::now(),
            ];
        }

        return DB::table(CurrencyExchangeRateHistory::$tablename)->upsert(
            $dataToInsert,
            [
                'base_currency_code',
                'target_currency_code',
                'date_time',
            ],
            [
                'exchange_rate',
                'last_update_date',
            ]
        );
    }

    public function updateBulkExchangeRatesHistory(array $historical_exchange_rates): bool
    {
        $dataToInsert = [];
        foreach ($historical_exchange_rates as $historical_exchange_rate) {
            foreach ($historical_exchange_rate->getExchangeRates() as $code => $rate) {
                $dataToInsert[] = [
                    'base_currency_code' => $historical_exchange_rate->getBaseCurrencyCode(),
                    'target_currency_code' => $code,
                    'exchange_rate' => $rate,
                    'date_time' => $historical_exchange_rate->getDateTime(),
                    'last_update_date' => Carbon::now(),
                ];
            }
        }

        return DB::table(CurrencyExchangeRateHistory::$tablename)->upsert(
            $dataToInsert,
            [
                'base_currency_code',
                'target_currency_code',
                'date_time',
            ],
            [
                'exchange_rate',
                'last_update_date',
            ]
        );
    }

    public function getAllExchangeRates(): Collection
    {
        return CurrencyExchangeRate::all();
    }

    public function getExchangeRates(string $base_currency_code): Collection
    {
        return CurrencyExchangeRate::where(compact('base_currency_code'))->get();
    }

    public function getBulkExchangeRates(array $base_currency_codes): Collection
    {
        return CurrencyExchangeRate::whereIn('base_currency_code', $base_currency_codes)->get()->groupBy('base_currency_code');
    }

    public function getExchangeRate(string $base_currency_code, string $target_currency_code): CurrencyExchangeRate
    {
        return CurrencyExchangeRate::where(compact('base_currency_code', 'target_currency_code'))->firstOrFail();
    }

    public function getBulkExchangeRate(array $currencies_pairs): Collection
    {
        $currencies_pairs_strings = array_map(fn($currencies_pair) => (string) $currencies_pair, $currencies_pairs);
        $currencies_pairs_strings_unique = array_unique($currencies_pairs_strings);

        return CurrencyExchangeRate::select('*', DB::raw("CONCAT(base_currency_code, '_', target_currency_code) as currencies_pair"))
            ->whereIn(DB::raw("CONCAT(base_currency_code, '_', target_currency_code)"), $currencies_pairs_strings_unique)
            ->get()
            ->groupBy('currencies_pair');
    }

    public function getHistoricalExchangeRates(string $base_currency_code, CarbonInterface $date_time): Collection
    {
        return CurrencyExchangeRateHistory::where(compact('base_currency_code'))
            ->whereDate('date_time', $date_time->format('Y-m-d'))->get();
    }

    public function getBulkHistoricalExchangeRates(array $historical_base_currencies): Collection
    {
        $historical_base_currencies_strings = array_map(fn($historical_base_currency) => (string) $historical_base_currency, $historical_base_currencies);
        $historical_base_currencies_strings_unique = array_unique($historical_base_currencies_strings);

        return CurrencyExchangeRateHistory::select(
            '*',
            DB::raw("CONCAT(base_currency_code, '_', DATE_FORMAT(date_time, '%Y-%m-%d')) as historical_base_currency")
        )
            ->whereIn(
                DB::raw("CONCAT(base_currency_code, '_', DATE_FORMAT(date_time, '%Y-%m-%d'))"),
                $historical_base_currencies_strings_unique
            )
            ->get()
            ->groupBy('historical_base_currency');
    }

    public function getHistoricalExchangeRate(string $base_currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory
    {
        return CurrencyExchangeRateHistory::where(compact('base_currency_code', 'target_currency_code'))
            ->whereDate('date_time', $date_time->format('Y-m-d'))->firstOrFail();
    }

    public function getBulkHistoricalExchangeRate(array $historical_currencies_pairs): Collection
    {
        $historical_currencies_pairs_strings = array_map(fn($historical_currencies_pair) => (string) $historical_currencies_pair, $historical_currencies_pairs);
        $historical_currencies_pairs_strings_unique = array_unique($historical_currencies_pairs_strings);

        return CurrencyExchangeRateHistory::select('*', DB::raw("CONCAT(base_currency_code, '_', target_currency_code, '_', DATE_FORMAT(date_time, '%Y-%m-%d')) as historical_currencies_pair"))
            ->whereIn(DB::raw("CONCAT(base_currency_code, '_', target_currency_code, '_', DATE_FORMAT(date_time, '%Y-%m-%d'))"), $historical_currencies_pairs_strings_unique)
            ->get()
            ->groupBy('historical_currencies_pair');
    }
}
