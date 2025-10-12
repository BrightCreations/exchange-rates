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
            'base_url'  => env('EXCHANGE_RATE_API_BASE_URL', 'https://v6.exchangerate-api.com/v6/'),
        ],
        // @see https://docs.openexchangerates.org/reference/api-introduction
        'open_exchange_rate' => [
            'base_url'  => env('OPEN_EXCHANGE_RATE_BASE_URL', 'https://openexchangerates.org/api/'),
            'app_id'    => env('OPEN_EXCHANGE_RATE_APP_ID'),
        ],
        // @see https://chatgpt.com/share/68ebbd85-d940-8006-9a1b-ff855a9513a1
        'world_bank_exchange_rate' => [
            'base_url'  => env('WORLD_BANK_EXCHANGE_RATE_BASE_URL', 'https://api.worldbank.org/v2/'),
        ],
    ],

];
