<?php

namespace Brights\ExchangeRates\Contracts;

abstract class BaseExchangeRateService implements ExchangeRateServiceInterface
{

    public function isSupportHistoricalExchangeRate(): bool
    {
        return $this instanceof HistoricalSupportExchangeRateServiceInterface;
    }
}
