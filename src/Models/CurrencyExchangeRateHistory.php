<?php

namespace BrightCreations\ExchangeRates\Models;

use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
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
 * @property \Carbon\Carbon|null $date_time
 * @property \Carbon\Carbon|null $last_update_date
 * @property-read string $table
 * @property-read string $tablename
 *
 * @method static Builder|CurrencyExchangeRateHistory whereId($value)
 * @method static Builder|CurrencyExchangeRateHistory whereBaseCurrencyCode($value)
 * @method static Builder|CurrencyExchangeRateHistory whereTargetCurrencyCode($value)
 * @method static Builder|CurrencyExchangeRateHistory whereExchangeRate($value)
 * @method static Builder|CurrencyExchangeRateHistory whereProvider($value)
 * @method static Builder|CurrencyExchangeRateHistory whereDateTime($value)
 * @method static Builder|CurrencyExchangeRateHistory whereLastUpdateDate($value)
 * @method static Builder|CurrencyExchangeRateHistory newModelQuery()
 * @method static Builder|CurrencyExchangeRateHistory newQuery()
 * @method static Builder|CurrencyExchangeRateHistory query()
 * @method static CurrencyExchangeRateHistory|null find($id, $columns = ['*'])
 * @method static CurrencyExchangeRateHistory findOrFail($id, $columns = ['*'])
 * @method static CurrencyExchangeRateHistory create(array $attributes = [])
 * @method static CurrencyExchangeRateHistory firstOrCreate(array $attributes, array $values = [])
 * @method static CurrencyExchangeRateHistory updateOrCreate(array $attributes, array $values = [])
 * @method static Collection|CurrencyExchangeRateHistory[] all($columns = ['*'])
 * @method static Collection|CurrencyExchangeRateHistory[] get($columns = ['*'])
 * @method static CurrencyExchangeRateHistory|null first($columns = ['*'])
 * @method static CurrencyExchangeRateHistory firstOrFail($columns = ['*'])
 */
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
        'provider',
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
    /**
     * Create or update a currency exchange rate history record
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
     * @param HistoricalExchangeRatesDto $dto The historical exchange rates DTO to construct from
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
                'base_currency_code' => $base_code,
                'target_currency_code' => $code,
                'exchange_rate' => $rate,
                'date_time' => $date_time,
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
