<?php

namespace BrightCreations\ExchangeRates\Tests\Concerns;

use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use Carbon\CarbonInterface;

trait SeedsExchangeRateTables
{
    protected function repository(): CurrencyExchangeRateRepositoryInterface
    {
        return app(CurrencyExchangeRateRepositoryInterface::class);
    }

    protected function seedCurrentRate(string $base, string $target, float|string $rate): void
    {
        $this->repository()->updateExchangeRates($base, [$target => $rate]);
    }

    protected function seedHistoricalRate(string $base, string $target, float|string $rate, CarbonInterface $date): void
    {
        $this->repository()->updateExchangeRatesHistory($base, [$target => $rate], $date);
    }

    protected function seedProxyRatesForDate(CarbonInterface $date, string $proxy = 'USD'): void
    {
        $this->seedHistoricalRate($proxy, 'EUR', 0.85, $date);
        $this->seedHistoricalRate($proxy, 'GBP', 0.75, $date);
    }
}
