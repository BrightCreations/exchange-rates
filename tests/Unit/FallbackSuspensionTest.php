<?php

use BrightCreations\ExchangeRates\Concretes\FallbackExchangeRateService;
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;

/**
 * Bind a fresh stub class name to the given mock and return the class name.
 */
function suspensionStubService(MockInterface $mock): string
{
    $stubClass = 'StubSuspensionService_'.uniqid();
    eval("class {$stubClass} {}");
    app()->bind($stubClass, fn () => $mock);

    return $stubClass;
}

function providerError(int $status, array $body): RequestException
{
    return new RequestException(new Illuminate\Http\Client\Response(new Response($status, [], json_encode($body))));
}

function historicalServiceMock(): MockInterface
{
    return Mockery::mock(ExchangeRateServiceInterface::class, HistoricalSupportExchangeRateServiceInterface::class);
}

beforeEach(function () {
    Cache::flush();
    $this->rates = collect([new CurrencyExchangeRate(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR', 'exchange_rate' => '0.92'])]);
    $this->historicalRates = collect([new CurrencyExchangeRateHistory(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR', 'exchange_rate' => '0.92', 'date_time' => Carbon::parse('2024-01-01')])]);
});

it('skips a provider for every operation after it reports an exhausted quota', function () {
    $exhausted = historicalServiceMock();
    $exhausted->shouldReceive('storeExchangeRates')->once()->andThrow(providerError(429, ['result' => 'error', 'error-type' => 'quota-reached']));
    $exhausted->shouldNotReceive('storeHistoricalExchangeRates');

    $healthy = historicalServiceMock();
    $healthy->shouldReceive('storeExchangeRates')->twice()->andReturn($this->rates);
    $healthy->shouldReceive('storeHistoricalExchangeRates')->once()->andReturn($this->historicalRates);

    $fallback = new FallbackExchangeRateService;
    $fallback->setFallbackServices([suspensionStubService($exhausted), suspensionStubService($healthy)]);

    expect($fallback->storeExchangeRates('USD'))->toBe($this->rates);
    expect($fallback->storeExchangeRates('USD'))->toBe($this->rates);
    expect($fallback->storeHistoricalExchangeRates('USD', Carbon::parse('2024-01-01')))->toBe($this->historicalRates);
});

it('recognises quota and credential errors by provider error code', function (int $status, array $body) {
    $failing = historicalServiceMock();
    $failing->shouldReceive('storeExchangeRates')->once()->andThrow(providerError($status, $body));

    $healthy = historicalServiceMock();
    $healthy->shouldReceive('storeExchangeRates')->twice()->andReturn($this->rates);

    $fallback = new FallbackExchangeRateService;
    $fallback->setFallbackServices([suspensionStubService($failing), suspensionStubService($healthy)]);

    $fallback->storeExchangeRates('USD');
    $fallback->storeExchangeRates('USD');
})->with([
    'ExchangeRate-API quota' => [200, ['result' => 'error', 'error-type' => 'quota-reached']],
    'ExchangeRate-API invalid key' => [403, ['result' => 'error', 'error-type' => 'invalid-key']],
    'Open Exchange Rates over-use' => [403, ['error' => true, 'status' => 403, 'message' => 'access_restricted']],
    'Open Exchange Rates invalid app id' => [401, ['error' => true, 'status' => 401, 'message' => 'invalid_app_id']],
]);

it('skips a provider only for historical operations after its plan rejects them', function () {
    $latestOnly = historicalServiceMock();
    $latestOnly->shouldReceive('storeHistoricalExchangeRates')->once()->andThrow(providerError(403, ['result' => 'error', 'error-type' => 'plan-upgrade-required']));
    $latestOnly->shouldReceive('storeExchangeRates')->once()->andReturn($this->rates);

    $healthy = historicalServiceMock();
    $healthy->shouldReceive('storeHistoricalExchangeRates')->twice()->andReturn($this->historicalRates);

    $fallback = new FallbackExchangeRateService;
    $fallback->setFallbackServices([suspensionStubService($latestOnly), suspensionStubService($healthy)]);

    $fallback->storeHistoricalExchangeRates('USD', Carbon::parse('2024-01-01'));
    $fallback->storeHistoricalExchangeRates('USD', Carbon::parse('2024-01-02'));

    expect($fallback->storeExchangeRates('USD'))->toBe($this->rates);
});

it('keeps retrying a provider after an ordinary failure', function () {
    $flaky = historicalServiceMock();
    $flaky->shouldReceive('storeExchangeRates')->twice()->andThrow(providerError(500, ['error' => true]));

    $healthy = historicalServiceMock();
    $healthy->shouldReceive('storeExchangeRates')->twice()->andReturn($this->rates);

    $fallback = new FallbackExchangeRateService;
    $fallback->setFallbackServices([suspensionStubService($flaky), suspensionStubService($healthy)]);

    $fallback->storeExchangeRates('USD');
    $fallback->storeExchangeRates('USD');
});

it('does not suspend when the suspension window is 0', function () {
    Config::set('exchange-rates.fallback_suspension.unavailable_seconds', 0);

    $exhausted = historicalServiceMock();
    $exhausted->shouldReceive('storeExchangeRates')->twice()->andThrow(providerError(429, ['error-type' => 'quota-reached']));

    $healthy = historicalServiceMock();
    $healthy->shouldReceive('storeExchangeRates')->twice()->andReturn($this->rates);

    $fallback = new FallbackExchangeRateService;
    $fallback->setFallbackServices([suspensionStubService($exhausted), suspensionStubService($healthy)]);

    $fallback->storeExchangeRates('USD');
    $fallback->storeExchangeRates('USD');
});

it('still reads stored rates through a suspended provider', function () {
    $exhausted = historicalServiceMock();
    $exhausted->shouldReceive('storeExchangeRates')->once()->andThrow(providerError(429, ['error-type' => 'quota-reached']));
    $exhausted->shouldReceive('getExchangeRates')->once()->with('USD')->andReturn($this->rates);

    $fallback = new FallbackExchangeRateService;
    $fallback->setFallbackServices([suspensionStubService($exhausted)]);

    expect(fn () => $fallback->storeExchangeRates('USD'))->toThrow(RuntimeException::class);
    expect($fallback->getExchangeRates('USD'))->toBe($this->rates);
});

it('names suspended providers when every provider is skipped', function () {
    $exhausted = historicalServiceMock();
    $exhausted->shouldReceive('storeExchangeRates')->once()->andThrow(providerError(429, ['error-type' => 'quota-reached']));
    $stub = suspensionStubService($exhausted);

    $fallback = new FallbackExchangeRateService;
    $fallback->setFallbackServices([$stub]);

    expect(fn () => $fallback->storeExchangeRates('USD'))->toThrow(RuntimeException::class);
    expect(fn () => $fallback->storeExchangeRates('USD'))->toThrow(RuntimeException::class, "Skipped (temporarily suspended): {$stub}");
});

it('serves stored historical rates without asking any provider', function () {
    $date = Carbon::parse('2024-01-01');
    app(CurrencyExchangeRateRepositoryInterface::class)->updateExchangeRatesHistory('USD', ['EUR' => 0.92], $date);

    $provider = historicalServiceMock();
    $provider->shouldNotReceive('getHistoricalExchangeRate');
    $provider->shouldNotReceive('getHistoricalExchangeRates');

    $fallback = new FallbackExchangeRateService;
    $fallback->setFallbackServices([suspensionStubService($provider)]);

    expect((string) $fallback->getHistoricalExchangeRate('USD', 'EUR', $date)->exchange_rate)->toBe('0.92');
    expect($fallback->getHistoricalExchangeRates('USD', $date))->toHaveCount(1);
});

it('serves stored historical rates even when every provider is suspended', function () {
    $date = Carbon::parse('2024-01-01');

    $exhausted = historicalServiceMock();
    $exhausted->shouldReceive('storeHistoricalExchangeRates')->once()->andThrow(providerError(429, ['error-type' => 'quota-reached']));
    $exhausted->shouldNotReceive('getHistoricalExchangeRate');

    $fallback = new FallbackExchangeRateService;
    $fallback->setFallbackServices([suspensionStubService($exhausted)]);

    expect(fn () => $fallback->storeHistoricalExchangeRates('USD', $date))->toThrow(RuntimeException::class);

    app(CurrencyExchangeRateRepositoryInterface::class)->updateExchangeRatesHistory('USD', ['EUR' => 0.92], $date);

    expect((string) $fallback->getHistoricalExchangeRate('USD', 'EUR', $date)->exchange_rate)->toBe('0.92');
});

it('falls through to the providers when the historical rate is not stored', function () {
    $date = Carbon::parse('2024-01-01');
    $fetched = $this->historicalRates->first();

    $provider = historicalServiceMock();
    $provider->shouldReceive('getHistoricalExchangeRate')->once()->with('USD', 'EUR', $date)->andReturn($fetched);

    $fallback = new FallbackExchangeRateService;
    $fallback->setFallbackServices([suspensionStubService($provider)]);

    expect($fallback->getHistoricalExchangeRate('USD', 'EUR', $date))->toBe($fetched);
});
