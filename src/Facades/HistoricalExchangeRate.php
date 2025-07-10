<?php

namespace BrightCreations\ExchangeRates\Facades;

use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use Illuminate\Support\Facades\Facade;

class HistoricalExchangeRate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return HistoricalSupportExchangeRateServiceInterface::class;
    }
}
