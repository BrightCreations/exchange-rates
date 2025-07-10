<?php

namespace BrightCreations\ExchangeRates;

use BrightCreations\ExchangeRates\Concretes\Repositories\CurrencyExchangeRateRepository;
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
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

        // Publish Migrations
        $this->publishes([
            __DIR__ . '/Database/Migrations' => $this->app->databasePath('migrations'),
        ], 'exchange-rate-migrations');

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
        $this->app->singleton(CurrencyExchangeRateRepositoryInterface::class, CurrencyExchangeRateRepository::class);
        $this->app->singleton(ExchangeRateServiceInterface::class, function () {
            $service = $this->app->make(Config::get('exchange-rates.default_service'));
            if (!($service instanceof ExchangeRateServiceInterface)) {
                throw new \RuntimeException('The configured exchange rate service does not implement ExchangeRateServiceInterface.');
            }
            return $service;
        });
        $this->app->singleton(HistoricalSupportExchangeRateServiceInterface::class, function () {
            $service = $this->app->make(Config::get('exchange-rates.default_service'));
            if (!($service instanceof HistoricalSupportExchangeRateServiceInterface)) {
                throw new \RuntimeException('The configured exchange rate service does not implement HistoricalSupportExchangeRateServiceInterface.');
            }
            return $service;
        });

        // Register the command
        $this->commands([
            \BrightCreations\ExchangeRates\Console\Commands\MigrateExchangeRatesCommand::class,
        ]);
    }
}
