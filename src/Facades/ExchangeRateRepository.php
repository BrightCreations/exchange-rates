<?php

namespace BrightCreations\ExchangeRates\Facades;

use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use Illuminate\Support\Facades\Facade;

class ExchangeRateRepository extends Facade
{
    protected static function getFacadeAccessor()
    {
        return CurrencyExchangeRateRepositoryInterface::class;
    }
}
