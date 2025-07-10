<?php

namespace BrightCreations\ExchangeRates\Facades;

use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use Illuminate\Support\Facades\Facade;

class ExchangeRate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ExchangeRateServiceInterface::class;
    }
}
