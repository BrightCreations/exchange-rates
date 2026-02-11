<?php

namespace BrightCreations\ExchangeRates\Models;

use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $base_currency_code
 * @property string $target_currency_code
 * @property string $exchange_rate
 * @property string|null $provider
 * @property \Carbon\Carbon|null $last_update_date
 * @property-read string $table
 * @property-read string $tablename
 *
 * @method static Builder|CurrencyExchangeRate whereId($value)
 * @method static Builder|CurrencyExchangeRate whereBaseCurrencyCode($value)
 * @method static Builder|CurrencyExchangeRate whereTargetCurrencyCode($value)
 * @method static Builder|CurrencyExchangeRate whereExchangeRate($value)
 * @method static Builder|CurrencyExchangeRate whereProvider($value)
 * @method static Builder|CurrencyExchangeRate whereLastUpdateDate($value)
 * @method static Builder|CurrencyExchangeRate newModelQuery()
 * @method static Builder|CurrencyExchangeRate newQuery()
 * @method static Builder|CurrencyExchangeRate query()
 * @method static CurrencyExchangeRate|null find($id, $columns = ['*'])
 * @method static CurrencyExchangeRate findOrFail($id, $columns = ['*'])
 * @method static CurrencyExchangeRate create(array $attributes = [])
 * @method static CurrencyExchangeRate firstOrCreate(array $attributes, array $values = [])
 * @method static CurrencyExchangeRate updateOrCreate(array $attributes, array $values = [])
 * @method static Collection|CurrencyExchangeRate[] all($columns = ['*'])
 * @method static Collection|CurrencyExchangeRate[] get($columns = ['*'])
 * @method static CurrencyExchangeRate|null first($columns = ['*'])
 * @method static CurrencyExchangeRate firstOrFail($columns = ['*'])
 */
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
    /**
     * Create or update a currency exchange rate record
     *
     * @param array<string, mixed> $attributes The attributes to create or update
     * @return static
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
     * @param ExchangeRatesDto $dto The exchange rates DTO to construct from
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
