<?php

namespace BrightCreations\ExchangeRates\DTOs;

use Carbon\CarbonInterface;

class HistoricalExchangeRatesDto extends ExchangeRatesDto
{
    public function __construct(
        string $base_currency_code,
        array $exchange_rates,
        protected CarbonInterface $date_time,
    ) {
        parent::__construct($base_currency_code, $exchange_rates);
    }

    public function getDateTime(): CarbonInterface
    {
        return $this->date_time;
    }
}
