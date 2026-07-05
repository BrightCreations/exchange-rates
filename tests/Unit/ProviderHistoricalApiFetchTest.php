<?php

use BrightCreations\ExchangeRates\Concretes\ExchangeRateApiService;
use BrightCreations\ExchangeRates\Concretes\FallbackExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\OpenExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;

test('open exchange rate service supports historical api fetch', function () {
    expect(app(OpenExchangeRateService::class)->supportsHistoricalApiFetch())->toBeTrue();
});

test('exchange rate api service supports historical api fetch', function () {
    expect(app(ExchangeRateApiService::class)->supportsHistoricalApiFetch())->toBeTrue();
});

test('world bank service does not support historical api fetch', function () {
    expect(app(WorldBankExchangeRateApiService::class)->supportsHistoricalApiFetch())->toBeFalse();
});

test('fallback service reports api fetch when any provider supports it', function () {
    $fallback = new FallbackExchangeRateService;

    expect($fallback->supportsHistoricalApiFetch())->toBeTrue();
});

test('fallback service delegates historical api fetch to current service', function () {
    $fallback = new FallbackExchangeRateService;
    $fallback->setFallbackServices([WorldBankExchangeRateApiService::class]);

    expect($fallback->supportsHistoricalApiFetch())->toBeFalse();
});
