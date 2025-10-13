<?php

use BrightCreations\ExchangeRates\Concretes\ExchangeRateApiService;
use BrightCreations\ExchangeRates\Concretes\FallbackExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\OpenExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;

return [

    // defaults
    'default_service' => FallbackExchangeRateService::class,

    // Fallback order for exchange rate services
    // The FallbackExchangeRateService will try each service in this order until one succeeds
    'fallback_order' => [
        ExchangeRateApiService::class,
        OpenExchangeRateService::class,
        WorldBankExchangeRateApiService::class,
    ],

    // exchange rate services
    'services' => [
        // @see https://www.exchangerate-api.com/
        'exchange_rate_api' => [
            'api_key' => env('EXCHANGE_RATE_API_TOKEN'),
            'version' => env('EXCHANGE_RATE_API_VERSION'),
            'base_url' => env('EXCHANGE_RATE_API_BASE_URL', 'https://v6.exchangerate-api.com/v6/'),
        ],
        // @see https://docs.openexchangerates.org/reference/api-introduction
        'open_exchange_rate' => [
            'base_url' => env('OPEN_EXCHANGE_RATE_BASE_URL', 'https://openexchangerates.org/api/'),
            'app_id' => env('OPEN_EXCHANGE_RATE_APP_ID'),
        ],
        // @see https://api.worldbank.org/v2/country/all/indicator/PA.NUS.FCRF
        'world_bank_exchange_rate' => [
            'base_url' => env('WORLD_BANK_EXCHANGE_RATE_BASE_URL', 'https://api.worldbank.org/v2'),
        ],
    ],

];
