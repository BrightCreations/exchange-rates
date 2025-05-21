<?php

use Brights\ExchangeRates\Concretes\ExchangeRateApiService;
use Brights\ExchangeRates\Enums\ExchangeRateProvidersEnum;

return [

    // PDO configuartions
    'table' => env('EXCHANGE_RATE_DB_TABLE', 'currency_exchange_rates'),
    'column' => env('EXCHANGE_RATE_DB_COLUMN', 'exchange_rate'),
    'source_currency_column' => env('EXCHANGE_RATE_DB_SOURCE_CURRENCY_COLUMN', 'source_currency_code'),
    'target_currency_column' => env('EXCHANGE_RATE_DB_TARGET_CURRENCY_COLUMN', 'target_currency_code'),

    // defaults
    'default_provider' => ExchangeRateProvidersEnum::PDO->value,
    'default_service' => ExchangeRateApiService::class,

    // job settings
    'update_exchange_rates_period_in_minutes' => (float) env('EXCHANGE_RATE_UPDATE_INTERVAL_IN_DAYS', 1) * 24 * 60,

    // exchange rate services
    'services' => [
        // @see https://www.exchangerate-api.com/
        'exchange_rate_api' => [
            'api_key'   => env("EXCHANGE_RATE_API_TOKEN"),
            'version'   => env("EXCHANGE_RATE_API_VERSION"),
            'base_url'  => env('EXCHANGE_RATE_API_BASE_URL'),
        ],
    ],

];
