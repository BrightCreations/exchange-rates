<?php

namespace BrightCreations\ExchangeRates\Tests;

use BrightCreations\ExchangeRates\ExchangeRatesServiceProvider;
use Dotenv\Dotenv;

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
}
