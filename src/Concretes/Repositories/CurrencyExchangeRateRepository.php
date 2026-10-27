<?php

namespace BrightCreations\ExchangeRates\Concretes\Repositories;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\Dtos\ExchangeRatesDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto;
use BrightCreations\ExchangeRates\Dtos\HistoricalExchangeRatesDto;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CurrencyExchangeRateRepository implements CurrencyExchangeRateRepositoryInterface
{
    /**
     * Build a BigDecimal from a provider rate without losing its fraction.
     *
     * brick/math 1.0 no longer accepts floats: PHP coerces 0.83 to int 0, which the callers
     * then skip as a zero rate. Casting to string is what brick/math 0.14 did itself.
     *
     * @param  string|float|int  $rate
     */
    private function toBigDecimal($rate): BigDecimal
    {
        return BigDecimal::of(is_float($rate) ? (string) $rate : $rate);
    }

    /**
     * Calculate the reversed exchange rate (1 / rate) with proper precision and rounding.
     *
     * @param  string|float|int  $rate  The exchange rate to reverse
     * @return string The reversed exchange rate as a string with 10 decimal places
     *
     * @throws \InvalidArgumentException If the rate is zero or invalid
     */
    private function calculateReversedRate($rate): string
    {
        $rateBigDecimal = $this->toBigDecimal($rate);

        if ($rateBigDecimal->isZero()) {
            throw new \InvalidArgumentException('Cannot calculate reversed rate: exchange rate cannot be zero');
        }

        return BigDecimal::of(1)
            ->dividedBy($rateBigDecimal, 10, RoundingMode::HALF_UP)
            ->__toString();
    }

    public function updateExchangeRates(string $base_currency_code, array $exchange_rates, ?string $provider = null): bool
    {
        $dataToInsert = [];
        foreach ($exchange_rates as $code => $rate) {
            $rateBigDecimal = $this->toBigDecimal($rate);

            if ($rateBigDecimal->isZero()) {
                continue;
            }

            $dataToInsert[] = [
                'base_currency_code' => $base_currency_code,
                'target_currency_code' => $code,
                'exchange_rate' => $rateBigDecimal->__toString(),
                'provider' => $provider,
                'last_update_date' => Carbon::now(),
            ];
        }

        return DB::table(CurrencyExchangeRate::$tablename)->upsert(
            $dataToInsert,
            ['base_currency_code', 'target_currency_code'],
            ['exchange_rate', 'provider', 'last_update_date'],
        );
    }

    public function updateBulkExchangeRates(array $exchange_rates, ?string $provider = null): bool
    {
        $dataToInsert = [];
        foreach ($exchange_rates as $exchange_rate) {
            if (! ($exchange_rate instanceof ExchangeRatesDto)) {
                throw new \InvalidArgumentException('Exchange rate must be an instance of \BrightCreations\ExchangeRates\Dtos\ExchangeRatesDto');
            }
            foreach ($exchange_rate->getExchangeRates() as $code => $rate) {
                $rateBigDecimal = $this->toBigDecimal($rate);

                if ($rateBigDecimal->isZero()) {
                    continue;
                }

                $dataToInsert[] = [
                    'base_currency_code' => $exchange_rate->getBaseCurrencyCode(),
                    'target_currency_code' => $code,
                    'exchange_rate' => $rateBigDecimal->__toString(),
                    'provider' => $provider,
                    'last_update_date' => Carbon::now(),
                ];
            }
        }

        return DB::table(CurrencyExchangeRate::$tablename)->upsert(
            $dataToInsert,
            ['base_currency_code', 'target_currency_code'],
            ['exchange_rate', 'provider', 'last_update_date'],
        );
    }

    public function updateExchangeRatesHistory(string $base_currency_code, array $exchange_rates, CarbonInterface $date_time, ?string $provider = null): bool
    {
        $dataToInsert = [];
        foreach ($exchange_rates as $code => $rate) {
            $rateBigDecimal = $this->toBigDecimal($rate);

            if ($rateBigDecimal->isZero()) {
                continue;
            }

            $dataToInsert[] = [
                'base_currency_code' => $base_currency_code,
                'target_currency_code' => $code,
                'exchange_rate' => $rateBigDecimal->__toString(),
                'provider' => $provider,
                'date_time' => $date_time,
                'last_update_date' => Carbon::now(),
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
                'provider',
                'last_update_date',
            ]
        );
    }

    public function updateBulkExchangeRatesHistory(array $historical_exchange_rates, ?string $provider = null): bool
    {
        $dataToInsert = [];
        foreach ($historical_exchange_rates as $historical_exchange_rate) {
            if (! ($historical_exchange_rate instanceof HistoricalExchangeRatesDto)) {
                throw new \InvalidArgumentException('Historical exchange rate must be an instance of \BrightCreations\ExchangeRates\Dtos\HistoricalExchangeRatesDto');
            }
            foreach ($historical_exchange_rate->getExchangeRates() as $code => $rate) {
                $rateBigDecimal = $this->toBigDecimal($rate);

                if ($rateBigDecimal->isZero()) {
                    continue;
                }

                $dataToInsert[] = [
                    'base_currency_code' => $historical_exchange_rate->getBaseCurrencyCode(),
                    'target_currency_code' => $code,
                    'exchange_rate' => $rateBigDecimal->__toString(),
                    'provider' => $provider,
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
                'provider',
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

    public function getExchangeRatesByTargetCurrency(string $target_currency_code): Collection
    {
        return CurrencyExchangeRate::where(compact('target_currency_code'))->get();
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
        $uniquePairs = [];
        foreach ($currencies_pairs as $pair) {
            $key = (string) $pair;
            if (! isset($uniquePairs[$key])) {
                $uniquePairs[$key] = $pair;
            }
        }

        return CurrencyExchangeRate::query()
            ->where(function ($query) use ($uniquePairs): void {
                foreach ($uniquePairs as $pair) {
                    $query->orWhere(function ($q) use ($pair): void {
                        $q->where('base_currency_code', $pair->getBaseCurrencyCode())
                            ->where('target_currency_code', $pair->getTargetCurrencyCode());
                    });
                }
            })
            ->get()
            ->groupBy(fn ($row) => $row->base_currency_code.'_'.$row->target_currency_code);
    }

    public function getHistoricalExchangeRates(string $base_currency_code, CarbonInterface $date_time): Collection
    {
        return CurrencyExchangeRateHistory::where(compact('base_currency_code'))
            ->whereDate('date_time', $date_time->format('Y-m-d'))->get();
    }

    public function getBulkHistoricalExchangeRates(array $historical_base_currencies): Collection
    {
        $uniqueEntries = [];
        foreach ($historical_base_currencies as $entry) {
            $key = (string) $entry;
            if (! isset($uniqueEntries[$key])) {
                $uniqueEntries[$key] = $entry;
            }
        }

        return CurrencyExchangeRateHistory::query()
            ->where(function ($query) use ($uniqueEntries): void {
                foreach ($uniqueEntries as $entry) {
                    $query->orWhere(function ($q) use ($entry): void {
                        $q->where('base_currency_code', $entry->getBaseCurrencyCode())
                            ->whereDate('date_time', $entry->getDateTime()->format('Y-m-d'));
                    });
                }
            })
            ->get()
            ->groupBy(fn ($row) => $row->base_currency_code.'_'.Carbon::parse($row->date_time)->format('Y-m-d'));
    }

    public function getHistoricalExchangeRate(string $base_currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory
    {
        return CurrencyExchangeRateHistory::where(compact('base_currency_code', 'target_currency_code'))
            ->whereDate('date_time', $date_time->format('Y-m-d'))->firstOrFail();
    }

    public function getBulkHistoricalExchangeRate(array $historical_currencies_pairs): Collection
    {
        $uniquePairs = [];
        foreach ($historical_currencies_pairs as $pair) {
            $key = (string) $pair;
            if (! isset($uniquePairs[$key])) {
                $uniquePairs[$key] = $pair;
            }
        }

        return CurrencyExchangeRateHistory::query()
            ->where(function ($query) use ($uniquePairs): void {
                foreach ($uniquePairs as $pair) {
                    $query->orWhere(function ($q) use ($pair): void {
                        $q->where('base_currency_code', $pair->getBaseCurrencyCode())
                            ->where('target_currency_code', $pair->getTargetCurrencyCode())
                            ->whereDate('date_time', $pair->getDateTime()->format('Y-m-d'));
                    });
                }
            })
            ->get()
            ->groupBy([
                'base_currency_code',
                fn ($item) => $item->target_currency_code,
                fn ($item) => Carbon::parse($item->date_time)->format('Y-m-d'),
            ]);
    }

    /**
     * Get the daily historical exchange rates for a currency pair within a date period (inclusive),
     * ordered by date ascending.
     */
    public function getHistoricalExchangeRatesForPeriod(string $base_currency_code, string $target_currency_code, CarbonInterface $start_date, CarbonInterface $end_date): Collection
    {
        if ($start_date->greaterThan($end_date)) {
            throw new \InvalidArgumentException('$start_date must not be after $end_date');
        }

        return CurrencyExchangeRateHistory::where(compact('base_currency_code', 'target_currency_code'))
            ->whereDate('date_time', '>=', $start_date->format('Y-m-d'))
            ->whereDate('date_time', '<=', $end_date->format('Y-m-d'))
            ->orderBy('date_time')
            ->get();
    }

    /**
     * Get the last historical rate on or before the given date for a currency pair.
     */
    public function getPreviousHistoricalRate(string $base_currency_code, string $target_currency_code, CarbonInterface $target_date): ?CurrencyExchangeRateHistory
    {
        return CurrencyExchangeRateHistory::where(compact('base_currency_code', 'target_currency_code'))
            ->where('date_time', '<=', $target_date)
            ->orderBy('date_time', 'desc')
            ->first();
    }

    /**
     * Get the first historical rate on or after the given date for a currency pair.
     */
    public function getNextHistoricalRate(string $base_currency_code, string $target_currency_code, CarbonInterface $target_date): ?CurrencyExchangeRateHistory
    {
        return CurrencyExchangeRateHistory::where(compact('base_currency_code', 'target_currency_code'))
            ->where('date_time', '>=', $target_date)
            ->orderBy('date_time', 'asc')
            ->first();
    }

    /**
     * Get the two historical exchange rate records that bound the requested date for a given currency pair.
     *
     * It will return at most two models (empty if not possible):
     * - d1, r1: last rate on or before $target_date
     * - d2, r2: first rate on or after $target_date
     *
     * If one of the bounds does not exist or both bounds are the same record, an empty collection is returned.
     */
    public function getBoundingHistoricalRates(string $base_currency_code, string $target_currency_code, CarbonInterface $target_date): Collection
    {
        $before = $this->getPreviousHistoricalRate($base_currency_code, $target_currency_code, $target_date);
        $after = $this->getNextHistoricalRate($base_currency_code, $target_currency_code, $target_date);

        if (! $before || ! $after) {
            return collect();
        }

        // If both bounds resolve to the same underlying record, we don't have a proper interval
        if ($before->is($after)) {
            return collect();
        }

        return collect([$before, $after]);
    }

    /**
     * @param  HistoricalCurrenciesPairDto[]  $historical_currencies_pairs
     * @return Collection<string, Collection<CurrencyExchangeRateHistory>>
     */
    public function getBulkBoundingHistoricalRates(array $historical_currencies_pairs): Collection
    {
        /** @var array<string, HistoricalCurrenciesPairDto> $uniqueDtos */
        $uniqueDtos = [];

        foreach ($historical_currencies_pairs as $dto) {
            $uniqueDtos[(string) $dto] = $dto;
        }

        /** @var array<string, list<HistoricalCurrenciesPairDto>> $byPair */
        $byPair = [];

        foreach ($uniqueDtos as $dto) {
            $pairKey = $dto->getBaseCurrencyCode().'_'.$dto->getTargetCurrencyCode();
            $byPair[$pairKey][] = $dto;
        }

        $results = collect();

        foreach ($byPair as $dtos) {
            if (count($dtos) === 1) {
                $dto = $dtos[0];
                $results[(string) $dto] = $this->getBoundingHistoricalRates(
                    $dto->getBaseCurrencyCode(),
                    $dto->getTargetCurrencyCode(),
                    $dto->getDateTime(),
                );

                continue;
            }

            $first = $dtos[0];
            $rates = CurrencyExchangeRateHistory::where([
                'base_currency_code' => $first->getBaseCurrencyCode(),
                'target_currency_code' => $first->getTargetCurrencyCode(),
            ])->orderBy('date_time')->get();

            foreach ($dtos as $dto) {
                $results[(string) $dto] = $this->computeBoundingFromRates($rates, $dto->getDateTime());
            }
        }

        return $results;
    }

    /**
     * @param  Collection<int, CurrencyExchangeRateHistory>  $rates
     * @return Collection<int, CurrencyExchangeRateHistory>
     */
    private function computeBoundingFromRates(Collection $rates, CarbonInterface $targetDate): Collection
    {
        $before = null;
        $after = null;

        foreach ($rates as $rate) {
            $dateTime = $rate->date_time instanceof CarbonInterface
                ? $rate->date_time
                : Carbon::parse($rate->date_time);

            if ($dateTime <= $targetDate) {
                $before = $rate;
            } elseif ($after === null) {
                $after = $rate;
            }
        }

        if (! $before || ! $after) {
            return collect();
        }

        if ($before->is($after)) {
            return collect();
        }

        return collect([$before, $after]);
    }
}
