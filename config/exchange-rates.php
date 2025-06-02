<?php

use BrightCreations\ExchangeRates\Concretes\ExchangeRateApiService;

return [

    // defaults
    'default_service' => ExchangeRateApiService::class,

    // exchange rate services
    'services' => [
        // @see https://www.exchangerate-api.com/
        'exchange_rate_api' => [
            'api_key'   => env("EXCHANGE_RATE_API_TOKEN"),
            'version'   => env("EXCHANGE_RATE_API_VERSION"),
            'base_url'  => env('EXCHANGE_RATE_API_BASE_URL'),
        ],
        // @see https://docs.openexchangerates.org/reference/api-introduction
        'open_exchange_rate' => [
            'base_url'  => env('OPEN_EXCHANGE_RATE_BASE_URL'),
            'app_id'    => env('OPEN_EXCHANGE_RATE_APP_ID'),
        ]
    ],

];
