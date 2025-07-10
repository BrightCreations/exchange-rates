<?php

namespace BrightCreations\ExchangeRates\Tests;

use BrightCreations\ExchangeRates\ExchangeRatesServiceProvider;
use Dotenv\Dotenv;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        // Load .env file
        if (file_exists(__DIR__ . '/../.env')) {
            Dotenv::createImmutable(__DIR__ . '/../')->load();
        }

        parent::setUp();
        
        // Initialize the service provider
        $this->app->boot();
        
        // Create tables manually for testing
        $this->createTables();
    }

    protected function getPackageProviders($app)
    {
        return [ExchangeRatesServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Configure testing environment
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
    
    protected function createTables()
    {
        // Create currency_exchange_rates table
        if (!Schema::hasTable('currency_exchange_rates')) {
            Schema::create('currency_exchange_rates', function ($table) {
                $table->id();
                $table->string('base_currency_code', 3);
                $table->string('target_currency_code', 3);
                $table->string('exchange_rate', 20);
                $table->timestamp('last_update_date')->default(now());
                
                $table->unique(['base_currency_code', 'target_currency_code'], 'currency_exchange_rates_unique');
            });
        }
        
        // Create currency_exchange_rates_history table
        if (!Schema::hasTable('currency_exchange_rates_history')) {
            Schema::create('currency_exchange_rates_history', function ($table) {
                $table->id();
                $table->string('base_currency_code', 3);
                $table->string('target_currency_code', 3);
                $table->string('exchange_rate', 20);
                $table->timestamp('date_time');
                $table->timestamp('last_update_date')->default(now());
                
                $table->unique(['base_currency_code', 'target_currency_code', 'date_time'], 'currency_exchange_rates_history_unique');
            });
        }
    }
}
