<?php

namespace BrightCreations\ExchangeRates\DTOs;

class CurrenciesPairDto
{
    public function __construct(
        protected string $base_currency_code,
        protected string $target_currency_code,
    ) {}

    public function getBaseCurrencyCode(): string
    {
        return $this->base_currency_code;
    }

    public function getTargetCurrencyCode(): string
    {
        return $this->target_currency_code;
    }

    public function __toString(): string
    {
        return $this->base_currency_code.'_'.$this->target_currency_code;
    }
}
