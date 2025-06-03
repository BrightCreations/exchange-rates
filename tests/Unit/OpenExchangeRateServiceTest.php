<?php

use BrightCreations\ExchangeRates\Concretes\OpenExchangeRateService;
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\Client\PendingRequest;

test('storeExchangeRates fetches and stores exchange rates', function () {
    // Arrange
    $currencyCode = 'USD';

    // Mock the repository
    $repository = Mockery::mock(CurrencyExchangeRateRepositoryInterface::class);
    $repository->shouldReceive('updateExchangeRates');
    $repository->shouldReceive('updateExchangeRatesHistory');

    // Create the service instance
    $service = new OpenExchangeRateService(
        new PendingRequest(),
        $repository
    );

    // Act
    $result = $service->storeExchangeRates($currencyCode);

    // Assert
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->count() != 0)->toBeTrue();
    expect($result[0])->toBeInstanceOf(CurrencyExchangeRate::class);
    expect($result[0]->base_currency_code)->toBe('USD');
});

test('getHistoricalExchangeRates returns stored rates or fetches new ones', function () {
    // Arrange
    $currencyCode = 'USD';
    $dateTime = Carbon::create(2023, 10, 12);

    // Mock the repository to return an empty collection initially
    $repository = Mockery::mock(CurrencyExchangeRateRepositoryInterface::class);
    $repository->shouldReceive('getHistoricalExchangeRates')
        ->once()
        ->andReturn(collect([]));

    // Mock the repository's update method
    $repository->shouldReceive('updateExchangeRatesHistory');

    // Create the service instance
    $service = new OpenExchangeRateService(
        new PendingRequest(),
        $repository
    );

    // Act
    $result = $service->getHistoricalExchangeRates($currencyCode, $dateTime);

    // Assert
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->count() != 0)->toBeTrue();
    expect($result[0])->toBeInstanceOf(CurrencyExchangeRateHistory::class);
});

test('getHistoricalExchangeRates returns existing rates from repository', function () {
    // Arrange
    $currencyCode = 'USD';
    $dateTime = Carbon::create(2023, 10, 12);
    $existingRates = collect([
        new CurrencyExchangeRateHistory([
            'base_currency_code' => 'USD',
            'target_currency_code' => 'EUR',
            'exchange_rate' => 0.83,
            'date_time' => $dateTime,
            'last_update_date' => Carbon::now(),
        ]),
    ]);

    // Mock the repository to return existing rates
    $repository = Mockery::mock(CurrencyExchangeRateRepositoryInterface::class);
    $repository->shouldReceive('getHistoricalExchangeRates')
        ->once()
        ->with($currencyCode, $dateTime)
        ->andReturn($existingRates);

    // Create the service instance
    $service = new OpenExchangeRateService(
        new PendingRequest(),
        $repository
    );

    // Act
    $result = $service->getHistoricalExchangeRates($currencyCode, $dateTime);

    // Assert
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(1);
    expect($result[0]->base_currency_code)->toBe('USD');
    expect($result[0]->target_currency_code)->toBe('EUR');
    expect($result[0]->exchange_rate)->toBe(0.83);
});