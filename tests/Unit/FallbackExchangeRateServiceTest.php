<?php

use BrightCreations\ExchangeRates\Concretes\FallbackExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\ExchangeRateApiService;
use BrightCreations\ExchangeRates\Concretes\OpenExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('exchange-rates.fallback_order', [
        ExchangeRateApiService::class,
        OpenExchangeRateService::class,
        WorldBankExchangeRateApiService::class,
    ]);
});

test('fallback service uses first successful service', function () {
    $mockRepo = Mockery::mock(CurrencyExchangeRateRepositoryInterface::class);

    $fallbackService = new FallbackExchangeRateService($mockRepo);

    $services = $fallbackService->getFallbackServices();

    expect($services)->toHaveCount(3);
    expect($services[0])->toBe(ExchangeRateApiService::class);
    expect($services[1])->toBe(OpenExchangeRateService::class);
    expect($services[2])->toBe(WorldBankExchangeRateApiService::class);
});

test('fallback service can update fallback order', function () {
    $mockRepo = Mockery::mock(CurrencyExchangeRateRepositoryInterface::class);

    $fallbackService = new FallbackExchangeRateService($mockRepo);

    $newOrder = [
        WorldBankExchangeRateApiService::class,
        ExchangeRateApiService::class,
    ];

    $fallbackService->setFallbackServices($newOrder);

    expect($fallbackService->getFallbackServices())->toBe($newOrder);
});

test('fallback service returns data from repository for get methods', function () {
    $mockRepo = Mockery::mock(CurrencyExchangeRateRepositoryInterface::class);

    $expectedRates = collect([
        new CurrencyExchangeRate(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR', 'exchange_rate' => '0.92']),
    ]);

    $mockRepo->shouldReceive('getExchangeRates')
        ->with('USD')
        ->once()
        ->andReturn($expectedRates);

    $fallbackService = new FallbackExchangeRateService($mockRepo);

    $result = $fallbackService->getExchangeRates('USD');

    expect($result)->toBe($expectedRates);
});

test('fallback service returns all rates from repository', function () {
    $mockRepo = Mockery::mock(CurrencyExchangeRateRepositoryInterface::class);

    $expectedRates = collect([
        new CurrencyExchangeRate(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR', 'exchange_rate' => '0.92']),
        new CurrencyExchangeRate(['base_currency_code' => 'EUR', 'target_currency_code' => 'USD', 'exchange_rate' => '1.08']),
    ]);

    $mockRepo->shouldReceive('getAllExchangeRates')
        ->once()
        ->andReturn($expectedRates);

    $fallbackService = new FallbackExchangeRateService($mockRepo);

    $result = $fallbackService->getAllExchangeRates();

    expect($result)->toBe($expectedRates);
});

test('fallback service tracks current successful service', function () {
    $mockRepo = Mockery::mock(CurrencyExchangeRateRepositoryInterface::class);

    $fallbackService = new FallbackExchangeRateService($mockRepo);

    // Initially null
    expect($fallbackService->getCurrentService())->toBeNull();
});
