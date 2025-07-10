<?php

namespace Tests\Unit;

use BrightCreations\ExchangeRates\Concretes\Repositories\CurrencyExchangeRateRepository;
use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

describe("Update and retrieve exchange rates", function () {
    it("can update exchange rates", function () {
        // Arrange
        $repository = new CurrencyExchangeRateRepository();
        $baseCurrencyCode = 'USD';
        $exchangeRates = [
            'EUR' => 0.83,
            'GBP' => 0.71,
            'JPY' => 108.50,
        ];

        // Act
        $repository->updateExchangeRates($baseCurrencyCode, $exchangeRates);

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

    it("can retrieve exchange rates", function () {
        // Arrange
        $repository = new CurrencyExchangeRateRepository();
        $baseCurrencyCode = 'USD';
        $exchangeRates = [
            'EUR' => 0.83,
            'GBP' => 0.71,
            'JPY' => 108.50,
        ];
        $repository->updateExchangeRates($baseCurrencyCode, $exchangeRates);

        // Act
        $result = $repository->getExchangeRates($baseCurrencyCode);

        // Assert
        $this->assertCount(count($exchangeRates), $result);
        $this->assertEquals($exchangeRates, $result->keyBy('target_currency_code')->map->exchange_rate->toArray());
    });
});

describe("Update and retrieve bulk exchange rates", function () {
    // Arrange
    $repository = new CurrencyExchangeRateRepository();
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
    it("can update bulk exchange rates", function () use ($repository, $dataToBeStored) {
        // Act
        $this->expectException(\InvalidArgumentException::class);
        $repository->updateBulkExchangeRates($dataToBeStored);

        $dtos = [];
        foreach ($dataToBeStored as &$data) {
            $dtos[] = new ExchangeRatesDto($data['base_currency_code'], $data['exchange_rates']);
        }
        $repository->updateBulkExchangeRates($dtos);

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

    it("can retrieve bulk exchange rates", function () use ($repository, $dataToBeStored) {
        // Arrange
        $dtos = [];
        foreach ($dataToBeStored as &$data) {
            $dtos[] = new ExchangeRatesDto($data['base_currency_code'], $data['exchange_rates']);
        }
        $repository->updateBulkExchangeRates($dtos);

        // Act
        $result = $repository->getBulkExchangeRates(['USD', 'EUR']);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals($dataToBeStored[0]['exchange_rates'], $result['USD']->keyBy('target_currency_code')->map->exchange_rate->toArray());
        $this->assertEquals($dataToBeStored[1]['exchange_rates'], $result['EUR']->keyBy('target_currency_code')->map->exchange_rate->toArray());
    });
});

describe("Update and retrieve historical exchange rates", function () {
    it("can update historical exchange rates", function () {
        // Arrange
        $repository = new CurrencyExchangeRateRepository();
        $baseCurrencyCode = 'USD';
        $exchangeRates = [
            'EUR' => 0.83,
            'GBP' => 0.71,
            'JPY' => 108.50,
        ];
        $dateTime = Carbon::parse('2024-01-15 10:30:00');

        // Act
        $repository->updateExchangeRatesHistory($baseCurrencyCode, $exchangeRates, $dateTime);

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

    it("can retrieve historical exchange rates", function () {
        // Arrange
        $repository = new CurrencyExchangeRateRepository();
        $baseCurrencyCode = 'USD';
        $exchangeRates = [
            'EUR' => 0.83,
            'GBP' => 0.71,
            'JPY' => 108.50,
        ];
        $dateTime = Carbon::parse('2024-01-15 10:30:00');
        $repository->updateExchangeRatesHistory($baseCurrencyCode, $exchangeRates, $dateTime);

        // Act
        $result = $repository->getHistoricalExchangeRates($baseCurrencyCode, $dateTime);

        // Assert
        $this->assertCount(count($exchangeRates), $result);
        $this->assertEquals($exchangeRates, $result->keyBy('target_currency_code')->map->exchange_rate->toArray());
    });
});

describe("Update and retrieve bulk historical exchange rates", function () {
    // Arrange
    $repository = new CurrencyExchangeRateRepository();
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
    
    it("can update bulk historical exchange rates", function () use ($repository, $dataToBeStored) {
        // Act
        $this->expectException(\InvalidArgumentException::class);
        $repository->updateBulkExchangeRatesHistory($dataToBeStored);

        $dtos = [];
        foreach ($dataToBeStored as &$data) {
            $dtos[] = new HistoricalExchangeRatesDto($data['base_currency_code'], $data['exchange_rates'], $data['date_time']);
        }
        $repository->updateBulkExchangeRatesHistory($dtos);

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

    it("can retrieve bulk historical exchange rates", function () use ($repository, $dataToBeStored) {
        // Arrange
        $dtos = [];
        foreach ($dataToBeStored as &$data) {
            $dtos[] = new HistoricalExchangeRatesDto($data['base_currency_code'], $data['exchange_rates'], $data['date_time']);
        }
        $repository->updateBulkExchangeRatesHistory($dtos);

        // Act
        $historicalBaseCurrencies = [
            new HistoricalBaseCurrencyDto('USD', $dataToBeStored[0]['date_time']),
            new HistoricalBaseCurrencyDto('EUR', $dataToBeStored[1]['date_time']),
        ];
        $result = $repository->getBulkHistoricalExchangeRates($historicalBaseCurrencies);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals($dataToBeStored[0]['exchange_rates'], $result['USD_2024-01-15']->keyBy('target_currency_code')->map->exchange_rate->toArray());
        $this->assertEquals($dataToBeStored[1]['exchange_rates'], $result['EUR_2024-01-16']->keyBy('target_currency_code')->map->exchange_rate->toArray());
    });
});
