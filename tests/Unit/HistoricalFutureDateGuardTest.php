<?php

use BrightCreations\ExchangeRates\Concretes\ExchangeRateApiService;
use BrightCreations\ExchangeRates\Concretes\OpenExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use BrightCreations\ExchangeRates\Exceptions\HistoricalRatesUnavailableException;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;

/**
 * A provider HTTP client that answers historical requests from the fixtures and records every URL.
 */
function futureGuardHttp(array &$requestedUrls): PendingRequest
{
    return Mockery::mock(PendingRequest::class, function ($mock) use (&$requestedUrls) {
        $mock->shouldReceive('baseUrl')->andReturnSelf();
        $mock->shouldReceive('withHeaders')->andReturnSelf();
        $mock->shouldReceive('throw')->andReturnSelf();
        $mock->shouldReceive('get')->andReturnUsing(function ($url) use (&$requestedUrls) {
            $requestedUrls[] = $url;

            $fixture = str_contains($url, '/history/')
                ? __DIR__.'/Data/ExchangeRateApi/exchange_rate_api_history_response.json'
                : __DIR__.'/Data/OpenExchangeRate/open_exchange_rate_history_response.json';

            return new Illuminate\Http\Client\Response(new Response(200, [], file_get_contents($fixture)));
        });
    });
}

beforeEach(function () {
    Cache::flush();
    Carbon::setTestNow(Carbon::parse('2026-11-04 12:00:00', 'UTC'));
});

afterEach(function () {
    Carbon::setTestNow();
});

dataset('historical providers', [
    'Open Exchange Rates' => OpenExchangeRateService::class,
    'ExchangeRate-API' => ExchangeRateApiService::class,
    'World Bank' => WorldBankExchangeRateApiService::class,
]);

it('rejects a future date without calling the provider', function (string $serviceClass) {
    $requestedUrls = [];
    $service = app($serviceClass, ['http' => futureGuardHttp($requestedUrls)]);

    expect(fn () => $service->storeHistoricalExchangeRates('USD', Carbon::parse('2026-11-05')))
        ->toThrow(HistoricalRatesUnavailableException::class);
    expect($requestedUrls)->toBe([]);
})->with('historical providers');

it('rejects a bulk request containing a future date without calling the provider', function (string $serviceClass) {
    $requestedUrls = [];
    $service = app($serviceClass, ['http' => futureGuardHttp($requestedUrls)]);

    expect(fn () => $service->storeBulkHistoricalExchangeRatesForMultipleCurrencies([
        new HistoricalBaseCurrencyDto('USD', Carbon::parse('2026-11-01')),
        new HistoricalBaseCurrencyDto('USD', Carbon::parse('2026-12-01')),
    ]))->toThrow(HistoricalRatesUnavailableException::class);
    expect($requestedUrls)->toBe([]);
})->with('historical providers');

it('still fetches today (UTC)', function (string $serviceClass) {
    $requestedUrls = [];
    $service = app($serviceClass, ['http' => futureGuardHttp($requestedUrls)]);

    $service->storeHistoricalExchangeRates('USD', Carbon::parse('2026-11-04'));

    expect($requestedUrls)->toHaveCount(1);
})->with([
    'Open Exchange Rates' => OpenExchangeRateService::class,
    'ExchangeRate-API' => ExchangeRateApiService::class,
]);

it('compares the date as sent to the provider, not the instant', function () {
    // 01:00 on the 5th in Cairo is still the 4th in UTC, but the provider would be asked for the 5th
    Carbon::setTestNow(Carbon::parse('2026-11-04 23:30:00', 'UTC'));
    $requestedUrls = [];
    $service = app(OpenExchangeRateService::class, ['http' => futureGuardHttp($requestedUrls)]);

    expect(fn () => $service->storeHistoricalExchangeRates('USD', Carbon::parse('2026-11-05 01:00:00', 'Africa/Cairo')))
        ->toThrow(HistoricalRatesUnavailableException::class);
    expect($requestedUrls)->toBe([]);
});

it('fills only the past days of a period that reaches into the future', function (string $serviceClass) {
    $requestedUrls = [];
    $service = app($serviceClass, ['http' => futureGuardHttp($requestedUrls)]);

    $service->getHistoricalExchangeRatesForPeriod('USD', 'AED', Carbon::parse('2026-11-03'), Carbon::parse('2026-11-06'));

    expect($requestedUrls)->toHaveCount(2);
    expect(implode(' ', $requestedUrls))->not->toContain('2026-11-05')
        ->not->toContain('2026/11/5')
        ->not->toContain('2026-11-06')
        ->not->toContain('2026/11/6');
})->with([
    'Open Exchange Rates' => OpenExchangeRateService::class,
    'ExchangeRate-API' => ExchangeRateApiService::class,
]);
