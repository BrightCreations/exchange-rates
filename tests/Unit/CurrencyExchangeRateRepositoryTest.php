<?php

namespace Tests\Unit;

use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use Carbon\Carbon;

beforeEach(function () {
    // Setup repository for each test
    $this->repository = app(\BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface::class);
});

describe('Update and retrieve exchange rates', function () {
    it('can update exchange rates', function () {
        // Arrange
        $baseCurrencyCode = 'USD';
        $exchangeRates = [
            'EUR' => 0.83,
            'GBP' => 0.71,
            'JPY' => 108.50,
        ];

        // Act
        $this->repository->updateExchangeRates($baseCurrencyCode, $exchangeRates);

        // Assert - Check that data exists in the database
        $this->assertDatabaseHas('currency_exchange_rates', [
            'base_currency_code' => $baseCurrencyCode,
            'target_currency_code' => 'EUR',
            'exchange_rate' => '0.83',
        ]);
        $this->assertDatabaseHas('currency_exchange_rates', [
            'base_currency_code' => $baseCurrencyCode,
            'target_currency_code' => 'GBP',
            'exchange_rate' => '0.71',
        ]);
        $this->assertDatabaseHas('currency_exchange_rates', [
            'base_currency_code' => $baseCurrencyCode,
            'target_currency_code' => 'JPY',
            'exchange_rate' => '108.5',
        ]);
    });

    it('can retrieve exchange rates', function () {
        // Arrange
        $baseCurrencyCode = 'USD';
        $exchangeRates = [
            'EUR' => 0.83,
            'GBP' => 0.71,
            'JPY' => 108.50,
        ];
        $this->repository->updateExchangeRates($baseCurrencyCode, $exchangeRates);

        // Act
        $result = $this->repository->getExchangeRates($baseCurrencyCode);

        // Assert
        $this->assertCount(count($exchangeRates), $result);
        $this->assertEquals($exchangeRates, $result->keyBy('target_currency_code')->map->exchange_rate->toArray());
    });
});

describe('Update and retrieve bulk exchange rates', function () {
    // Arrange
    $dataToBeStored = [
        [
            'base_currency_code' => 'USD',
            'exchange_rates' => [
                'EUR' => 0.83,
                'GBP' => 0.71,
                'JPY' => 108.50,
            ],
        ],
        [
            'base_currency_code' => 'EUR',
            'exchange_rates' => [
                'USD' => 1.15,
                'GBP' => 0.85,
                'JPY' => 125.00,
            ],
        ],
    ];
    it('can update bulk exchange rates', function () use ($dataToBeStored) {
        // Act
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->updateBulkExchangeRates($dataToBeStored);

        $dtos = [];
        foreach ($dataToBeStored as &$data) {
            $dtos[] = new ExchangeRatesDto($data['base_currency_code'], $data['exchange_rates']);
        }
        $this->repository->updateBulkExchangeRates($dtos);

        // Assert
        $this->assertDatabaseHas('currency_exchange_rates', [
            'base_currency_code' => 'USD',
            'target_currency_code' => 'EUR',
            'exchange_rate' => 0.83,
        ]);
        $this->assertDatabaseHas('currency_exchange_rates', [
            'base_currency_code' => 'USD',
            'target_currency_code' => 'GBP',
            'exchange_rate' => 0.71,
        ]);
        $this->assertDatabaseHas('currency_exchange_rates', [
            'base_currency_code' => 'USD',
            'target_currency_code' => 'JPY',
            'exchange_rate' => 108.50,
        ]);
        $this->assertDatabaseHas('currency_exchange_rates', [
            'base_currency_code' => 'EUR',
            'target_currency_code' => 'USD',
            'exchange_rate' => 1.15,
        ]);
        $this->assertDatabaseHas('currency_exchange_rates', [
            'base_currency_code' => 'EUR',
            'target_currency_code' => 'GBP',
            'exchange_rate' => 0.85,
        ]);
        $this->assertDatabaseHas('currency_exchange_rates', [
            'base_currency_code' => 'EUR',
            'target_currency_code' => 'JPY',
            'exchange_rate' => 125.00,
        ]);
    });

    it('can retrieve bulk exchange rates', function () use ($dataToBeStored) {
        // Arrange
        $dtos = [];
        foreach ($dataToBeStored as &$data) {
            $dtos[] = new ExchangeRatesDto($data['base_currency_code'], $data['exchange_rates']);
        }
        $this->repository->updateBulkExchangeRates($dtos);

        // Act
        $result = $this->repository->getBulkExchangeRates(['USD', 'EUR']);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals($dataToBeStored[0]['exchange_rates'], $result['USD']->keyBy('target_currency_code')->map->exchange_rate->toArray());
        $this->assertEquals($dataToBeStored[1]['exchange_rates'], $result['EUR']->keyBy('target_currency_code')->map->exchange_rate->toArray());
    });
});

describe('Update and retrieve historical exchange rates', function () {
    it('can update historical exchange rates', function () {
        // Arrange
        $baseCurrencyCode = 'USD';
        $exchangeRates = [
            'EUR' => 0.83,
            'GBP' => 0.71,
            'JPY' => 108.50,
        ];
        $dateTime = Carbon::parse('2024-01-15 10:30:00');

        // Act
        $this->repository->updateExchangeRatesHistory($baseCurrencyCode, $exchangeRates, $dateTime);

        // Assert - Check that data exists in the database
        $this->assertDatabaseHas('currency_exchange_rates_history', [
            'base_currency_code' => $baseCurrencyCode,
            'target_currency_code' => 'EUR',
            'exchange_rate' => '0.83',
            'date_time' => $dateTime->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseHas('currency_exchange_rates_history', [
            'base_currency_code' => $baseCurrencyCode,
            'target_currency_code' => 'GBP',
            'exchange_rate' => '0.71',
            'date_time' => $dateTime->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseHas('currency_exchange_rates_history', [
            'base_currency_code' => $baseCurrencyCode,
            'target_currency_code' => 'JPY',
            'exchange_rate' => '108.5',
            'date_time' => $dateTime->format('Y-m-d H:i:s'),
        ]);
    });

    it('can retrieve historical exchange rates', function () {
        // Arrange
        $baseCurrencyCode = 'USD';
        $exchangeRates = [
            'EUR' => 0.83,
            'GBP' => 0.71,
            'JPY' => 108.50,
        ];
        $dateTime = Carbon::parse('2024-01-15 10:30:00');
        $this->repository->updateExchangeRatesHistory($baseCurrencyCode, $exchangeRates, $dateTime);

        // Act
        $result = $this->repository->getHistoricalExchangeRates($baseCurrencyCode, $dateTime);

        // Assert
        $this->assertCount(count($exchangeRates), $result);
        $this->assertEquals($exchangeRates, $result->keyBy('target_currency_code')->map->exchange_rate->toArray());
    });
});

describe('Update and retrieve bulk historical exchange rates', function () {
    // Arrange
    $dataToBeStored = [
        [
            'base_currency_code' => 'USD',
            'exchange_rates' => [
                'EUR' => 0.83,
                'GBP' => 0.71,
                'JPY' => 108.50,
            ],
            'date_time' => Carbon::parse('2024-01-15 10:30:00'),
        ],
        [
            'base_currency_code' => 'EUR',
            'exchange_rates' => [
                'USD' => 1.15,
                'GBP' => 0.85,
                'JPY' => 125.00,
            ],
            'date_time' => Carbon::parse('2024-01-16 14:45:00'),
        ],
    ];

    it('can update bulk historical exchange rates', function () use ($dataToBeStored) {
        // Act
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->updateBulkExchangeRatesHistory($dataToBeStored);

        $dtos = [];
        foreach ($dataToBeStored as &$data) {
            $dtos[] = new HistoricalExchangeRatesDto($data['base_currency_code'], $data['exchange_rates'], $data['date_time']);
        }
        $this->repository->updateBulkExchangeRatesHistory($dtos);

        // Assert
        $this->assertDatabaseHas('currency_exchange_rates_history', [
            'base_currency_code' => 'USD',
            'target_currency_code' => 'EUR',
            'exchange_rate' => 0.83,
            'date_time' => $dataToBeStored[0]['date_time']->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseHas('currency_exchange_rates_history', [
            'base_currency_code' => 'USD',
            'target_currency_code' => 'GBP',
            'exchange_rate' => 0.71,
            'date_time' => $dataToBeStored[0]['date_time']->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseHas('currency_exchange_rates_history', [
            'base_currency_code' => 'USD',
            'target_currency_code' => 'JPY',
            'exchange_rate' => 108.50,
            'date_time' => $dataToBeStored[0]['date_time']->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseHas('currency_exchange_rates_history', [
            'base_currency_code' => 'EUR',
            'target_currency_code' => 'USD',
            'exchange_rate' => 1.15,
            'date_time' => $dataToBeStored[1]['date_time']->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseHas('currency_exchange_rates_history', [
            'base_currency_code' => 'EUR',
            'target_currency_code' => 'GBP',
            'exchange_rate' => 0.85,
            'date_time' => $dataToBeStored[1]['date_time']->format('Y-m-d H:i:s'),
        ]);
        $this->assertDatabaseHas('currency_exchange_rates_history', [
            'base_currency_code' => 'EUR',
            'target_currency_code' => 'JPY',
            'exchange_rate' => 125.00,
            'date_time' => $dataToBeStored[1]['date_time']->format('Y-m-d H:i:s'),
        ]);
    });

    it('can retrieve bulk historical exchange rates', function () use ($dataToBeStored) {
        // Arrange
        $dtos = [];
        foreach ($dataToBeStored as &$data) {
            $dtos[] = new HistoricalExchangeRatesDto($data['base_currency_code'], $data['exchange_rates'], $data['date_time']);
        }
        $this->repository->updateBulkExchangeRatesHistory($dtos);

        // Act
        $historicalBaseCurrencies = [
            new HistoricalBaseCurrencyDto('USD', $dataToBeStored[0]['date_time']),
            new HistoricalBaseCurrencyDto('EUR', $dataToBeStored[1]['date_time']),
        ];
        $result = $this->repository->getBulkHistoricalExchangeRates($historicalBaseCurrencies);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals($dataToBeStored[0]['exchange_rates'], $result['USD_2024-01-15']->keyBy('target_currency_code')->map->exchange_rate->toArray());
        $this->assertEquals($dataToBeStored[1]['exchange_rates'], $result['EUR_2024-01-16']->keyBy('target_currency_code')->map->exchange_rate->toArray());
    });
});

describe('Retrieve single exchange rate for a pair and bulk exchange rates for multiple pairs', function () {
    it('can retrieve a single exchange rate', function () {
        // Arrange
        $baseCurrency = 'USD';
        $exchangeRates = [
            'EUR' => 0.85,
            'JPY' => 110.0,
        ];
        $this->repository->updateExchangeRates($baseCurrency, $exchangeRates);

        // Act
        $result = $this->repository->getExchangeRate('USD', 'EUR');

        // Assert
        $this->assertEquals('USD', $result->base_currency_code);
        $this->assertEquals('EUR', $result->target_currency_code);
        $this->assertEquals(0.85, $result->exchange_rate);
    });

    it('can retrieve bulk exchange rates by currencies pair', function () {
        // Arrange
        $this->repository->updateExchangeRates('USD', [
            'EUR' => 0.85,
            'JPY' => 110.0,
        ]);
        $this->repository->updateExchangeRates('EUR', [
            'USD' => 1.15,
            'JPY' => 129.0,
        ]);

        $pairs = [
            new \BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto('USD', 'EUR'),
            new \BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto('USD', 'JPY'),
            new \BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto('EUR', 'USD'),
            new \BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto('EUR', 'JPY'),
        ];

        // Act
        $result = $this->repository->getBulkExchangeRate($pairs);

        // Assert
        $this->assertCount(4, $result);
        $this->assertEquals(0.85, $result['USD_EUR'][0]->exchange_rate);
        $this->assertEquals(110.0, $result['USD_JPY'][0]->exchange_rate);
        $this->assertEquals(1.15, $result['EUR_USD'][0]->exchange_rate);
        $this->assertEquals(129.0, $result['EUR_JPY'][0]->exchange_rate);
    });
});

describe('Retrieve single historical exchange rate for a pair and bulk historical exchange rates for multiple pairs', function () {
    it('can retrieve a single historical exchange rate', function () {
        // Arrange
        $baseCurrency = 'USD';
        $targetCurrency = 'EUR';
        $exchangeRate = 0.82;
        $date = Carbon::parse('2023-01-01');

        $this->repository->updateExchangeRatesHistory($baseCurrency, [
            $targetCurrency => $exchangeRate,
        ], $date);

        // Act
        $result = $this->repository->getHistoricalExchangeRate($baseCurrency, $targetCurrency, $date);

        // Assert
        $this->assertEquals($baseCurrency, $result->base_currency_code);
        $this->assertEquals($targetCurrency, $result->target_currency_code);
        $this->assertEquals($exchangeRate, $result->exchange_rate);
        $this->assertEquals($date->format('Y-m-d'), Carbon::parse($result->date_time)->format('Y-m-d'));
    });

    it('can retrieve bulk historical exchange rates by currencies pair', function () {
        // Arrange
        $date1 = Carbon::parse('2023-01-01');
        $date2 = Carbon::parse('2023-01-02');

        $this->repository->updateExchangeRatesHistory('USD', [
            'EUR' => 0.82,
            'JPY' => 109.0,
        ], $date1);

        $this->repository->updateExchangeRatesHistory('EUR', [
            'USD' => 1.18,
            'JPY' => 130.0,
        ], $date2);

        $pairs = [
            new \BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto('USD', 'EUR', $date1),
            new \BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto('USD', 'JPY', $date1),
            new \BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto('EUR', 'USD', $date2),
            new \BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto('EUR', 'JPY', $date2),
        ];

        // Act
        $result = $this->repository->getBulkHistoricalExchangeRate($pairs);

        // Assert
        $this->assertCount(4, $result);
        $this->assertEquals(0.82, $result['USD_EUR_2023-01-01'][0]->exchange_rate);
        $this->assertEquals(109.0, $result['USD_JPY_2023-01-01'][0]->exchange_rate);
        $this->assertEquals(1.18, $result['EUR_USD_2023-01-02'][0]->exchange_rate);
        $this->assertEquals(130.0, $result['EUR_JPY_2023-01-02'][0]->exchange_rate);
    });
});
