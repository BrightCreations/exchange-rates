# Services & Contracts

The Exchange Rates Library uses a service-oriented architecture with contracts to provide a clean, extensible interface for working with different exchange rate providers.

## Service Architecture

The library implements a layered architecture where:

- **Contracts** define the interface specifications
- **Services** implement the business logic and API communication
- **Repository** handles database operations
- **DTOs** provide type-safe data transfer

## Core Contracts

### ExchangeRateServiceInterface

The main contract for basic exchange rate operations:

```php
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;

interface ExchangeRateServiceInterface
{
    /**
     * Check if the service supports historical exchange rates
     */
    public function isSupportHistoricalExchangeRate(): bool;

    /**
     * Store exchange rates in the database
     */
    public function storeExchangeRates(string $currency_code): Collection;

    /**
     * Get exchange rates from the database
     */
    public function getExchangeRates(string $currency_code): Collection;

    /**
     * Get all exchange rates from the database
     */
    public function getAllExchangeRates(): Collection;
}
```

### HistoricalSupportExchangeRateServiceInterface

Extended contract for services that support historical data:

```php
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use Carbon\CarbonInterface;

interface HistoricalSupportExchangeRateServiceInterface
{
    /**
     * Store historical exchange rates in the database
     */
    public function storeHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection;

    /**
     * Get historical exchange rates from the database
     */
    public function getHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection;

    /**
     * Get a specific historical exchange rate from the database
     */
    public function getHistoricalExchangeRate(string $currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory;
}
```

## Available Services

### ExchangeRateApiService

**Default Service** - Uses the Exchange Rate API provider.

**Features:**
- Current exchange rates
- Historical exchange rates
- Automatic caching
- Error handling

**Configuration:**
```env
EXCHANGE_RATE_API_TOKEN=your_api_token_here
EXCHANGE_RATE_API_VERSION=v6
EXCHANGE_RATE_API_BASE_URL=https://v6.exchangerate-api.com/v6/
```

### OpenExchangeRateService

**Alternative Service** - Uses the Open Exchange Rates provider.

**Features:**
- Current exchange rates
- Historical exchange rates
- Automatic caching
- Error handling

**Configuration:**
```env
OPEN_EXCHANGE_RATE_BASE_URL=https://openexchangerates.org/api/
OPEN_EXCHANGE_RATE_APP_ID=your_app_id_here
```

## Service Usage

### Dependency Injection

The recommended way to use services is through dependency injection:

```php
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;

class CurrencyService
{
    public function __construct(
        private ExchangeRateServiceInterface $exchangeRateService,
        private HistoricalSupportExchangeRateServiceInterface $historicalService
    ) {}

    public function getCurrentRates(string $currency): Collection
    {
        return $this->exchangeRateService->getExchangeRates($currency);
    }

    public function getHistoricalRates(string $currency, Carbon $date): Collection
    {
        return $this->historicalService->getHistoricalExchangeRates($currency, $date);
    }
}
```

### Service Resolution

You can resolve services from the container:

```php
// Resolve the default service
$service = app(ExchangeRateServiceInterface::class);

// Resolve historical service
$historicalService = app(HistoricalSupportExchangeRateServiceInterface::class);

// Using resolve helper
$service = resolve(ExchangeRateServiceInterface::class);
```

### Service Capability Check

Check if a service supports historical data:

```php
$service = app(ExchangeRateServiceInterface::class);

if ($service->isSupportHistoricalExchangeRate()) {
    // Service supports historical data
    $historicalRates = $service->getHistoricalExchangeRates('USD', Carbon::yesterday());
}
```

## Service Methods

### Current Exchange Rates

#### storeExchangeRates()

Fetches and stores current exchange rates from the API:

```php
$service = app(ExchangeRateServiceInterface::class);

// Store USD exchange rates
$rates = $service->storeExchangeRates('USD');

// Returns Collection of CurrencyExchangeRate models
foreach ($rates as $rate) {
    echo "USD to {$rate->target_currency_code}: {$rate->exchange_rate}\n";
}
```

#### getExchangeRates()

Retrieves stored exchange rates from the database:

```php
$service = app(ExchangeRateServiceInterface::class);

// Get stored USD exchange rates
$rates = $service->getExchangeRates('USD');

// Returns Collection of CurrencyExchangeRate models
```

#### getAllExchangeRates()

Retrieves all stored exchange rates:

```php
$service = app(ExchangeRateServiceInterface::class);

// Get all exchange rates
$allRates = $service->getAllExchangeRates();

// Returns Collection of all CurrencyExchangeRate models
```

### Historical Exchange Rates

#### storeHistoricalExchangeRates()

Fetches and stores historical exchange rates:

```php
use Carbon\Carbon;

$service = app(HistoricalSupportExchangeRateServiceInterface::class);

// Store historical rates for a specific date
$date = Carbon::parse('2023-01-15');
$historicalRates = $service->storeHistoricalExchangeRates('USD', $date);

// Returns Collection of CurrencyExchangeRateHistory models
```

#### getHistoricalExchangeRates()

Retrieves stored historical exchange rates:

```php
use Carbon\Carbon;

$service = app(HistoricalSupportExchangeRateServiceInterface::class);

$date = Carbon::parse('2023-01-15');
$historicalRates = $service->getHistoricalExchangeRates('USD', $date);

// Returns Collection of CurrencyExchangeRateHistory models
```

#### getHistoricalExchangeRate()

Retrieves a specific historical exchange rate:

```php
use Carbon\Carbon;

$service = app(HistoricalSupportExchangeRateServiceInterface::class);

$date = Carbon::parse('2023-01-15');
$rate = $service->getHistoricalExchangeRate('USD', 'EUR', $date);

// Returns single CurrencyExchangeRateHistory model
echo "USD to EUR on {$date->format('Y-m-d')}: {$rate->exchange_rate}\n";
```

## Service Configuration

### Changing Default Service

Modify `config/exchange-rates.php`:

```php
<?php

use BrightCreations\ExchangeRates\Concretes\OpenExchangeRateService;

return [
    'default_service' => OpenExchangeRateService::class,
    // ... rest of configuration
];
```

### Service Provider Registration

The service provider automatically registers the contracts:

```php
// In ExchangeRatesServiceProvider::register()
$this->app->singleton(ExchangeRateServiceInterface::class, fn() => 
    $this->app->make(Config::get('exchange-rates.default_service'))
);
```

## Error Handling

Services include built-in error handling:

```php
try {
    $rates = $service->storeExchangeRates('USD');
} catch (\Illuminate\Http\Client\RequestException $e) {
    // Handle API request errors
    Log::error('Exchange rate API error: ' . $e->getMessage());
} catch (\Exception $e) {
    // Handle other errors
    Log::error('Exchange rate service error: ' . $e->getMessage());
}
```

## Creating Custom Services

To create a custom service, implement the required contracts:

```php
<?php

namespace App\Services;

use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use Illuminate\Support\Collection;
use Carbon\CarbonInterface;

class CustomExchangeRateService implements ExchangeRateServiceInterface, HistoricalSupportExchangeRateServiceInterface
{
    public function isSupportHistoricalExchangeRate(): bool
    {
        return true;
    }

    public function storeExchangeRates(string $currency_code): Collection
    {
        // Your implementation
    }

    public function getExchangeRates(string $currency_code): Collection
    {
        // Your implementation
    }

    public function getAllExchangeRates(): Collection
    {
        // Your implementation
    }

    public function storeHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection
    {
        // Your implementation
    }

    public function getHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection
    {
        // Your implementation
    }

    public function getHistoricalExchangeRate(string $currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory
    {
        // Your implementation
    }
}
```

Then register it in your service provider or configuration.

## Best Practices

1. **Use Contracts**: Always inject contracts rather than concrete implementations
2. **Check Capabilities**: Verify service capabilities before using advanced features
3. **Handle Errors**: Implement proper error handling for API calls
4. **Cache Results**: Services automatically cache results, but consider additional caching strategies
5. **Monitor Usage**: Track API usage to stay within rate limits

## Related Documentation

- **[Repository Pattern](repository.md)** - Database operations and DTO usage
- **[DTOs Guide](dtos.md)** - Data Transfer Objects explained
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Examples](examples.md)** - Practical usage examples 