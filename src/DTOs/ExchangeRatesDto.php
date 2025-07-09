<?php

namespace BrightCreations\ExchangeRates\DTOs;

class ExchangeRatesDto
{
    public function __construct(
        protected string $base_currency_code,
        protected array $exchange_rates,
    ) {}

    public function getBaseCurrencyCode(): string
    {
        return $this->base_currency_code;
    }

    public function getExchangeRates(): array
    {
        return $this->exchange_rates;
    }
}
