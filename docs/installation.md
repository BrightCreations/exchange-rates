# Installation & Configuration

This guide will walk you through the complete installation and configuration process for the Exchange Rates Library.

## Prerequisites

- PHP 8.1 or higher
- Laravel 9.0, 10.0, 11.0, or 12.0
- Composer
- Database (MySQL, PostgreSQL, SQLite, etc.)

## Step 1: Install the Package

Install the package via Composer:

```bash
composer require brightcreations/exchange-rates
```

## Step 2: Publish Configuration Files

Publish the configuration file to customize the package settings:

```bash
php artisan vendor:publish --tag=exchange-rates-config
```

This will create `config/exchange-rates.php` in your Laravel application.

## Step 3: Publish and Run Migrations

Publish the database migrations:

```bash
php artisan vendor:publish --tag=exchange-rate-migrations
```

Run the migrations to create the necessary database tables:

```bash
php artisan migrate
```

### Database Tables Created

The package creates two main tables:

1. **`currency_exchange_rates`** - Stores current exchange rates
2. **`currency_exchange_rates_history`** - Stores historical exchange rates

## Step 4: Configure Environment Variables

Add the following environment variables to your `.env` file:

### Exchange Rate API (Default Service)

```env
# Exchange Rate API Configuration
EXCHANGE_RATE_API_TOKEN=your_api_token_here
EXCHANGE_RATE_API_VERSION=v6
EXCHANGE_RATE_API_BASE_URL=https://v6.exchangerate-api.com/v6/
```

### Open Exchange Rates (Alternative Service)

```env
# Open Exchange Rates Configuration
OPEN_EXCHANGE_RATE_BASE_URL=https://openexchangerates.org/api/
OPEN_EXCHANGE_RATE_APP_ID=your_app_id_here
```

## Step 5: Get API Keys

### Exchange Rate API
1. Visit [Exchange Rate API](https://www.exchangerate-api.com/)
2. Sign up for a free account
3. Get your API key from the dashboard
4. Add it to your `.env` file as `EXCHANGE_RATE_API_TOKEN`

### Open Exchange Rates (Optional)
1. Visit [Open Exchange Rates](https://openexchangerates.org/)
2. Sign up for an account
3. Get your App ID from the dashboard
4. Add it to your `.env` file as `OPEN_EXCHANGE_RATE_APP_ID`

## Step 6: Configure the Default Service

The package uses Exchange Rate API as the default service. To change this, modify the `config/exchange-rates.php` file:

```php
<?php

use BrightCreations\ExchangeRates\Concretes\ExchangeRateApiService;
use BrightCreations\ExchangeRates\Concretes\OpenExchangeRateService;

return [
    // Change default service
    'default_service' => OpenExchangeRateService::class,

    // ... rest of configuration
];
```

## Step 7: Verify Installation

Create a simple test to verify the installation:

```php
<?php

namespace App\Http\Controllers;

use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test(Request $request)
    {
        $service = app(ExchangeRateServiceInterface::class);
        
        try {
            $rates = $service->storeExchangeRates('USD');
            return response()->json([
                'success' => true,
                'message' => 'Exchange rates library is working!',
                'rates_count' => $rates->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

## Configuration Options

### Available Configuration

The `config/exchange-rates.php` file contains the following options:

```php
<?php

use BrightCreations\ExchangeRates\Concretes\ExchangeRateApiService;

return [
    // Default service to use
    'default_service' => ExchangeRateApiService::class,

    // Available services configuration
    'services' => [
        'exchange_rate_api' => [
            'api_key'   => env("EXCHANGE_RATE_API_TOKEN"),
            'version'   => env("EXCHANGE_RATE_API_VERSION"),
            'base_url'  => env('EXCHANGE_RATE_API_BASE_URL', 'https://v6.exchangerate-api.com/v6/'),
        ],
        'open_exchange_rate' => [
            'base_url'  => env('OPEN_EXCHANGE_RATE_BASE_URL', 'https://openexchangerates.org/api/'),
            'app_id'    => env('OPEN_EXCHANGE_RATE_APP_ID'),
        ]
    ],
];
```

### Service Provider Registration

The package automatically registers the service provider. If you need to register it manually, add this to your `config/app.php`:

```php
'providers' => [
    // ... other providers
    BrightCreations\ExchangeRates\ExchangeRatesServiceProvider::class,
],
```

## Troubleshooting

### Common Issues

1. **"Class not found" errors**
   - Run `composer dump-autoload`
   - Clear Laravel cache: `php artisan cache:clear`

2. **Database connection errors**
   - Verify your database configuration in `.env`
   - Ensure migrations ran successfully

3. **API authentication errors**
   - Verify your API keys are correct
   - Check that environment variables are properly set
   - Ensure your API service account is active

4. **Service not found errors**
   - Verify the service provider is registered
   - Check that the default service class exists

### Debug Mode

Enable debug mode to see detailed error messages:

```env
APP_DEBUG=true
```

## Next Steps

After successful installation, you can:

1. **[Read the Services Guide](services.md)** - Learn about the service layer and contracts
2. **[Explore the Repository Pattern](repository.md)** - Understand database operations
3. **[Learn about DTOs](dtos.md)** - Master data transfer objects
4. **[Check the API Reference](api-reference.md)** - Complete method documentation
5. **[See Examples](examples.md)** - Practical usage examples 