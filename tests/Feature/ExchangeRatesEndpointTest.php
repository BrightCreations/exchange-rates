<?php

use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;

beforeEach(function () {
    CurrencyExchangeRate::insert([
        [
            'base_currency_code'   => 'USD',
            'target_currency_code' => 'EUR',
            'exchange_rate'        => '0.9200000000',
            'provider'             => 'test',
            'last_update_date'     => now(),
        ],
        [
            'base_currency_code'   => 'USD',
            'target_currency_code' => 'GBP',
            'exchange_rate'        => '0.7800000000',
            'provider'             => 'test',
            'last_update_date'     => now(),
        ],
        [
            'base_currency_code'   => 'USD',
            'target_currency_code' => 'SAR',
            'exchange_rate'        => '3.7500000000',
            'provider'             => 'test',
            'last_update_date'     => now(),
        ],
    ]);
});

it('registers the package route', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes());

    $found = $routes->first(fn ($route) => $route->getName() === 'exchange-rates.index');

    expect($found)->not->toBeNull();
});

it('returns rates for a base currency', function () {
    $response = $this->getJson('/api/exchange-rates/USD');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'base_currency',
                'rates' => [
                    '*' => ['target_currency', 'rate', 'last_updated'],
                ],
            ],
        ])
        ->assertJsonPath('data.base_currency', 'USD');

    expect($response->json('data.rates'))->toHaveCount(3);
});

it('lowercases the currency in the path and still returns results', function () {
    $response = $this->getJson('/api/exchange-rates/usd');

    $response->assertStatus(200)
        ->assertJsonPath('data.base_currency', 'USD');

    expect($response->json('data.rates'))->toHaveCount(3);
});

it('filters rates by a targets query parameter', function () {
    $response = $this->getJson('/api/exchange-rates/USD?targets=EUR,GBP');

    $response->assertStatus(200);

    $rates = $response->json('data.rates');
    expect($rates)->toHaveCount(2);

    $targetCurrencies = collect($rates)->pluck('target_currency')->sort()->values()->all();
    expect($targetCurrencies)->toBe(['EUR', 'GBP']);
});

it('filters by a single target currency', function () {
    $response = $this->getJson('/api/exchange-rates/USD?targets=SAR');

    $response->assertStatus(200);

    $rates = $response->json('data.rates');
    expect($rates)->toHaveCount(1);
    expect($rates[0]['target_currency'])->toBe('SAR');
});

it('normalizes lowercase targets to uppercase', function () {
    $response = $this->getJson('/api/exchange-rates/USD?targets=eur,gbp');

    $response->assertStatus(200);

    $rates = $response->json('data.rates');
    expect($rates)->toHaveCount(2);
});

it('returns an empty rates list when no data is stored for the currency', function () {
    $response = $this->getJson('/api/exchange-rates/JPY');

    $response->assertStatus(200)
        ->assertJsonPath('data.base_currency', 'JPY')
        ->assertJsonPath('data.rates', []);
});

it('returns an empty list when the targets filter matches nothing', function () {
    $response = $this->getJson('/api/exchange-rates/USD?targets=JPY');

    $response->assertStatus(200)
        ->assertJsonPath('data.rates', []);
});

it('rejects a currency code shorter than three characters', function () {
    $response = $this->getJson('/api/exchange-rates/US');

    $response->assertStatus(422);
});

it('rejects a currency code longer than three characters', function () {
    $response = $this->getJson('/api/exchange-rates/USDD');

    $response->assertStatus(422);
});

it('rejects a currency code containing non-letter characters', function () {
    $response = $this->getJson('/api/exchange-rates/U1D');

    $response->assertStatus(422);
});
