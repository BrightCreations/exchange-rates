<?php

namespace BrightCreations\ExchangeRates\Models;

use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CurrencyExchangeRateHistory extends Model
{
    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'base_currency_code',
        'target_currency_code',
        'exchange_rate',
        'date_time',
        'last_update_date',
    ];

    public $table = 'currency_exchange_rates_history';
    public static $tablename = 'currency_exchange_rates_history';
    public $timestamps = false;

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | CONFIGURATION
    |--------------------------------------------------------------------------
    */
    public static function createOrUpdate($attributes)
    {
        // Attempt to find a record with the provided user_id
        $record = static::where([
            ['base_currency_code', '=', $attributes['base_currency_code'] ?? null],
            ['target_currency_code', '=', $attributes['target_currency_code'] ?? null],
            ['last_update_timestamp', '=', $attributes['last_update_timestamp'] ?? null],
            ['next_update_timestamp', '=', $attributes['next_update_timestamp'] ?? null],
        ])->first();

        if ($record) {
            // If record exists, update it
            $record->update($attributes);
        } else {
            // If record doesn't exist, create a new one
            $record = static::create($attributes);
        }

        return $record;
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC FUNCTIONS
    |--------------------------------------------------------------------------
    */
    /**
     * Construct a CurrencyExchangeRateHistory model from a HistoricalExchangeRatesDto
     *
     * @param HistoricalExchangeRatesDto $dto
     * 
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public static function constructFromHistoricalExchangeRatesDto(HistoricalExchangeRatesDto $dto): Collection
    {
        $data = collect();
        $base_code = $dto->getBaseCurrencyCode();
        $date_time = $dto->getDateTime();
        $now = Carbon::now();
        foreach ($dto->getExchangeRates() as $code => $rate) {
            $data->push(new CurrencyExchangeRateHistory([
                'base_currency_code'    => $base_code,
                'target_currency_code'  => $code,
                'exchange_rate'         => $rate,
                'date_time'             => $date_time,
                'last_update_date'      => $now,
            ]));
        }
        return $data;
    }
    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | TESTING
    |--------------------------------------------------------------------------
    */
}
