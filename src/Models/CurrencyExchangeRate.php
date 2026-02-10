<?php

namespace BrightCreations\ExchangeRates\Models;

use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CurrencyExchangeRate extends Model
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
        'provider',
        'last_update_date',
    ];

    public $table = 'currency_exchange_rates';

    public static $tablename = 'currency_exchange_rates';

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
     * Construct a CurrencyExchangeRate model from an ExchangeRatesDto
     *
     *
     * @return Collection<CurrencyExchangeRate>
     */
    public static function constructFromExchangeRatesDto(ExchangeRatesDto $dto): Collection
    {
        $data = collect();
        $base_code = $dto->getBaseCurrencyCode();
        $now = Carbon::now();
        foreach ($dto->getExchangeRates() as $code => $rate) {
            $data->push(new CurrencyExchangeRate([
                'base_currency_code' => $base_code,
                'target_currency_code' => $code,
                'exchange_rate' => $rate,
                'last_update_date' => $now,
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
