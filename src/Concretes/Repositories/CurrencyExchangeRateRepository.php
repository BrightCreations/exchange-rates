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

    public function getExchangeRates(string $base_currency_code): Collection
    {
        return CurrencyExchangeRate::where(compact('base_currency_code'))->get();
    }

    public function getAllExchangeRates(): Collection
    {
        return CurrencyExchangeRate::all();
    }

    public function getExchangeRate(string $base_currency_code, string $target_currency_code): CurrencyExchangeRate
    {
        return CurrencyExchangeRate::where(compact('base_currency_code', 'target_currency_code'))->firstOrFail();
    }

    public function getHistoricalExchangeRates(string $base_currency_code, CarbonInterface $date_time): Collection
    {
        return CurrencyExchangeRateHistory::where(compact('base_currency_code'))
            ->whereDate('date_time', $date_time->format('Y-m-d'))->get();
    }

    public function getHistoricalExchangeRate(string $base_currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory
    {
        return CurrencyExchangeRateHistory::where(compact('base_currency_code', 'target_currency_code'))
            ->whereDate('date_time', $date_time->format('Y-m-d'))->firstOrFail();
    }
}
