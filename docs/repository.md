# Repository Pattern

The Exchange Rates Library implements the Repository pattern to provide a clean abstraction layer between the business logic and data access. This ensures type safety, maintainability, and testability.

## Repository Architecture

The repository layer consists of:

- **Contracts** (`CurrencyExchangeRateRepositoryInterface`) - Define the interface
- **Implementation** (`CurrencyExchangeRateRepository`) - Concrete implementation
- **DTOs** - Type-safe data transfer objects for method arguments
- **Models** - Eloquent models for database entities

## Repository Interface

The main repository contract defines all database operations:

```php
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;

interface CurrencyExchangeRateRepositoryInterface
{
    // Current exchange rates
    public function updateExchangeRates(string $base_currency_code, array $exchange_rates): bool;
    public function updateBulkExchangeRates(array $exchange_rates): bool;
    public function getAllExchangeRates(): Collection;
    public function getExchangeRates(string $base_currency_code): Collection;
    public function getBulkExchangeRates(array $base_currency_codes): Collection;
    public function getExchangeRate(string $base_currency_code, string $target_currency_code): CurrencyExchangeRate;
    public function getBulkExchangeRate(array $currencies_pairs): Collection;

    // Historical exchange rates
    public function updateExchangeRatesHistory(string $base_currency_code, array $exchange_rates, CarbonInterface $date_time): bool;
    public function updateBulkExchangeRatesHistory(array $historical_exchange_rates): bool;
    public function getHistoricalExchangeRates(string $base_currency_code, CarbonInterface $date_time): Collection;
    public function getBulkHistoricalExchangeRates(array $historical_base_currencies): Collection;
    public function getHistoricalExchangeRate(string $base_currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory;
    public function getBulkHistoricalExchangeRate(array $historical_currencies_pairs): Collection;
}
```

## DTOs in Repository Operations

DTOs (Data Transfer Objects) are used extensively in repository operations to ensure type safety and clear data contracts. Here's how they work:

### ExchangeRatesDto

Used for bulk current exchange rate operations:

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

// Pass to repository
$repository->updateBulkExchangeRates([$exchangeRatesDto]);
```

### HistoricalExchangeRatesDto

Used for bulk historical exchange rate operations:

```php
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use Carbon\Carbon;

// Create DTO for historical exchange rates
$historicalDto = new HistoricalExchangeRatesDto(
    base_currency_code: 'USD',
    exchange_rates: [
        'EUR' => 0.82,
        'GBP' => 0.70
    ],
    date_time: Carbon::parse('2023-01-15')
);

// Pass to repository
$repository->updateBulkExchangeRatesHistory([$historicalDto]);
```

### CurrenciesPairDto

Used for bulk currency pair queries:

```php
use BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto;

// Create DTOs for currency pairs
$pairs = [
    new CurrenciesPairDto('USD', 'EUR'),
    new CurrenciesPairDto('USD', 'GBP'),
    new CurrenciesPairDto('EUR', 'USD')
];

// Get bulk exchange rates
$rates = $repository->getBulkExchangeRate($pairs);

// Returns: Collection with keys like 'USD_EUR', 'USD_GBP', 'EUR_USD'
foreach ($rates as $pairKey => $rate) {
    echo "Rate for {$pairKey}: {$rate->exchange_rate}\n";
}
```

### HistoricalCurrenciesPairDto

Used for bulk historical currency pair queries:

```php
use BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto;
use Carbon\Carbon;

// Create DTOs for historical currency pairs
$historicalPairs = [
    new HistoricalCurrenciesPairDto('USD', 'EUR', Carbon::parse('2023-01-15')),
    new HistoricalCurrenciesPairDto('USD', 'GBP', Carbon::parse('2023-01-15')),
    new HistoricalCurrenciesPairDto('EUR', 'USD', Carbon::parse('2023-01-16'))
];

// Get bulk historical exchange rates
$historicalRates = $repository->getBulkHistoricalExchangeRate($historicalPairs);

// Returns: Collection with keys like 'USD_EUR_2023-01-15', 'USD_GBP_2023-01-15'
foreach ($historicalRates as $pairKey => $rate) {
    echo "Historical rate for {$pairKey}: {$rate->exchange_rate}\n";
}
```

### HistoricalBaseCurrencyDto

Used for bulk historical base currency queries:

```php
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use Carbon\Carbon;

// Create DTOs for historical base currencies
$historicalBaseCurrencies = [
    new HistoricalBaseCurrencyDto('USD', Carbon::parse('2023-01-15')),
    new HistoricalBaseCurrencyDto('EUR', Carbon::parse('2023-01-15')),
    new HistoricalBaseCurrencyDto('GBP', Carbon::parse('2023-01-16'))
];

// Get bulk historical exchange rates
$historicalRates = $repository->getBulkHistoricalExchangeRates($historicalBaseCurrencies);

// Returns: Collection grouped by base currency and date
foreach ($historicalRates as $baseKey => $rates) {
    echo "Historical rates for {$baseKey}:\n";
    foreach ($rates as $rate) {
        echo "  {$rate->target_currency_code}: {$rate->exchange_rate}\n";
    }
}
```

## Repository Methods

### Current Exchange Rates

#### updateExchangeRates()

Updates exchange rates for a single base currency:

```php
$repository = app(CurrencyExchangeRateRepositoryInterface::class);

$rates = [
    'EUR' => 0.85,
    'GBP' => 0.73,
    'JPY' => 110.50
];

$success = $repository->updateExchangeRates('USD', $rates);
```

#### updateBulkExchangeRates()

Updates exchange rates for multiple base currencies using DTOs:

```php
use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;

$dtos = [
    new ExchangeRatesDto('USD', ['EUR' => 0.85, 'GBP' => 0.73]),
    new ExchangeRatesDto('EUR', ['USD' => 1.18, 'GBP' => 0.86])
];

$success = $repository->updateBulkExchangeRates($dtos);
```

#### getExchangeRates()

Retrieves all exchange rates for a base currency:

```php
$rates = $repository->getExchangeRates('USD');

// Returns Collection<CurrencyExchangeRate>
foreach ($rates as $rate) {
    echo "USD to {$rate->target_currency_code}: {$rate->exchange_rate}\n";
}
```

#### getBulkExchangeRates()

Retrieves exchange rates for multiple base currencies:

```php
$rates = $repository->getBulkExchangeRates(['USD', 'EUR', 'GBP']);

// Returns Collection grouped by base currency
foreach ($rates as $baseCurrency => $currencyRates) {
    echo "Rates for {$baseCurrency}:\n";
    foreach ($currencyRates as $rate) {
        echo "  {$rate->target_currency_code}: {$rate->exchange_rate}\n";
    }
}
```

#### getExchangeRate()

Retrieves a specific exchange rate:

```php
try {
    $rate = $repository->getExchangeRate('USD', 'EUR');
    echo "USD to EUR: {$rate->exchange_rate}\n";
} catch (ModelNotFoundException $e) {
    echo "Exchange rate not found\n";
}
```

#### getBulkExchangeRate()

Retrieves multiple specific exchange rates using DTOs:

```php
use BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto;

$pairs = [
    new CurrenciesPairDto('USD', 'EUR'),
    new CurrenciesPairDto('USD', 'GBP'),
    new CurrenciesPairDto('EUR', 'USD')
];

$rates = $repository->getBulkExchangeRate($pairs);

// Returns Collection with keys like 'USD_EUR', 'USD_GBP', 'EUR_USD'
foreach ($rates as $pairKey => $rate) {
    echo "Rate for {$pairKey}: {$rate->exchange_rate}\n";
}
```

### Historical Exchange Rates

#### updateExchangeRatesHistory()

Updates historical exchange rates for a specific date:

```php
use Carbon\Carbon;

$date = Carbon::parse('2023-01-15');
$rates = [
    'EUR' => 0.82,
    'GBP' => 0.70,
    'JPY' => 108.50
];

$success = $repository->updateExchangeRatesHistory('USD', $rates, $date);
```

#### updateBulkExchangeRatesHistory()

Updates historical exchange rates for multiple dates using DTOs:

```php
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use Carbon\Carbon;

$dtos = [
    new HistoricalExchangeRatesDto('USD', ['EUR' => 0.82, 'GBP' => 0.70], Carbon::parse('2023-01-15')),
    new HistoricalExchangeRatesDto('EUR', ['USD' => 1.22, 'GBP' => 0.85], Carbon::parse('2023-01-15'))
];

$success = $repository->updateBulkExchangeRatesHistory($dtos);
```

#### getHistoricalExchangeRates()

Retrieves historical exchange rates for a specific date:

```php
use Carbon\Carbon;

$date = Carbon::parse('2023-01-15');
$rates = $repository->getHistoricalExchangeRates('USD', $date);

// Returns Collection<CurrencyExchangeRateHistory>
foreach ($rates as $rate) {
    echo "USD to {$rate->target_currency_code} on {$rate->date_time}: {$rate->exchange_rate}\n";
}
```

#### getBulkHistoricalExchangeRates()

Retrieves historical exchange rates for multiple base currencies and dates:

```php
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use Carbon\Carbon;

$dtos = [
    new HistoricalBaseCurrencyDto('USD', Carbon::parse('2023-01-15')),
    new HistoricalBaseCurrencyDto('EUR', Carbon::parse('2023-01-15')),
    new HistoricalBaseCurrencyDto('GBP', Carbon::parse('2023-01-16'))
];

$rates = $repository->getBulkHistoricalExchangeRates($dtos);

// Returns Collection grouped by base currency and date
foreach ($rates as $baseKey => $currencyRates) {
    echo "Historical rates for {$baseKey}:\n";
    foreach ($currencyRates as $rate) {
        echo "  {$rate->target_currency_code}: {$rate->exchange_rate}\n";
    }
}
```

#### getHistoricalExchangeRate()

Retrieves a specific historical exchange rate:

```php
use Carbon\Carbon;

try {
    $date = Carbon::parse('2023-01-15');
    $rate = $repository->getHistoricalExchangeRate('USD', 'EUR', $date);
    echo "USD to EUR on {$date->format('Y-m-d')}: {$rate->exchange_rate}\n";
} catch (ModelNotFoundException $e) {
    echo "Historical exchange rate not found\n";
}
```

#### getBulkHistoricalExchangeRate()

Retrieves multiple specific historical exchange rates:

```php
use BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto;
use Carbon\Carbon;

$pairs = [
    new HistoricalCurrenciesPairDto('USD', 'EUR', Carbon::parse('2023-01-15')),
    new HistoricalCurrenciesPairDto('USD', 'GBP', Carbon::parse('2023-01-15')),
    new HistoricalCurrenciesPairDto('EUR', 'USD', Carbon::parse('2023-01-16'))
];

$rates = $repository->getBulkHistoricalExchangeRate($pairs);

// Returns Collection with keys like 'USD_EUR_2023-01-15', 'USD_GBP_2023-01-15'
foreach ($rates as $pairKey => $rate) {
    echo "Historical rate for {$pairKey}: {$rate->exchange_rate}\n";
}
```

## Why DTOs?

DTOs provide several benefits in repository operations:

1. **Type Safety**: Ensures correct data structure
2. **Validation**: Can include validation logic
3. **Clarity**: Makes method signatures clear and self-documenting
4. **Extensibility**: Easy to add new properties without breaking existing code
5. **Testing**: Easier to mock and test

## Repository Registration

The repository is automatically registered in the service provider:

```php
// In ExchangeRatesServiceProvider::register()
$this->app->singleton(CurrencyExchangeRateRepositoryInterface::class, CurrencyExchangeRateRepository::class);
```

## Error Handling

The repository throws specific exceptions:

```php
use Illuminate\Database\Eloquent\ModelNotFoundException;

try {
    $rate = $repository->getExchangeRate('USD', 'INVALID');
} catch (ModelNotFoundException $e) {
    // Handle not found case
    Log::warning('Exchange rate not found: USD to INVALID');
} catch (\Exception $e) {
    // Handle other errors
    Log::error('Repository error: ' . $e->getMessage());
}
```

## Best Practices

1. **Use DTOs**: Always use DTOs for bulk operations
2. **Handle Exceptions**: Implement proper exception handling
3. **Type Hinting**: Use interface injection for testability
4. **Validation**: Validate data before passing to repository
5. **Caching**: Consider caching frequently accessed data

## Related Documentation

- **[Services & Contracts](services.md)** - Understanding the service layer
- **[DTOs Guide](dtos.md)** - Detailed DTO documentation
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Examples](examples.md)** - Practical usage examples 