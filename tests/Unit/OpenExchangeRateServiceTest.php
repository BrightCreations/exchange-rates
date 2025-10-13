<?php

use BrightCreations\ExchangeRates\Concretes\OpenExchangeRateService;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\Carbon;

beforeEach(function () {
    // Setup repository for each test
    $http = Mockery::mock(\Illuminate\Http\Client\PendingRequest::class, function ($mock) {
        $mock->shouldReceive('baseUrl')->andReturnSelf();
        $mock->shouldReceive('withHeaders')->andReturnSelf();
        $mock->shouldReceive('throw')->andReturnSelf();
        $mock->shouldReceive('get')->andReturnUsing(function ($url) {
            // Match /latest.json?base={currency}
            if (preg_match('#/latest\\.json\\?base=([^&]+)#', $url, $matches)) {
                $currency = $matches[1];
                $json = json_decode(file_get_contents(__DIR__.'/Data/OpenExchangeRate/open_exchange_rate_latest_response.json'), true);
                $json['base'] = $currency;
                $json['timestamp'] = Carbon::now()->timestamp; // ensure timestamp is present

                return new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(200, [], json_encode($json))
                );
            }
            // Match /historical/{date}.json?base={currency}
            if (preg_match('#/historical/(\d{4}-\d{2}-\d{2})\\.json\\?base=([^&]+)#', $url, $matches)) {
                $date = $matches[1];
                $currency = $matches[2];
                $json = json_decode(file_get_contents(__DIR__.'/Data/OpenExchangeRate/open_exchange_rate_history_response.json'), true);
                $json['base'] = $currency;
                $json['timestamp'] = Carbon::parse($date)->timestamp; // ensure timestamp is present

                return new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(200, [], json_encode($json))
                );
            }

            // Default fallback
            return new \Illuminate\Http\Client\Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode([]))
            );
        });
    });
    $this->service = app(OpenExchangeRateService::class, [
        'http' => $http,
    ]);
});

describe('Store and retrieve exchange rates', function () {
    it('should store and retrieve exchange rates', function () {
        /** @var OpenExchangeRateService $service */
        $service = $this->service;
        $exchangeRates = $service->storeExchangeRates('USD');
        $this->assertNotNull($exchangeRates);
        $this->assertNotEmpty($exchangeRates);
        $this->assertInstanceOf(CurrencyExchangeRate::class, $exchangeRates[0]);
        $this->assertEquals('USD', $exchangeRates[0]->base_currency_code);
        $targetCurrencyCodes = $exchangeRates->pluck('target_currency_code')->toArray();
        $dbRates = CurrencyExchangeRate::where('base_currency_code', 'USD')
            ->whereIn('target_currency_code', $targetCurrencyCodes)
            ->get();

        $this->assertCount(count($exchangeRates), $dbRates);

        foreach ($exchangeRates as $rate) {
            $this->assertTrue(
                $dbRates->contains(function ($dbRate) use ($rate) {
                    return $dbRate->target_currency_code === $rate->target_currency_code
                        && (string) $dbRate->exchange_rate === (string) $rate->exchange_rate;
                }),
                "Database does not contain expected rate for {$rate->target_currency_code}"
            );
        }
    });

    it('should store and retrieve bulk exchange rates', function () {
        /** @var OpenExchangeRateService $service */
        $service = $this->service;
        $exchangeRates = $service->storeBulkExchangeRatesForMultipleCurrencies(['USD', 'EUR']);
        $this->assertNotNull($exchangeRates);
        $this->assertNotEmpty($exchangeRates);
        $this->assertCount(2, $exchangeRates);

        // Assert that the output is grouped by the currencies passed in
        $this->assertArrayHasKey('USD', $exchangeRates);
        $this->assertArrayHasKey('EUR', $exchangeRates);

        // Assert that each group is a collection of CurrencyExchangeRate
        $this->assertNotEmpty($exchangeRates['USD']);
        $this->assertNotEmpty($exchangeRates['EUR']);
        $this->assertInstanceOf(CurrencyExchangeRate::class, $exchangeRates['USD'][0]);
        $this->assertInstanceOf(CurrencyExchangeRate::class, $exchangeRates['EUR'][0]);

        // Assert that all base_currency_code in each group matches the key
        foreach (['USD', 'EUR'] as $currency) {
            foreach ($exchangeRates[$currency] as $rate) {
                $this->assertEquals($currency, $rate->base_currency_code);
            }
        }
    });

    it('should store and retrieve exchange rates history', function () {
        /** @var OpenExchangeRateService $service */
        $service = $this->service;
        $exchangeRates = $service->storeHistoricalExchangeRates('USD', Carbon::now()->subDays(1));
        $this->assertNotNull($exchangeRates);
        $this->assertNotEmpty($exchangeRates);
        $this->assertInstanceOf(CurrencyExchangeRateHistory::class, $exchangeRates[0]);
        $this->assertEquals('USD', $exchangeRates[0]->base_currency_code);
    });

    it('should store and retrieve exchange rates history for multiple currencies', function () {
        /** @var OpenExchangeRateService $service */
        $service = $this->service;
        $date = Carbon::now()->subDays(1);
        $dtos = [
            new HistoricalBaseCurrencyDto('USD', $date),
            new HistoricalBaseCurrencyDto('EUR', $date),
        ];
        $exchangeRates = $service->storeBulkHistoricalExchangeRatesForMultipleCurrencies($dtos);
        $this->assertNotNull($exchangeRates);
        $this->assertNotEmpty($exchangeRates);
        $this->assertCount(2, $exchangeRates);
        $this->assertArrayHasKey('USD', $exchangeRates);
        $this->assertArrayHasKey('EUR', $exchangeRates);
        $this->assertInstanceOf(CurrencyExchangeRateHistory::class, $exchangeRates['USD'][$date->format('Y-m-d')][0]);
        $this->assertInstanceOf(CurrencyExchangeRateHistory::class, $exchangeRates['EUR'][$date->format('Y-m-d')][0]);
        $this->assertEquals('USD', $exchangeRates['USD'][$date->format('Y-m-d')][0]->base_currency_code);
        $this->assertEquals('EUR', $exchangeRates['EUR'][$date->format('Y-m-d')][0]->base_currency_code);
    });
});
