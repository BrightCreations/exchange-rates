# Exchange Rates Service Package

![Downloads](https://img.shields.io/github/downloads/BrightCreations/exchange-rates/total)
![License](https://img.shields.io/github/license/BrightCreations/exchange-rates)
![Last Commit](https://img.shields.io/github/last-commit/BrightCreations/exchange-rates)
![Stars](https://img.shields.io/github/stars/BrightCreations/exchange-rates?style=social)
![Tests](https://img.shields.io/github/actions/workflow/status/BrightCreations/exchange-rates/tests.yml?label=tests)

## Overview
The Exchange Rates Service package provides a robust and flexible way to retrieve and manage exchange rates in your Laravel application. This package implements a clean architecture with contracts, DTOs, and multiple service providers, making it ideal for e-commerce, financial, and other applications that require currency conversions.

## Features
- **Contract-based Architecture**: Use interfaces for loose coupling and testability
- **Multiple Service Providers**: Support for Exchange Rate API and Open Exchange Rates
- **Historical Data Support**: Retrieve and store historical exchange rates
- **DTO Pattern**: Structured data transfer objects for type safety
- **Database Caching**: Automatic caching of exchange rates for improved performance
- **Flexible Configuration**: Easy configuration through environment variables

## Installation
To install the Exchange Rates Service package, run the following command in your terminal:

```bash
composer require brightcreations/exchange-rates
```

## Migrations
You can run the package migrations using the following command:

```bash
php artisan exchange-rates:migrate
```

## Configuration
To configure the package, publish the configuration file using the following command:

```bash
php artisan vendor:publish --provider="BrightCreations\ExchangeRates\ExchangeRatesServiceProvider"
```

Next, execute the migrations (if they haven't been executed yet):

```bash
php artisan migrate
```

Then, update the `exchange-rates.php` configuration file and add the required environment variables:

```env
# Exchange Rate API Configuration
EXCHANGE_RATE_API_TOKEN=your_api_key_here
EXCHANGE_RATE_API_VERSION=v6
EXCHANGE_RATE_API_BASE_URL=https://v6.exchangerate-api.com/v6/

# Open Exchange Rates Configuration (Alternative)
OPEN_EXCHANGE_RATE_BASE_URL=https://openexchangerates.org/api/
OPEN_EXCHANGE_RATE_APP_ID=your_app_id_here
```

## Usage

### Using Contracts (Recommended Approach)

The package provides several contracts that you should use for dependency injection:

#### 1. Basic Exchange Rate Service

```php
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use Illuminate\Support\Collection;

class CurrencyService
{
    public function __construct(
        private ExchangeRateServiceInterface $exchangeRateService
    ) {}

    public function getCurrentRates(string $baseCurrency): Collection
    {
        return $this->exchangeRateService->getExchangeRates($baseCurrency);
    }

    public function storeLatestRates(string $baseCurrency): Collection
    {
        return $this->exchangeRateService->storeExchangeRates($baseCurrency);
    }

    public function getAllRates(): Collection
    {
        return $this->exchangeRateService->getAllExchangeRates();
    }
}
```

#### 2. Historical Exchange Rate Service

```php
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use Carbon\Carbon;

class HistoricalCurrencyService
{
    public function __construct(
        private HistoricalSupportExchangeRateServiceInterface $historicalService
    ) {}

    public function getHistoricalRates(string $baseCurrency, Carbon $date): Collection
    {
        return $this->historicalService->getHistoricalExchangeRates($baseCurrency, $date);
    }

    public function getSpecificHistoricalRate(
        string $baseCurrency, 
        string $targetCurrency, 
        Carbon $date
    ): CurrencyExchangeRateHistory {
        return $this->historicalService->getHistoricalExchangeRate(
            $baseCurrency, 
            $targetCurrency, 
            $date
        );
    }

    public function storeHistoricalRates(string $baseCurrency, Carbon $date): Collection
    {
        return $this->historicalService->storeHistoricalExchangeRates($baseCurrency, $date);
    }
}
```

### Using DTOs for Type Safety

The package provides several DTOs for structured data handling:

#### 1. ExchangeRatesDto

```php
use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;

// Create DTO for current exchange rates
$exchangeRatesDto = new ExchangeRatesDto(
    base_currency_code: 'USD',
    exchange_rates: [
        'EUR' => 0.85,
        'GBP' => 0.73,
        'JPY' => 110.50
    ]
);

// Access data through getters
$baseCurrency = $exchangeRatesDto->getBaseCurrencyCode(); // 'USD'
$rates = $exchangeRatesDto->getExchangeRates(); // ['EUR' => 0.85, ...]
```

#### 2. CurrenciesPairDto

```php
use BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto;

// Create DTO for currency pair
$currencyPair = new CurrenciesPairDto(
    base_currency_code: 'USD',
    target_currency_code: 'EUR'
);

// Access individual currencies
$baseCurrency = $currencyPair->getBaseCurrencyCode(); // 'USD'
$targetCurrency = $currencyPair->getTargetCurrencyCode(); // 'EUR'

// Convert to string representation
$pairString = (string) $currencyPair; // 'USD_EUR'
```

#### 3. HistoricalExchangeRatesDto

```php
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use Carbon\Carbon;

// Create DTO for historical exchange rates
$historicalRates = new HistoricalExchangeRatesDto(
    base_currency_code: 'USD',
    exchange_rates: [
        'EUR' => 0.82,
        'GBP' => 0.70
    ],
    date_time: Carbon::parse('2023-01-15')
);

// Access historical data
$date = $historicalRates->getDateTime(); // Carbon instance
$baseCurrency = $historicalRates->getBaseCurrencyCode(); // 'USD'
$rates = $historicalRates->getExchangeRates(); // ['EUR' => 0.82, ...]
```

### Service Resolution Methods

#### Constructor Injection (Recommended)

```php
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;

class CurrencyController
{
    public function __construct(
        private ExchangeRateServiceInterface $exchangeRateService
    ) {}

    public function index()
    {
        $rates = $this->exchangeRateService->getExchangeRates('USD');
        return response()->json($rates);
    }
}
```

#### Using Laravel's Service Container

```php
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;

// Using resolve helper
$exchangeRateService = resolve(ExchangeRateServiceInterface::class);
$rates = $exchangeRateService->getExchangeRates('USD');

// Using app() helper
$exchangeRateService = app(ExchangeRateServiceInterface::class);
$rates = $exchangeRateService->getExchangeRates('USD');

// Using make method
$exchangeRateService = app()->make(ExchangeRateServiceInterface::class);
$rates = $exchangeRateService->getExchangeRates('USD');
```

### Working with Models

The package provides Eloquent models for database operations:

```php
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;

// Create or update exchange rate
$exchangeRate = CurrencyExchangeRate::createOrUpdate([
    'base_currency_code' => 'USD',
    'target_currency_code' => 'EUR',
    'exchange_rate' => 0.85,
    'last_update_date' => now(),
]);

// Query historical rates
$historicalRates = CurrencyExchangeRateHistory::where('base_currency_code', 'USD')
    ->where('date_time', '2023-01-15')
    ->get();
```

### Checking Service Capabilities

```php
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;

class CurrencyService
{
    public function __construct(
        private ExchangeRateServiceInterface $exchangeRateService
    ) {}

    public function canHandleHistoricalData(): bool
    {
        return $this->exchangeRateService->isSupportHistoricalExchangeRate();
    }
}
```

## API Documentation
Coming soon...

## Contributing
Contributions are welcome! Please submit a pull request or open an issue to report any bugs or suggest new features.

## License
This package is licensed under the MIT License.

## Author
Kareem Mohamed - Bright Creations
Email: [kareem.shaaban@brightcreations.com](mailto:kareem.shaaban@brightcreations.com)

## Version
0.2.0
