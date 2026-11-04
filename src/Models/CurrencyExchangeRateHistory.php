<?php

namespace BrightCreations\ExchangeRates\Models;

use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * @property int $id
 * @property string $base_currency_code
 * @property string $target_currency_code
 * @property string $exchange_rate
 * @property string|null $provider
 * @property Carbon|null $date_time
 * @property Carbon|null $last_update_date
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
 * @method static Builder|CurrencyExchangeRateHistory dailyFirst()
 * @method static Builder|CurrencyExchangeRateHistory excludingProvisional()
 * @method static Builder|CurrencyExchangeRateHistory onlyProvisional()
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

    /**
     * Whether this rate comes from a provisional provider (a period average, e.g. World Bank's
     * yearly rate) rather than a daily rate for its date.
     */
    public function isProvisional(): bool
    {
        return $this->provider !== null && in_array($this->provider, static::provisionalProviders(), true);
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIGURATION
    |--------------------------------------------------------------------------
    */
    /**
     * Create or update a currency exchange rate history record
     *
     * @param  array<string, mixed>  $attributes  The attributes to create or update
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
     * @param  HistoricalExchangeRatesDto  $dto  The historical exchange rates DTO to construct from
     * @param  string|null  $provider  The provider the rates came from, as stored in the database
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public static function constructFromHistoricalExchangeRatesDto(HistoricalExchangeRatesDto $dto, ?string $provider = null): Collection
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
                'provider' => $provider,
                'date_time' => $date_time,
                'last_update_date' => $now,
            ]));
        }

        return $data;
    }

    /**
     * Providers whose rates are period averages rather than daily rates
     * (config `exchange-rates.provisional_providers`, stored provider names).
     *
     * @return list<string>
     */
    public static function provisionalProviders(): array
    {
        return array_values((array) Config::get('exchange-rates.provisional_providers', ['WorldBankExchangeRateApiService']));
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

    /**
     * Order daily rates before provisional ones, so `first()` prefers a daily rate.
     */
    public function scopeDailyFirst(Builder $query): Builder
    {
        $providers = static::provisionalProviders();
        if ($providers === []) {
            return $query;
        }

        $placeholders = implode(', ', array_fill(0, count($providers), '?'));

        return $query->orderByRaw("CASE WHEN provider IN ({$placeholders}) THEN 1 ELSE 0 END", $providers);
    }

    /**
     * Only daily rates (rows without a provider predate provider tracking and count as daily).
     */
    public function scopeExcludingProvisional(Builder $query): Builder
    {
        $providers = static::provisionalProviders();
        if ($providers === []) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->whereNull('provider')->orWhereNotIn('provider', $providers));
    }

    /**
     * Only provisional rates.
     */
    public function scopeOnlyProvisional(Builder $query): Builder
    {
        return $query->whereIn('provider', static::provisionalProviders());
    }

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
