<?php

namespace BrightCreations\ExchangeRates\Contracts;

abstract class BaseExchangeRateService implements ExchangeRateServiceInterface
{
    public function isSupportHistoricalExchangeRate(): bool
    {
        return $this instanceof HistoricalSupportExchangeRateServiceInterface;
    }

    /**
     * Get the provider name (short class name) for this service
     */
    protected function getProviderName(): string
    {
        return basename(str_replace('\\', '/', static::class));
    }
}
