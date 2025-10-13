<?php

use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;
use BrightCreations\ExchangeRates\Concretes\Helpers\WorldBankExchangeRateHelper;
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Clear cache before each test
    Cache::flush();
});

test('mapCountryToCurrency maps ARE to AED correctly', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testMapCountryToCurrency(string $iso3Code): ?string
        {
            return $this->mapCountryToCurrency($iso3Code);
        }
    };

    $result = $helper->testMapCountryToCurrency('ARE');
    expect($result)->toBe('AED');
});

test('mapCountryToCurrency maps GBR to GBP correctly', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testMapCountryToCurrency(string $iso3Code): ?string
        {
            return $this->mapCountryToCurrency($iso3Code);
        }
    };

    $result = $helper->testMapCountryToCurrency('GBR');
    expect($result)->toBe('GBP');
});

test('mapCountryToCurrency maps EMU to EUR via override', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testMapCountryToCurrency(string $iso3Code): ?string
        {
            return $this->mapCountryToCurrency($iso3Code);
        }
    };

    $result = $helper->testMapCountryToCurrency('EMU');
    expect($result)->toBe('EUR');
});

test('mapCountryToCurrency maps USD territories correctly', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testMapCountryToCurrency(string $iso3Code): ?string
        {
            return $this->mapCountryToCurrency($iso3Code);
        }
    };

    expect($helper->testMapCountryToCurrency('TCA'))->toBe('USD');
    expect($helper->testMapCountryToCurrency('VIR'))->toBe('USD');
    expect($helper->testMapCountryToCurrency('TLS'))->toBe('USD');
});

test('isAggregateRegion filters out aggregate codes', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testIsAggregateRegion(string $iso3Code): bool
        {
            return $this->isAggregateRegion($iso3Code);
        }
    };

    expect($helper->testIsAggregateRegion('AFE'))->toBeTrue();
    expect($helper->testIsAggregateRegion('ARB'))->toBeTrue();
    expect($helper->testIsAggregateRegion('EUU'))->toBeTrue();
    expect($helper->testIsAggregateRegion('GBR'))->toBeFalse();
    expect($helper->testIsAggregateRegion('USA'))->toBeFalse();
});

test('parseWorldBankResponseToUsdRates filters nulls and aggregates', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testParseWorldBankResponseToUsdRates(array $response): array
        {
            return $this->parseWorldBankResponseToUsdRates($response);
        }
    };

    $mockResponse = [
        ['page' => 1, 'pages' => 1, 'total' => 5],
        [
            ['countryiso3code' => 'AFE', 'value' => null], // Aggregate, null - should be filtered
            ['countryiso3code' => 'GBR', 'value' => 0.782414], // Valid
            ['countryiso3code' => 'USA', 'value' => 1.0], // Valid
            ['countryiso3code' => 'ARB', 'value' => 5.5], // Aggregate - should be filtered
            ['countryiso3code' => 'EMU', 'value' => 0.923889], // EUR via override
        ]
    ];

    $result = $helper->testParseWorldBankResponseToUsdRates($mockResponse);

    expect($result)->toHaveKey('GBP');
    expect($result)->toHaveKey('USD');
    expect($result)->toHaveKey('EUR'); // From EMU
    expect($result)->not->toHaveKey('AFE');
    expect($result)->not->toHaveKey('ARB');
    expect($result['USD'])->toBe(1.0);
});

test('computeCrossCurrencyRates computes correct cross rates', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testComputeCrossCurrencyRates(string $base, array $usdRates): array
        {
            return $this->computeCrossCurrencyRates($base, $usdRates);
        }
    };

    $usdRates = [
        'USD' => 1.0,
        'EUR' => 0.92,
        'GBP' => 0.78,
        'JPY' => 150.0,
    ];

    // Test EUR as base
    $eurRates = $helper->testComputeCrossCurrencyRates('EUR', $usdRates);

    expect($eurRates['EUR'])->toBe(1.0);
    expect($eurRates['USD'])->toBe(1.0 / 0.92); // ~1.087
    expect($eurRates['GBP'])->toBe(0.78 / 0.92); // ~0.848
    expect($eurRates['JPY'])->toBe(150.0 / 0.92); // ~163.04
});

test('currency aggregation prefers priority countries', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testAggregateCurrencyRate(string $currency, float $existing, float $new, string $newIso3): float
        {
            return $this->aggregateCurrencyRate($currency, $existing, $new, $newIso3);
        }
    };

    // EUR should prefer EMU data
    $result = $helper->testAggregateCurrencyRate('EUR', 0.95, 0.923889, 'EMU');
    expect($result)->toBe(0.923889); // Should use EMU rate

    // GBP should prefer GBR data
    $result = $helper->testAggregateCurrencyRate('GBP', 0.80, 0.782414, 'GBR');
    expect($result)->toBe(0.782414); // Should use GBR rate

    // Non-priority should keep existing
    $result = $helper->testAggregateCurrencyRate('EUR', 0.95, 0.92, 'EGY');
    expect($result)->toBe(0.95); // Should keep existing (first wins)
});

test('extractExchangeRatesForCurrency returns correct rates', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testExtractExchangeRatesForCurrency(string $currency, array $response): array
        {
            return $this->extractExchangeRatesForCurrency($currency, $response);
        }
    };

    $mockResponse = [
        ['page' => 1, 'pages' => 1],
        [
            ['countryiso3code' => 'GBR', 'value' => 0.782414],
            ['countryiso3code' => 'USA', 'value' => 1.0],
            ['countryiso3code' => 'EMU', 'value' => 0.923889],
            ['countryiso3code' => 'JPN', 'value' => 150.0],
        ]
    ];

    $rates = $helper->testExtractExchangeRatesForCurrency('EUR', $mockResponse);

    expect($rates)->toHaveKey('EUR');
    expect($rates)->toHaveKey('GBP');
    expect($rates)->toHaveKey('USD');
    expect($rates)->toHaveKey('JPY');
    expect($rates['EUR'])->toBe(1.0); // Base currency
});

test('extractExchangeRatesForMultipleCurrencies is efficient', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testExtractExchangeRatesForMultipleCurrencies(array $currencies, array $response): array
        {
            return $this->extractExchangeRatesForMultipleCurrencies($currencies, $response);
        }
    };

    $mockResponse = [
        ['page' => 1, 'pages' => 1],
        [
            ['countryiso3code' => 'GBR', 'value' => 0.782414],
            ['countryiso3code' => 'USA', 'value' => 1.0],
            ['countryiso3code' => 'EMU', 'value' => 0.923889],
        ]
    ];

    $multiRates = $helper->testExtractExchangeRatesForMultipleCurrencies(['EUR', 'GBP', 'USD'], $mockResponse);

    expect($multiRates)->toHaveKey('EUR');
    expect($multiRates)->toHaveKey('GBP');
    expect($multiRates)->toHaveKey('USD');
    expect($multiRates['EUR'])->toHaveKey('EUR');
    expect($multiRates['EUR'])->toHaveKey('GBP');
    expect($multiRates['EUR'])->toHaveKey('USD');
});

test('getAvailableCurrencies returns all mapped currencies', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testGetAvailableCurrencies(array $response): array
        {
            return $this->getAvailableCurrencies($response);
        }
    };

    $mockResponse = [
        ['page' => 1, 'pages' => 1],
        [
            ['countryiso3code' => 'GBR', 'value' => 0.782414],
            ['countryiso3code' => 'USA', 'value' => 1.0],
            ['countryiso3code' => 'EMU', 'value' => 0.923889],
            ['countryiso3code' => 'AFE', 'value' => null], // Filtered
        ]
    ];

    $currencies = $helper->testGetAvailableCurrencies($mockResponse);

    expect($currencies)->toContain('GBP');
    expect($currencies)->toContain('USD');
    expect($currencies)->toContain('EUR');
    expect($currencies)->not->toContain('AFE');
});

test('addCurrencyOverride allows custom mappings', function () {
    $helper = new class {
        use WorldBankExchangeRateHelper;

        public function testMapCountryToCurrency(string $iso3Code): ?string
        {
            return $this->mapCountryToCurrency($iso3Code);
        }
    };

    // Add custom override
    $helper->addCurrencyOverride('XXX', 'XYZ');

    $result = $helper->testMapCountryToCurrency('XXX');
    expect($result)->toBe('XYZ');
});
