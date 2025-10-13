<?php

namespace BrightCreations\ExchangeRates\DTOs;

use Carbon\CarbonInterface;

class HistoricalBaseCurrencyDto
{
    public function __construct(
        protected string $base_currency_code,
        protected CarbonInterface $date_time,
    ) {}

    public function getBaseCurrencyCode(): string
    {
        return $this->base_currency_code;
    }

    public function getDateTime(): CarbonInterface
    {
        return $this->date_time;
    }

    public function __toString(): string
    {
        return $this->base_currency_code.'_'.$this->date_time->format('Y-m-d');
    }
}
