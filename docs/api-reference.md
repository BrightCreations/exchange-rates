# API Reference

Complete reference documentation for all classes, methods, and interfaces in the Exchange Rates Library.

## Table of Contents

- [Service Interfaces](#service-interfaces)
- [Repository Interface](#repository-interface)
- [DTOs](#dtos)
- [Models](#models)
- [Traits](#traits)

## Service Interfaces

### ExchangeRateServiceInterface

Main contract for exchange rate operations.

#### Methods

##### `isSupportHistoricalExchangeRate(): bool`

Checks if the service supports historical exchange rates.

**Returns:** `bool` - True if historical data is supported

**Example:**
```php
$service = app(ExchangeRateServiceInterface::class);
$supportsHistorical = $service->isSupportHistoricalExchangeRate();
```

##### `storeExchangeRates(string $currency_code): Collection`

Fetches and stores current exchange rates from the API.

**Parameters:**
- `$currency_code` (string) - Base currency code (e.g., 'USD')

**Returns:** `Collection<CurrencyExchangeRate>` - Collection of stored exchange rates

**Example:**
```php
$service = app(ExchangeRateServiceInterface::class);
$rates = $service->storeExchangeRates('USD');

foreach ($rates as $rate) {
    echo "USD to {$rate->target_currency_code}: {$rate->exchange_rate}\n";
}
```

##### `getExchangeRates(string $currency_code): Collection`

Retrieves stored exchange rates from the database.

**Parameters:**
- `$currency_code` (string) - Base currency code (e.g., 'USD')

**Returns:** `Collection<CurrencyExchangeRate>` - Collection of exchange rates

**Example:**
```php
$service = app(ExchangeRateServiceInterface::class);
$rates = $service->getExchangeRates('USD');
```

##### `getAllExchangeRates(): Collection`

Retrieves all stored exchange rates from the database.

**Returns:** `Collection<CurrencyExchangeRate>` - Collection of all exchange rates

**Example:**
```php
$service = app(ExchangeRateServiceInterface::class);
$allRates = $service->getAllExchangeRates();
```

### HistoricalSupportExchangeRateServiceInterface

Extended contract for services supporting historical data.

#### Methods

##### `storeHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection`

Fetches and stores historical exchange rates from the API.

**Parameters:**
- `$currency_code` (string) - Base currency code (e.g., 'USD')
- `$date_time` (CarbonInterface) - Date for historical data

**Returns:** `Collection<CurrencyExchangeRateHistory>` - Collection of stored historical rates

**Example:**
```php
use Carbon\Carbon;

$service = app(HistoricalSupportExchangeRateServiceInterface::class);
$date = Carbon::parse('2023-01-15');
$rates = $service->storeHistoricalExchangeRates('USD', $date);
```

##### `getHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection`

Retrieves stored historical exchange rates from the database.

**Parameters:**
- `$currency_code` (string) - Base currency code (e.g., 'USD')
- `$date_time` (CarbonInterface) - Date for historical data

**Returns:** `Collection<CurrencyExchangeRateHistory>` - Collection of historical rates

**Example:**
```php
use Carbon\Carbon;

$service = app(HistoricalSupportExchangeRateServiceInterface::class);
$date = Carbon::parse('2023-01-15');
$rates = $service->getHistoricalExchangeRates('USD', $date);
```

##### `getHistoricalExchangeRate(string $currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory`

Retrieves a specific historical exchange rate from the database.

**Parameters:**
- `$currency_code` (string) - Base currency code (e.g., 'USD')
- `$target_currency_code` (string) - Target currency code (e.g., 'EUR')
- `$date_time` (CarbonInterface) - Date for historical data

**Returns:** `CurrencyExchangeRateHistory` - Historical exchange rate model

**Throws:** `ModelNotFoundException` - When rate is not found

**Example:**
```php
use Carbon\Carbon;

$service = app(HistoricalSupportExchangeRateServiceInterface::class);
$date = Carbon::parse('2023-01-15');

try {
    $rate = $service->getHistoricalExchangeRate('USD', 'EUR', $date);
    echo "USD to EUR on {$date->format('Y-m-d')}: {$rate->exchange_rate}\n";
} catch (ModelNotFoundException $e) {
    echo "Historical rate not found\n";
}
```

## Repository Interface

### CurrencyExchangeRateRepositoryInterface

Repository contract for database operations.

#### Current Exchange Rates Methods

##### `updateExchangeRates(string $base_currency_code, array $exchange_rates): bool`

Updates exchange rates for a single base currency.

**Parameters:**
- `$base_currency_code` (string) - Base currency code
- `$exchange_rates` (array) - Array of target currency codes and rates

**Returns:** `bool` - Success status

**Example:**
```php
$repository = app(CurrencyExchangeRateRepositoryInterface::class);

$rates = [
    'EUR' => 0.85,
    'GBP' => 0.73,
    'JPY' => 110.50
];

$success = $repository->updateExchangeRates('USD', $rates);
```

##### `updateBulkExchangeRates(array $exchange_rates): bool`

Updates exchange rates for multiple base currencies using DTOs.

**Parameters:**
- `$exchange_rates` (ExchangeRatesDto[]) - Array of ExchangeRatesDto objects

**Returns:** `bool` - Success status

**Example:**
```php
use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;

$repository = app(CurrencyExchangeRateRepositoryInterface::class);

$dtos = [
    new ExchangeRatesDto('USD', ['EUR' => 0.85, 'GBP' => 0.73]),
    new ExchangeRatesDto('EUR', ['USD' => 1.18, 'GBP' => 0.86])
];

$success = $repository->updateBulkExchangeRates($dtos);
```

##### `getAllExchangeRates(): Collection`

Retrieves all exchange rates from the database.

**Returns:** `Collection<CurrencyExchangeRate>` - All exchange rates

**Example:**
```php
$repository = app(CurrencyExchangeRateRepositoryInterface::class);
$allRates = $repository->getAllExchangeRates();
```

##### `getExchangeRates(string $base_currency_code): Collection`

Retrieves exchange rates for a specific base currency.

**Parameters:**
- `$base_currency_code` (string) - Base currency code

**Returns:** `Collection<CurrencyExchangeRate>` - Exchange rates for the base currency

**Example:**
```php
$repository = app(CurrencyExchangeRateRepositoryInterface::class);
$rates = $repository->getExchangeRates('USD');
```

##### `getBulkExchangeRates(array $base_currency_codes): Collection`

Retrieves exchange rates for multiple base currencies.

**Parameters:**
- `$base_currency_codes` (string[]) - Array of base currency codes

**Returns:** `Collection<string, Collection<CurrencyExchangeRate>>` - Exchange rates grouped by base currency

**Example:**
```php
$repository = app(CurrencyExchangeRateRepositoryInterface::class);
$rates = $repository->getBulkExchangeRates(['USD', 'EUR', 'GBP']);

foreach ($rates as $baseCurrency => $currencyRates) {
    echo "Rates for {$baseCurrency}:\n";
    foreach ($currencyRates as $rate) {
        echo "  {$rate->target_currency_code}: {$rate->exchange_rate}\n";
    }
}
```

##### `getExchangeRate(string $base_currency_code, string $target_currency_code): CurrencyExchangeRate`

Retrieves a specific exchange rate.

**Parameters:**
- `$base_currency_code` (string) - Base currency code
- `$target_currency_code` (string) - Target currency code

**Returns:** `CurrencyExchangeRate` - Exchange rate model

**Throws:** `ModelNotFoundException` - When rate is not found

**Example:**
```php
$repository = app(CurrencyExchangeRateRepositoryInterface::class);

try {
    $rate = $repository->getExchangeRate('USD', 'EUR');
    echo "USD to EUR: {$rate->exchange_rate}\n";
} catch (ModelNotFoundException $e) {
    echo "Exchange rate not found\n";
}
```

##### `getBulkExchangeRate(array $currencies_pairs): Collection`

Retrieves multiple specific exchange rates using DTOs.

**Parameters:**
- `$currencies_pairs` (CurrenciesPairDto[]) - Array of CurrenciesPairDto objects

**Returns:** `Collection<string, CurrencyExchangeRate>` - Exchange rates with currency pair keys

**Example:**
```php
use BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto;

$repository = app(CurrencyExchangeRateRepositoryInterface::class);

$pairs = [
    new CurrenciesPairDto('USD', 'EUR'),
    new CurrenciesPairDto('USD', 'GBP'),
    new CurrenciesPairDto('EUR', 'USD')
];

$rates = $repository->getBulkExchangeRate($pairs);

foreach ($rates as $pairKey => $rate) {
    echo "Rate for {$pairKey}: {$rate->exchange_rate}\n";
}
```

#### Historical Exchange Rates Methods

##### `updateExchangeRatesHistory(string $base_currency_code, array $exchange_rates, CarbonInterface $date_time): bool`

Updates historical exchange rates for a specific date.

**Parameters:**
- `$base_currency_code` (string) - Base currency code
- `$exchange_rates` (array) - Array of target currency codes and rates
- `$date_time` (CarbonInterface) - Date for historical data

**Returns:** `bool` - Success status

**Example:**
```php
use Carbon\Carbon;

$repository = app(CurrencyExchangeRateRepositoryInterface::class);

$date = Carbon::parse('2023-01-15');
$rates = [
    'EUR' => 0.82,
    'GBP' => 0.70,
    'JPY' => 108.50
];

$success = $repository->updateExchangeRatesHistory('USD', $rates, $date);
```

##### `updateBulkExchangeRatesHistory(array $historical_exchange_rates): bool`

Updates historical exchange rates for multiple dates using DTOs.

**Parameters:**
- `$historical_exchange_rates` (HistoricalExchangeRatesDto[]) - Array of HistoricalExchangeRatesDto objects

**Returns:** `bool` - Success status

**Example:**
```php
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use Carbon\Carbon;

$repository = app(CurrencyExchangeRateRepositoryInterface::class);

$dtos = [
    new HistoricalExchangeRatesDto('USD', ['EUR' => 0.82, 'GBP' => 0.70], Carbon::parse('2023-01-15')),
    new HistoricalExchangeRatesDto('EUR', ['USD' => 1.22, 'GBP' => 0.85], Carbon::parse('2023-01-15'))
];

$success = $repository->updateBulkExchangeRatesHistory($dtos);
```

##### `getHistoricalExchangeRates(string $base_currency_code, CarbonInterface $date_time): Collection`

Retrieves historical exchange rates for a specific date.

**Parameters:**
- `$base_currency_code` (string) - Base currency code
- `$date_time` (CarbonInterface) - Date for historical data

**Returns:** `Collection<CurrencyExchangeRateHistory>` - Historical exchange rates

**Example:**
```php
use Carbon\Carbon;

$repository = app(CurrencyExchangeRateRepositoryInterface::class);
$date = Carbon::parse('2023-01-15');
$rates = $repository->getHistoricalExchangeRates('USD', $date);
```

##### `getBulkHistoricalExchangeRates(array $historical_base_currencies): Collection`

Retrieves historical exchange rates for multiple base currencies and dates.

**Parameters:**
- `$historical_base_currencies` (HistoricalBaseCurrencyDto[]) - Array of HistoricalBaseCurrencyDto objects

**Returns:** `Collection<string, Collection<CurrencyExchangeRateHistory>>` - Historical rates grouped by base currency and date

**Example:**
```php
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use Carbon\Carbon;

$repository = app(CurrencyExchangeRateRepositoryInterface::class);

$dtos = [
    new HistoricalBaseCurrencyDto('USD', Carbon::parse('2023-01-15')),
    new HistoricalBaseCurrencyDto('EUR', Carbon::parse('2023-01-15')),
    new HistoricalBaseCurrencyDto('GBP', Carbon::parse('2023-01-16'))
];

$rates = $repository->getBulkHistoricalExchangeRates($dtos);

foreach ($rates as $baseKey => $currencyRates) {
    echo "Historical rates for {$baseKey}:\n";
    foreach ($currencyRates as $rate) {
        echo "  {$rate->target_currency_code}: {$rate->exchange_rate}\n";
    }
}
```

##### `getHistoricalExchangeRate(string $base_currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory`

Retrieves a specific historical exchange rate.

**Parameters:**
- `$base_currency_code` (string) - Base currency code
- `$target_currency_code` (string) - Target currency code
- `$date_time` (CarbonInterface) - Date for historical data

**Returns:** `CurrencyExchangeRateHistory` - Historical exchange rate model

**Throws:** `ModelNotFoundException` - When rate is not found

**Example:**
```php
use Carbon\Carbon;

$repository = app(CurrencyExchangeRateRepositoryInterface::class);
$date = Carbon::parse('2023-01-15');

try {
    $rate = $repository->getHistoricalExchangeRate('USD', 'EUR', $date);
    echo "USD to EUR on {$date->format('Y-m-d')}: {$rate->exchange_rate}\n";
} catch (ModelNotFoundException $e) {
    echo "Historical exchange rate not found\n";
}
```

##### `getBulkHistoricalExchangeRate(array $historical_currencies_pairs): Collection`

Retrieves multiple specific historical exchange rates using DTOs.

**Parameters:**
- `$historical_currencies_pairs` (HistoricalCurrenciesPairDto[]) - Array of HistoricalCurrenciesPairDto objects

**Returns:** `Collection<string, CurrencyExchangeRateHistory>` - Historical rates with currency pair and date keys

**Example:**
```php
use BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto;
use Carbon\Carbon;

$repository = app(CurrencyExchangeRateRepositoryInterface::class);

$pairs = [
    new HistoricalCurrenciesPairDto('USD', 'EUR', Carbon::parse('2023-01-15')),
    new HistoricalCurrenciesPairDto('USD', 'GBP', Carbon::parse('2023-01-15')),
    new HistoricalCurrenciesPairDto('EUR', 'USD', Carbon::parse('2023-01-16'))
];

$rates = $repository->getBulkHistoricalExchangeRate($pairs);

foreach ($rates as $pairKey => $rate) {
    echo "Historical rate for {$pairKey}: {$rate->exchange_rate}\n";
}
```

## DTOs

### ExchangeRatesDto

Data transfer object for current exchange rates.

#### Constructor

```php
public function __construct(
    protected string $base_currency_code,
    protected array $exchange_rates,
)
```

**Parameters:**
- `$base_currency_code` (string) - Base currency code
- `$exchange_rates` (array) - Array of target currency codes and rates

#### Methods

##### `getBaseCurrencyCode(): string`

Returns the base currency code.

**Returns:** `string` - Base currency code

##### `getExchangeRates(): array`

Returns the exchange rates array.

**Returns:** `array` - Exchange rates array

### HistoricalExchangeRatesDto

Data transfer object for historical exchange rates.

#### Constructor

```php
public function __construct(
    string $base_currency_code,
    array $exchange_rates,
    protected CarbonInterface $date_time,
)
```

**Parameters:**
- `$base_currency_code` (string) - Base currency code
- `$exchange_rates` (array) - Array of target currency codes and rates
- `$date_time` (CarbonInterface) - Date for historical data

#### Methods

##### `getDateTime(): CarbonInterface`

Returns the date time for historical data.

**Returns:** `CarbonInterface` - Date time

### CurrenciesPairDto

Data transfer object for currency pairs.

#### Constructor

```php
public function __construct(
    protected string $base_currency_code,
    protected string $target_currency_code,
)
```

**Parameters:**
- `$base_currency_code` (string) - Base currency code
- `$target_currency_code` (string) - Target currency code

#### Methods

##### `getBaseCurrencyCode(): string`

Returns the base currency code.

**Returns:** `string` - Base currency code

##### `getTargetCurrencyCode(): string`

Returns the target currency code.

**Returns:** `string` - Target currency code

##### `__toString(): string`

Returns string representation of the currency pair.

**Returns:** `string` - Currency pair string (e.g., 'USD_EUR')

### HistoricalCurrenciesPairDto

Data transfer object for historical currency pairs.

#### Constructor

```php
public function __construct(
    string $base_currency_code,
    string $target_currency_code,
    protected CarbonInterface $date_time,
)
```

**Parameters:**
- `$base_currency_code` (string) - Base currency code
- `$target_currency_code` (string) - Target currency code
- `$date_time` (CarbonInterface) - Date for historical data

#### Methods

##### `getDateTime(): CarbonInterface`

Returns the date time for historical data.

**Returns:** `CarbonInterface` - Date time

##### `__toString(): string`

Returns string representation of the historical currency pair.

**Returns:** `string` - Historical currency pair string (e.g., 'USD_EUR_2023-01-15')

### HistoricalBaseCurrencyDto

Data transfer object for historical base currencies.

#### Constructor

```php
public function __construct(
    protected string $base_currency_code,
    protected CarbonInterface $date_time,
)
```

**Parameters:**
- `$base_currency_code` (string) - Base currency code
- `$date_time` (CarbonInterface) - Date for historical data

#### Methods

##### `getBaseCurrencyCode(): string`

Returns the base currency code.

**Returns:** `string` - Base currency code

##### `getDateTime(): CarbonInterface`

Returns the date time for historical data.

**Returns:** `CarbonInterface` - Date time

##### `__toString(): string`

Returns string representation of the historical base currency.

**Returns:** `string` - Historical base currency string (e.g., 'USD_2023-01-15')

## Models

### CurrencyExchangeRate

Eloquent model for current exchange rates.

#### Properties

- `$fillable` (array) - Fillable properties
- `$table` (string) - Database table name
- `$timestamps` (bool) - Whether to use timestamps

#### Methods

##### `createOrUpdate($attributes)`

Creates or updates an exchange rate record.

**Parameters:**
- `$attributes` (array) - Model attributes

**Returns:** `CurrencyExchangeRate` - Model instance

### CurrencyExchangeRateHistory

Eloquent model for historical exchange rates.

#### Properties

- `$fillable` (array) - Fillable properties
- `$table` (string) - Database table name
- `$timestamps` (bool) - Whether to use timestamps

#### Methods

##### `createOrUpdate($attributes)`

Creates or updates a historical exchange rate record.

**Parameters:**
- `$attributes` (array) - Model attributes

**Returns:** `CurrencyExchangeRateHistory` - Model instance

## Traits

### CollectableResponse

Trait for handling API responses.

### TimeLoggable

Trait for logging execution time.

### EnumHelpers

Trait for enum helper methods.

## Related Documentation

- **[Installation & Configuration](installation.md)** - Setup instructions
- **[Services & Contracts](services.md)** - Service layer documentation
- **[Repository Pattern](repository.md)** - Database operations
- **[DTOs Guide](dtos.md)** - Data Transfer Objects
- **[Examples](examples.md)** - Practical usage examples 