<?php

namespace BrightCreations\ExchangeRates\DTOs;

use Carbon\CarbonInterface;

class HistoricalCurrenciesPairDto extends CurrenciesPairDto
{
    public function __construct(
        string $base_currency_code,
        string $target_currency_code,
        protected CarbonInterface $date_time,
    ) {
        parent::__construct($base_currency_code, $target_currency_code);
    }

    public function getDateTime(): CarbonInterface
    {
        return $this->date_time;
    }

    public function __toString(): string
    {
        return $this->base_currency_code . '_' . $this->target_currency_code . '_' . $this->date_time->format('Y-m-d');
    }
}
