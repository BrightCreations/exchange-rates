<?php

use BrightCreations\ExchangeRates\Concretes\ExchangeRateApiService;
use BrightCreations\ExchangeRates\Concretes\FallbackExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\OpenExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;

return [

    /*
    |--------------------------------------------------------------------------
    | Built-in HTTP Endpoints
    |--------------------------------------------------------------------------
    |
    | Set 'routes.enabled' to true to register the package's default read-only
    | REST endpoints. The 'prefix' and 'middleware' keys let the consuming
    | application adjust the route group without publishing the package files.
    |
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'exchange-rates',
        'middleware' => ['api'],
    ],

    // defaults
    'default_service' => FallbackExchangeRateService::class,

    // Fallback order for exchange rate services
    // The FallbackExchangeRateService will try each service in this order until one succeeds
    'fallback_order' => [
        OpenExchangeRateService::class,
        ExchangeRateApiService::class,
        WorldBankExchangeRateApiService::class,
    ],

    // Providers (stored provider names) whose rates are period averages rather than daily rates —
    // World Bank publishes yearly averages. They are a last resort: exact lookups prefer a daily
    // rate for the same date, interpolation never uses them as bounds, and
    // `exchange-rates:refresh-provisional` replaces them once a daily provider has the date.
    'provisional_providers' => [
        'WorldBankExchangeRateApiService',
    ],

    // Temporarily skip a provider whose errors mean retrying is pointless (seconds; 0 disables).
    // - unavailable_seconds: after an exhausted quota or rejected credentials (HTTP 401/429,
    //   e.g. `quota-reached`, `invalid-key`, `access_restricted`), for every operation.
    // - historical_unsupported_seconds: after the plan rejects historical requests
    //   (e.g. ExchangeRate-API `plan-upgrade-required` on the free plan), for historical operations.
    'fallback_suspension' => [
        'unavailable_seconds' => (int) env('EXCHANGE_RATES_PROVIDER_UNAVAILABLE_SECONDS', 3600),
        'historical_unsupported_seconds' => (int) env('EXCHANGE_RATES_PROVIDER_HISTORICAL_UNSUPPORTED_SECONDS', 86400),
    ],

    // HTTP timeouts (seconds) for provider API calls. Unset keeps Laravel's HTTP client
    // defaults (30s request, 10s connect).
    'http' => [
        'timeout' => env('EXCHANGE_RATES_HTTP_TIMEOUT'),
        'connect_timeout' => env('EXCHANGE_RATES_HTTP_CONNECT_TIMEOUT'),
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
