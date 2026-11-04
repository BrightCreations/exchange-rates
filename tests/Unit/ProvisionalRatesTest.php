<?php

use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;

const DAILY_PROVIDER = 'OpenExchangeRateService';
const YEARLY_PROVIDER = 'WorldBankExchangeRateApiService';

beforeEach(function () {
    Cache::flush();
    Carbon::setTestNow(Carbon::parse('2026-11-04 12:00:00'));
    $this->repository = app(CurrencyExchangeRateRepositoryInterface::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('Provisional rates', function () {
    it('marks period-average providers as provisional', function () {
        expect((new CurrencyExchangeRateHistory(['provider' => YEARLY_PROVIDER]))->isProvisional())->toBeTrue();
        expect((new CurrencyExchangeRateHistory(['provider' => DAILY_PROVIDER]))->isProvisional())->toBeFalse();
        expect((new CurrencyExchangeRateHistory(['provider' => null]))->isProvisional())->toBeFalse();
    });

    it('follows the provisional_providers config', function () {
        Config::set('exchange-rates.provisional_providers', []);

        expect((new CurrencyExchangeRateHistory(['provider' => YEARLY_PROVIDER]))->isProvisional())->toBeFalse();
    });

    it('keeps the provider on models built from fetched rates', function () {
        $models = CurrencyExchangeRateHistory::constructFromHistoricalExchangeRatesDto(
            new HistoricalExchangeRatesDto('USD', ['EUR' => 0.9], Carbon::parse('2024-01-01')),
            YEARLY_PROVIDER,
        );

        expect($models->first()->provider)->toBe(YEARLY_PROVIDER);
        expect($models->first()->isProvisional())->toBeTrue();
    });
});

describe('Exact lookups', function () {
    it('prefers a daily rate over a provisional one for the same date', function () {
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.80], Carbon::parse('2024-03-10 00:00:00'), YEARLY_PROVIDER);
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.91], Carbon::parse('2024-03-10 12:00:00'), DAILY_PROVIDER);

        $rate = $this->repository->getHistoricalExchangeRate('USD', 'EUR', Carbon::parse('2024-03-10'));

        expect($rate->provider)->toBe(DAILY_PROVIDER);
        expect((string) $rate->exchange_rate)->toBe('0.91');
    });

    it('falls back to a provisional rate when it is the only one for the date', function () {
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.80], Carbon::parse('2024-03-10'), YEARLY_PROVIDER);

        expect($this->repository->getHistoricalExchangeRate('USD', 'EUR', Carbon::parse('2024-03-10'))->provider)->toBe(YEARLY_PROVIDER);
    });

    it('lists one rate per target, daily where available', function () {
        $date = Carbon::parse('2024-03-10');
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.80, 'GBP' => 0.70], $date, YEARLY_PROVIDER);
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.91], $date->copy()->setTime(12, 0), DAILY_PROVIDER);

        $rates = $this->repository->getHistoricalExchangeRates('USD', $date)->keyBy('target_currency_code');

        expect($rates)->toHaveCount(2);
        expect($rates['EUR']->provider)->toBe(DAILY_PROVIDER);
        expect($rates['GBP']->provider)->toBe(YEARLY_PROVIDER);
    });

    it('prefers daily rates in bulk pair lookups', function () {
        $date = Carbon::parse('2024-03-10');
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.80], $date, YEARLY_PROVIDER);
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.91], $date->copy()->setTime(12, 0), DAILY_PROVIDER);

        $rates = $this->repository->getBulkHistoricalExchangeRate([new HistoricalCurrenciesPairDto('USD', 'EUR', $date)]);

        expect($rates->get('USD')->get('EUR')->get('2024-03-10')->first()->provider)->toBe(DAILY_PROVIDER);
    });
});

describe('Interpolation bounds', function () {
    beforeEach(function () {
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.90], Carbon::parse('2024-03-08'), DAILY_PROVIDER);
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.70], Carbon::parse('2024-03-09'), YEARLY_PROVIDER);
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.60], Carbon::parse('2024-03-11'), YEARLY_PROVIDER);
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.92], Carbon::parse('2024-03-12'), DAILY_PROVIDER);
    });

    it('bounds interpolation by daily rates only', function () {
        $bounds = $this->repository->getBoundingHistoricalRates('USD', 'EUR', Carbon::parse('2024-03-10'));

        expect($bounds->map(fn ($rate) => Carbon::parse($rate->date_time)->format('Y-m-d'))->all())->toBe(['2024-03-08', '2024-03-12']);
    });

    it('bounds bulk interpolation by daily rates only', function () {
        $first = new HistoricalCurrenciesPairDto('USD', 'EUR', Carbon::parse('2024-03-10'));
        $second = new HistoricalCurrenciesPairDto('USD', 'EUR', Carbon::parse('2024-03-10 18:00:00'));

        $bounds = $this->repository->getBulkBoundingHistoricalRates([$first, $second]);

        foreach ([$first, $second] as $dto) {
            expect($bounds[(string) $dto]->map(fn ($rate) => Carbon::parse($rate->date_time)->format('Y-m-d'))->all())->toBe(['2024-03-08', '2024-03-12']);
        }
    });

    it('still extrapolates from the nearest rate, provisional or not', function () {
        expect($this->repository->getPreviousHistoricalRate('USD', 'EUR', Carbon::parse('2024-03-10'))->provider)->toBe(YEARLY_PROVIDER);
    });
});

describe('exchange-rates:refresh-provisional', function () {
    function provisionalRefreshStub(MockInterface $mock): string
    {
        $stubClass = 'StubDailyProvider_'.uniqid();
        eval("class {$stubClass} {}");
        app()->bind($stubClass, fn () => $mock);

        return $stubClass;
    }

    it('replaces provisional rates with daily ones stored under the same date_time', function () {
        $date = Carbon::parse('2026-10-20 14:30:00');
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.80, 'GBP' => 0.70], $date, YEARLY_PROVIDER);

        $daily = Mockery::mock(ExchangeRateServiceInterface::class, HistoricalSupportExchangeRateServiceInterface::class);
        $daily->shouldReceive('storeHistoricalExchangeRates')
            ->once()
            ->withArgs(fn ($base, $dateTime) => $base === 'USD' && $dateTime->equalTo($date))
            ->andReturnUsing(function ($base, $dateTime) {
                $this->repository->updateExchangeRatesHistory($base, ['EUR' => 0.91, 'GBP' => 0.79], $dateTime, DAILY_PROVIDER);

                return collect([new CurrencyExchangeRateHistory]);
            });
        Config::set('exchange-rates.fallback_order', [provisionalRefreshStub($daily), WorldBankExchangeRateApiService::class]);

        $this->artisan('exchange-rates:refresh-provisional')->assertSuccessful();

        expect(CurrencyExchangeRateHistory::onlyProvisional()->count())->toBe(0);
        expect((string) $this->repository->getHistoricalExchangeRate('USD', 'EUR', $date)->exchange_rate)->toBe('0.91');
    });

    it('leaves rates provisional when no daily provider has the date yet', function () {
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.80], Carbon::parse('2026-10-20'), YEARLY_PROVIDER);

        $daily = Mockery::mock(ExchangeRateServiceInterface::class, HistoricalSupportExchangeRateServiceInterface::class);
        $daily->shouldReceive('storeHistoricalExchangeRates')->once()->andThrow(new RuntimeException('not available'));
        Config::set('exchange-rates.fallback_order', [provisionalRefreshStub($daily), WorldBankExchangeRateApiService::class]);

        $this->artisan('exchange-rates:refresh-provisional')->assertSuccessful();

        expect(CurrencyExchangeRateHistory::onlyProvisional()->count())->toBe(1);
    });

    it('only looks back --days days', function () {
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.80], Carbon::parse('2026-01-01'), YEARLY_PROVIDER);

        $daily = Mockery::mock(ExchangeRateServiceInterface::class, HistoricalSupportExchangeRateServiceInterface::class);
        $daily->shouldNotReceive('storeHistoricalExchangeRates');
        Config::set('exchange-rates.fallback_order', [provisionalRefreshStub($daily), WorldBankExchangeRateApiService::class]);

        $this->artisan('exchange-rates:refresh-provisional', ['--days' => 30])->assertSuccessful();
    });

    it('never asks the provisional provider itself', function () {
        $this->repository->updateExchangeRatesHistory('USD', ['EUR' => 0.80], Carbon::parse('2026-10-20'), YEARLY_PROVIDER);
        Config::set('exchange-rates.fallback_order', [WorldBankExchangeRateApiService::class]);

        $this->artisan('exchange-rates:refresh-provisional')
            ->expectsOutputToContain('Nothing to refresh')
            ->assertSuccessful();
    });
});
