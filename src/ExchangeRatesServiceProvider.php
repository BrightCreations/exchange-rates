<?php

namespace Brights\ExchangeRates;

use Brights\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class ExchangeRatesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish config file
        $this->publishes([
            __DIR__ . '/../config/exchange-rates.php' => $this->app->configPath('exchange-rates.php'),
        ], 'exchange-rates-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => $this->app->resourcePath('views/vendor/exchange-rates'),
        ], 'exchange-rates-views');

        // Publish Migrations
        $this->publishes([
            __DIR__ . '/Database/migrations' => $this->app->databasePath('migrations'),
        ], 'exchange-rate-migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'exchange-rates');

        // Load Migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/exchange-rates.php',
            'exchange-rates'
        );

        // Register the service
        $this->app->bind(ExchangeRateServiceInterface::class, fn() => $this->app->make(Config::get('exchange-rates.default_service')));

        // Register the command
        $this->commands([
            \Brights\ExchangeRates\Console\Commands\MigrateExchangeRatesCommand::class,
        ]);
    }
}
