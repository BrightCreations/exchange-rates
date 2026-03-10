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
    $response = $this->getJson('/api/exchange-rates/USD?currencies=EUR,GBP');

    $response->assertStatus(200);

    $rates = $response->json('data.rates');
    expect($rates)->toHaveCount(2);

    $targetCurrencies = collect($rates)->pluck('target_currency')->sort()->values()->all();
    expect($targetCurrencies)->toBe(['EUR', 'GBP']);
});

it('filters by a single target currency', function () {
    $response = $this->getJson('/api/exchange-rates/USD?currencies=SAR');

    $response->assertStatus(200);

    $rates = $response->json('data.rates');
    expect($rates)->toHaveCount(1);
    expect($rates[0]['target_currency'])->toBe('SAR');
});

it('normalizes lowercase targets to uppercase', function () {
    $response = $this->getJson('/api/exchange-rates/USD?currencies=eur,gbp');

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
    $response = $this->getJson('/api/exchange-rates/USD?currencies=JPY');

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

// ---------------------------------------------------------------------------
// reversed=true  GET /api/exchange-rates/{currency}?reversed=true
// {currency} is the TARGET; returns source currencies with inverted rates
// ---------------------------------------------------------------------------

describe('reversed mode', function () {
    beforeEach(function () {
        // Seed extra rows so multiple bases point to EUR
        CurrencyExchangeRate::insert([
            [
                'base_currency_code'   => 'GBP',
                'target_currency_code' => 'EUR',
                'exchange_rate'        => '1.1800000000',
                'provider'             => 'test',
                'last_update_date'     => now(),
            ],
            [
                'base_currency_code'   => 'SAR',
                'target_currency_code' => 'EUR',
                'exchange_rate'        => '0.2453000000',
                'provider'             => 'test',
                'last_update_date'     => now(),
            ],
        ]);
    });

    it('returns target_currency and source_currency in the response shape', function () {
        $response = $this->getJson('/api/exchange-rates/EUR?reversed=true');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'target_currency',
                    'rates' => [
                        '*' => ['source_currency', 'rate', 'last_updated'],
                    ],
                ],
            ])
            ->assertJsonPath('data.target_currency', 'EUR');
    });

    it('returns all source currencies that store a rate to the target', function () {
        $response = $this->getJson('/api/exchange-rates/EUR?reversed=true');

        // USD→EUR, GBP→EUR, SAR→EUR are all stored
        expect($response->json('data.rates'))->toHaveCount(3);
    });

    it('normalizes lowercase currency in path when reversed', function () {
        $response = $this->getJson('/api/exchange-rates/eur?reversed=true');

        $response->assertStatus(200)
            ->assertJsonPath('data.target_currency', 'EUR');
    });

    it('returns accurately inverted rates', function () {
        $response = $this->getJson('/api/exchange-rates/EUR?reversed=true');

        $rates = collect($response->json('data.rates'))->keyBy('source_currency');

        // Stored USD→EUR = 0.9200000000 ∴ inverted = 1/0.92 ≈ 1.0869565217
        $usdRate = $rates->get('USD')['rate'];
        expect(bccomp($usdRate, bcdiv('1', '0.9200000000', 10), 10))->toBe(0);

        // Stored GBP→EUR = 1.1800000000 ∴ inverted = 1/1.18 ≈ 0.8474576271
        $gbpRate = $rates->get('GBP')['rate'];
        expect(bccomp($gbpRate, bcdiv('1', '1.1800000000', 10), 10))->toBe(0);
    });

    it('filters results by sources query parameter', function () {
        $response = $this->getJson('/api/exchange-rates/EUR?reversed=true&currencies=USD,GBP');

        $response->assertStatus(200);
        expect($response->json('data.rates'))->toHaveCount(2);

        $sources = collect($response->json('data.rates'))->pluck('source_currency')->sort()->values()->all();
        expect($sources)->toBe(['GBP', 'USD']);
    });

    it('normalizes lowercase sources to uppercase', function () {
        $response = $this->getJson('/api/exchange-rates/EUR?reversed=true&currencies=usd,sar');

        $response->assertStatus(200);
        expect($response->json('data.rates'))->toHaveCount(2);
    });

    it('returns an empty rates list when no data targets that currency', function () {
        $response = $this->getJson('/api/exchange-rates/JPY?reversed=true');

        $response->assertStatus(200)
            ->assertJsonPath('data.target_currency', 'JPY')
            ->assertJsonPath('data.rates', []);
    });

    it('returns empty list when sources filter matches nothing', function () {
        $response = $this->getJson('/api/exchange-rates/EUR?reversed=true&currencies=JPY');

        $response->assertStatus(200)
            ->assertJsonPath('data.rates', []);
    });
});
