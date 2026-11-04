<?php

use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    Carbon::setTestNow(Carbon::parse('2026-11-04 12:00:00'));

    // `lastupdated` is deliberately unrelated to any requested year
    $http = Mockery::mock(PendingRequest::class, function ($mock) {
        $mock->shouldReceive('baseUrl')->andReturnSelf();
        $mock->shouldReceive('withHeaders')->andReturnSelf();
        $mock->shouldReceive('throw')->andReturnSelf();
        $mock->shouldReceive('get')->andReturnUsing(function ($url) {
            preg_match('#date=(\d{4})#', $url, $matches);
            $gbpPerUsd = $matches[1] === '2022' ? 0.81 : 0.80;

            return new Illuminate\Http\Client\Response(new Response(200, [], json_encode([
                ['page' => 1, 'pages' => 1, 'per_page' => 1000, 'total' => 3, 'lastupdated' => '2025-10-07'],
                [
                    ['countryiso3code' => 'USA', 'value' => 1.0, 'date' => $matches[1]],
                    ['countryiso3code' => 'GBR', 'value' => $gbpPerUsd, 'date' => $matches[1]],
                    ['countryiso3code' => 'EMU', 'value' => 0.92, 'date' => $matches[1]],
                ],
            ])));
        });
    });

    $this->service = app(WorldBankExchangeRateApiService::class, ['http' => $http]);
});

afterEach(function () {
    Carbon::setTestNow();
});

function worldBankStoredDates(): array
{
    return CurrencyExchangeRateHistory::query()
        ->get()
        ->map(fn ($rate) => Carbon::parse($rate->date_time)->format('Y-m-d'))
        ->unique()
        ->sort()
        ->values()
        ->all();
}

it('stores historical rates under the requested date, not the dataset lastupdated date', function () {
    $rates = $this->service->storeHistoricalExchangeRates('USD', Carbon::parse('2023-06-15'));

    expect(worldBankStoredDates())->toBe(['2023-06-15']);
    expect($rates->first()->date_time->format('Y-m-d'))->toBe('2023-06-15');
});

it('stores each requested year of a bulk backfill under its own date', function () {
    $this->service->storeBulkHistoricalExchangeRatesForMultipleCurrencies([
        new HistoricalBaseCurrencyDto('USD', Carbon::parse('2022-01-01')),
        new HistoricalBaseCurrencyDto('USD', Carbon::parse('2023-01-01')),
    ]);

    expect(worldBankStoredDates())->toBe(['2022-01-01', '2023-01-01']);

    $gbp2022 = CurrencyExchangeRateHistory::where('target_currency_code', 'GBP')->whereDate('date_time', '2022-01-01')->first();
    $gbp2023 = CurrencyExchangeRateHistory::where('target_currency_code', 'GBP')->whereDate('date_time', '2023-01-01')->first();
    expect((string) $gbp2022->exchange_rate)->toBe('0.81');
    expect((string) $gbp2023->exchange_rate)->toBe('0.8');
});

it('stores one row set per currency and date when a bulk request repeats it', function () {
    $this->service->storeBulkHistoricalExchangeRatesForMultipleCurrencies([
        new HistoricalBaseCurrencyDto('USD', Carbon::parse('2023-01-01')),
        new HistoricalBaseCurrencyDto('USD', Carbon::parse('2023-01-01')),
    ]);

    expect(CurrencyExchangeRateHistory::where('target_currency_code', 'GBP')->count())->toBe(1);
});

it('stores the history row of a latest-rates fetch at fetch time', function () {
    $this->service->storeExchangeRates('USD');

    expect(worldBankStoredDates())->toBe(['2026-11-04']);
});
