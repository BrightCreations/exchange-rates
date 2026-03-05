<?php

use BrightCreations\ExchangeRates\Concretes\ExchangeRateApiService;
use BrightCreations\ExchangeRates\Concretes\FallbackExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\OpenExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('exchange-rates.fallback_order', [
        ExchangeRateApiService::class,
        OpenExchangeRateService::class,
        WorldBankExchangeRateApiService::class,
    ]);
});

test('fallback service uses correct fallback order from config', function () {
    $fallbackService = new FallbackExchangeRateService();

    $services = $fallbackService->getFallbackServices();

    expect($services)->toHaveCount(3);
    expect($services[0])->toBe(ExchangeRateApiService::class);
    expect($services[1])->toBe(OpenExchangeRateService::class);
    expect($services[2])->toBe(WorldBankExchangeRateApiService::class);
});

test('fallback service can update fallback order', function () {
    $fallbackService = new FallbackExchangeRateService();

    $newOrder = [
        WorldBankExchangeRateApiService::class,
        ExchangeRateApiService::class,
    ];

    $fallbackService->setFallbackServices($newOrder);

    expect($fallbackService->getFallbackServices())->toBe($newOrder);
});

test('fallback service initially has no current service', function () {
    $fallbackService = new FallbackExchangeRateService();

    expect($fallbackService->getCurrentService())->toBeNull();
});

test('fallback service delegates getExchangeRates to first successful fallback service', function () {
    $expectedRates = collect([
        new CurrencyExchangeRate(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR', 'exchange_rate' => '0.92']),
    ]);

    // Create a mock service class that implements ExchangeRateServiceInterface
    $mockService = Mockery::mock(ExchangeRateServiceInterface::class);
    $mockService->shouldReceive('getExchangeRates')
        ->with('USD')
        ->once()
        ->andReturn($expectedRates);

    // Use a unique stub class name
    $stubClass = 'StubServiceGetExchangeRates_'.uniqid();
    eval("class {$stubClass} {}");

    // Bind stub class to return our mock
    app()->bind($stubClass, fn () => $mockService);

    $fallbackService = new FallbackExchangeRateService();
    $fallbackService->setFallbackServices([$stubClass]);

    $result = $fallbackService->getExchangeRates('USD');

    expect($result)->toBe($expectedRates);
});

test('fallback service delegates getAllExchangeRates to first successful fallback service', function () {
    $expectedRates = collect([
        new CurrencyExchangeRate(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR', 'exchange_rate' => '0.92']),
        new CurrencyExchangeRate(['base_currency_code' => 'EUR', 'target_currency_code' => 'USD', 'exchange_rate' => '1.08']),
    ]);

    $mockService = Mockery::mock(ExchangeRateServiceInterface::class);
    $mockService->shouldReceive('getAllExchangeRates')
        ->once()
        ->andReturn($expectedRates);

    $stubClass = 'StubServiceGetAllExchangeRates_'.uniqid();
    eval("class {$stubClass} {}");

    app()->bind($stubClass, fn () => $mockService);

    $fallbackService = new FallbackExchangeRateService();
    $fallbackService->setFallbackServices([$stubClass]);

    $result = $fallbackService->getAllExchangeRates();

    expect($result)->toBe($expectedRates);
});

test('fallback service delegates storeExchangeRates to first successful fallback service', function () {
    $expectedRates = collect([
        new CurrencyExchangeRate(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR', 'exchange_rate' => '0.92']),
    ]);

    $mockService = Mockery::mock(ExchangeRateServiceInterface::class);
    $mockService->shouldReceive('storeExchangeRates')
        ->with('USD')
        ->once()
        ->andReturn($expectedRates);

    $stubClass = 'StubServiceStoreExchangeRates_'.uniqid();
    eval("class {$stubClass} {}");

    app()->bind($stubClass, fn () => $mockService);

    $fallbackService = new FallbackExchangeRateService();
    $fallbackService->setFallbackServices([$stubClass]);

    $result = $fallbackService->storeExchangeRates('USD');

    expect($result)->toBe($expectedRates);
});

test('fallback service skips to next service when first returns empty collection', function () {
    $emptyRates = collect();
    $expectedRates = collect([
        new CurrencyExchangeRate(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR', 'exchange_rate' => '0.92']),
    ]);

    $firstMock = Mockery::mock(ExchangeRateServiceInterface::class);
    $firstMock->shouldReceive('getExchangeRates')
        ->with('USD')
        ->once()
        ->andReturn($emptyRates);

    $secondMock = Mockery::mock(ExchangeRateServiceInterface::class);
    $secondMock->shouldReceive('getExchangeRates')
        ->with('USD')
        ->once()
        ->andReturn($expectedRates);

    $firstStub = 'StubServiceFirst_'.uniqid();
    $secondStub = 'StubServiceSecond_'.uniqid();
    eval("class {$firstStub} {}");
    eval("class {$secondStub} {}");

    app()->bind($firstStub, fn () => $firstMock);
    app()->bind($secondStub, fn () => $secondMock);

    $fallbackService = new FallbackExchangeRateService();
    $fallbackService->setFallbackServices([$firstStub, $secondStub]);

    $result = $fallbackService->getExchangeRates('USD');

    expect($result)->toBe($expectedRates);
});

test('fallback service skips to next service when first throws exception', function () {
    $expectedRates = collect([
        new CurrencyExchangeRate(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR', 'exchange_rate' => '0.92']),
    ]);

    $firstMock = Mockery::mock(ExchangeRateServiceInterface::class);
    $firstMock->shouldReceive('getExchangeRates')
        ->with('USD')
        ->once()
        ->andThrow(new \RuntimeException('API unavailable'));

    $secondMock = Mockery::mock(ExchangeRateServiceInterface::class);
    $secondMock->shouldReceive('getExchangeRates')
        ->with('USD')
        ->once()
        ->andReturn($expectedRates);

    $firstStub = 'StubServiceFailFirst_'.uniqid();
    $secondStub = 'StubServiceSucceedSecond_'.uniqid();
    eval("class {$firstStub} {}");
    eval("class {$secondStub} {}");

    app()->bind($firstStub, fn () => $firstMock);
    app()->bind($secondStub, fn () => $secondMock);

    $fallbackService = new FallbackExchangeRateService();
    $fallbackService->setFallbackServices([$firstStub, $secondStub]);

    $result = $fallbackService->getExchangeRates('USD');

    expect($result)->toBe($expectedRates);
});

test('fallback service throws RuntimeException when all services fail', function () {
    $mockService = Mockery::mock(ExchangeRateServiceInterface::class);
    $mockService->shouldReceive('getExchangeRates')
        ->with('USD')
        ->once()
        ->andThrow(new \RuntimeException('All APIs down'));

    $stubClass = 'StubServiceAllFail_'.uniqid();
    eval("class {$stubClass} {}");

    app()->bind($stubClass, fn () => $mockService);

    $fallbackService = new FallbackExchangeRateService();
    $fallbackService->setFallbackServices([$stubClass]);

    expect(fn () => $fallbackService->getExchangeRates('USD'))
        ->toThrow(\RuntimeException::class);
});

test('fallback service sets current service on success', function () {
    $expectedRates = collect([
        new CurrencyExchangeRate(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR', 'exchange_rate' => '0.92']),
    ]);

    $mockService = Mockery::mock(ExchangeRateServiceInterface::class);
    $mockService->shouldReceive('storeExchangeRates')
        ->with('USD')
        ->once()
        ->andReturn($expectedRates);

    $stubClass = 'StubServiceSetCurrent_'.uniqid();
    eval("class {$stubClass} {}");

    app()->bind($stubClass, fn () => $mockService);

    $fallbackService = new FallbackExchangeRateService();
    $fallbackService->setFallbackServices([$stubClass]);

    expect($fallbackService->getCurrentService())->toBeNull();

    $fallbackService->storeExchangeRates('USD');

    expect($fallbackService->getCurrentService())->toBe($mockService);
});

test('fallback service delegates getHistoricalExchangeRates through fallback services', function () {
    $date = Carbon::parse('2023-01-15');
    $expectedRates = collect([
        new CurrencyExchangeRateHistory([
            'base_currency_code' => 'USD',
            'target_currency_code' => 'EUR',
            'exchange_rate' => '0.82',
            'date_time' => $date,
        ]),
    ]);

    $mockService = Mockery::mock(ExchangeRateServiceInterface::class, HistoricalSupportExchangeRateServiceInterface::class);
    $mockService->shouldReceive('getHistoricalExchangeRates')
        ->with('USD', $date)
        ->once()
        ->andReturn($expectedRates);

    $stubClass = 'StubServiceGetHistorical_'.uniqid();
    eval("class {$stubClass} {}");

    app()->bind($stubClass, fn () => $mockService);

    $fallbackService = new FallbackExchangeRateService();
    $fallbackService->setFallbackServices([$stubClass]);

    $result = $fallbackService->getHistoricalExchangeRates('USD', $date);

    expect($result)->toBe($expectedRates);
});

test('fallback service delegates getHistoricalExchangeRate through fallback services', function () {
    $date = Carbon::parse('2023-01-15');
    $expectedRate = new CurrencyExchangeRateHistory([
        'base_currency_code' => 'USD',
        'target_currency_code' => 'EUR',
        'exchange_rate' => '0.82',
        'date_time' => $date,
    ]);

    $mockService = Mockery::mock(ExchangeRateServiceInterface::class, HistoricalSupportExchangeRateServiceInterface::class);
    $mockService->shouldReceive('getHistoricalExchangeRate')
        ->with('USD', 'EUR', $date)
        ->once()
        ->andReturn($expectedRate);

    $stubClass = 'StubServiceGetHistoricalSingle_'.uniqid();
    eval("class {$stubClass} {}");

    app()->bind($stubClass, fn () => $mockService);

    $fallbackService = new FallbackExchangeRateService();
    $fallbackService->setFallbackServices([$stubClass]);

    $result = $fallbackService->getHistoricalExchangeRate('USD', 'EUR', $date);

    expect($result)->toBe($expectedRate);
});

test('fallback service throws exception for historical methods when service does not support historical', function () {
    $date = Carbon::parse('2023-01-15');

    // A mock that implements only ExchangeRateServiceInterface, not HistoricalSupportExchangeRateServiceInterface
    $mockService = Mockery::mock(ExchangeRateServiceInterface::class);

    $stubClass = 'StubServiceNoHistorical_'.uniqid();
    eval("class {$stubClass} {}");

    app()->bind($stubClass, fn () => $mockService);

    $fallbackService = new FallbackExchangeRateService();
    $fallbackService->setFallbackServices([$stubClass]);

    expect(fn () => $fallbackService->getHistoricalExchangeRates('USD', $date))
        ->toThrow(\RuntimeException::class);
});
