# DTOs Guide

Data Transfer Objects (DTOs) are a crucial part of the Exchange Rates Library architecture. They provide type safety, clear data contracts, and ensure consistent data structure across the application.

## What are DTOs?

DTOs are simple objects that carry data between processes. In this library, they serve several purposes:

1. **Type Safety**: Ensure correct data structure
2. **Validation**: Provide a place for data validation
3. **Clarity**: Make method signatures self-documenting
4. **Extensibility**: Easy to extend without breaking existing code
5. **Testing**: Easier to mock and test

## DTO Hierarchy

The library provides a hierarchy of DTOs:

```
ExchangeRatesDto (Base)
├── HistoricalExchangeRatesDto (Extends ExchangeRatesDto)

CurrenciesPairDto (Base)
├── HistoricalCurrenciesPairDto (Extends CurrenciesPairDto)

HistoricalBaseCurrencyDto (Standalone)
```

## ExchangeRatesDto

The base DTO for current exchange rates.

### Structure

```php
class ExchangeRatesDto
{
    public function __construct(
        protected string $base_currency_code,
        protected array $exchange_rates,
    ) {}

    public function getBaseCurrencyCode(): string
    {
        return $this->base_currency_code;
    }

    public function getExchangeRates(): array
    {
        return $this->exchange_rates;
    }
}
```

### Usage

```php
use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;

// Create DTO for current exchange rates
$dto = new ExchangeRatesDto(
    base_currency_code: 'USD',
    exchange_rates: [
        'EUR' => 0.85,
        'GBP' => 0.73,
        'JPY' => 110.50,
        'CAD' => 1.25
    ]
);

// Access data
$baseCurrency = $dto->getBaseCurrencyCode(); // 'USD'
$rates = $dto->getExchangeRates(); // ['EUR' => 0.85, ...]

// Use in repository
$repository->updateBulkExchangeRates([$dto]);
```

### When to Use

- Bulk operations with current exchange rates
- Passing structured data to repository methods
- API responses that need type safety

## HistoricalExchangeRatesDto

Extends `ExchangeRatesDto` to include historical date information.

### Structure

```php
class HistoricalExchangeRatesDto extends ExchangeRatesDto
{
    public function __construct(
        string $base_currency_code,
        array $exchange_rates,
        protected CarbonInterface $date_time,
    ) {
        parent::__construct($base_currency_code, $exchange_rates);
    }

    public function getDateTime(): CarbonInterface
    {
        return $this->date_time;
    }
}
```

### Usage

```php
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use Carbon\Carbon;

// Create DTO for historical exchange rates
$dto = new HistoricalExchangeRatesDto(
    base_currency_code: 'USD',
    exchange_rates: [
        'EUR' => 0.82,
        'GBP' => 0.70,
        'JPY' => 108.50
    ],
    date_time: Carbon::parse('2023-01-15')
);

// Access data
$baseCurrency = $dto->getBaseCurrencyCode(); // 'USD'
$rates = $dto->getExchangeRates(); // ['EUR' => 0.82, ...]
$date = $dto->getDateTime(); // Carbon instance

// Use in repository
$repository->updateBulkExchangeRatesHistory([$dto]);
```

### When to Use

- Bulk operations with historical exchange rates
- Storing historical data with date information
- API responses for historical data

## CurrenciesPairDto

Represents a currency pair for querying specific exchange rates.

### Structure

```php
class CurrenciesPairDto
{
    public function __construct(
        protected string $base_currency_code,
        protected string $target_currency_code,
    ) {}

    public function getBaseCurrencyCode(): string
    {
        return $this->base_currency_code;
    }

    public function getTargetCurrencyCode(): string
    {
        return $this->target_currency_code;
    }

    public function __toString(): string
    {
        return $this->base_currency_code . '_' . $this->target_currency_code;
    }
}
```

### Usage

```php
use BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto;

// Create DTOs for currency pairs
$pairs = [
    new CurrenciesPairDto('USD', 'EUR'),
    new CurrenciesPairDto('USD', 'GBP'),
    new CurrenciesPairDto('EUR', 'USD'),
    new CurrenciesPairDto('GBP', 'EUR')
];

// Access individual currencies
foreach ($pairs as $pair) {
    $base = $pair->getBaseCurrencyCode(); // 'USD', 'USD', 'EUR', 'GBP'
    $target = $pair->getTargetCurrencyCode(); // 'EUR', 'GBP', 'USD', 'EUR'
    
    // String representation
    $pairString = (string) $pair; // 'USD_EUR', 'USD_GBP', 'EUR_USD', 'GBP_EUR'
}

// Use in repository
$rates = $repository->getBulkExchangeRate($pairs);
```

### When to Use

- Querying specific currency pairs
- Bulk operations on multiple currency pairs
- API requests for specific exchange rates

## HistoricalCurrenciesPairDto

Extends `CurrenciesPairDto` to include historical date information.

### Structure

```php
class HistoricalCurrenciesPairDto extends CurrenciesPairDto
{
    public function __construct(
        string $base_currency_code,
        string $target_currency_code,
        protected CarbonInterface $date_time,
    ) {
        parent::__construct($base_currency_code, $target_currency_code);
    }

    public function getDateTime(): CarbonInterface
    {
        return $this->date_time;
    }

    public function __toString(): string
    {
        return $this->base_currency_code . '_' . $this->target_currency_code . '_' . $this->date_time->format('Y-m-d');
    }
}
```

### Usage

```php
use BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto;
use Carbon\Carbon;

// Create DTOs for historical currency pairs
$historicalPairs = [
    new HistoricalCurrenciesPairDto('USD', 'EUR', Carbon::parse('2023-01-15')),
    new HistoricalCurrenciesPairDto('USD', 'GBP', Carbon::parse('2023-01-15')),
    new HistoricalCurrenciesPairDto('EUR', 'USD', Carbon::parse('2023-01-16'))
];

// Access data
foreach ($historicalPairs as $pair) {
    $base = $pair->getBaseCurrencyCode(); // 'USD', 'USD', 'EUR'
    $target = $pair->getTargetCurrencyCode(); // 'EUR', 'GBP', 'USD'
    $date = $pair->getDateTime(); // Carbon instances
    
    // String representation includes date
    $pairString = (string) $pair; // 'USD_EUR_2023-01-15', 'USD_GBP_2023-01-15', 'EUR_USD_2023-01-16'
}

// Use in repository
$rates = $repository->getBulkHistoricalExchangeRate($historicalPairs);
```

### When to Use

- Querying specific historical currency pairs
- Bulk operations on historical data
- API requests for historical exchange rates

## HistoricalBaseCurrencyDto

Represents a base currency with a specific date for historical queries.

### Structure

```php
class HistoricalBaseCurrencyDto
{
    public function __construct(
        protected string $base_currency_code,
        protected CarbonInterface $date_time,
    ) {}

    public function getBaseCurrencyCode(): string
    {
        return $this->base_currency_code;
    }

    public function getDateTime(): CarbonInterface
    {
        return $this->date_time;
    }

    public function __toString(): string
    {
        return $this->base_currency_code . '_' . $this->date_time->format('Y-m-d');
    }
}
```

### Usage

```php
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use Carbon\Carbon;

// Create DTOs for historical base currencies
$historicalBaseCurrencies = [
    new HistoricalBaseCurrencyDto('USD', Carbon::parse('2023-01-15')),
    new HistoricalBaseCurrencyDto('EUR', Carbon::parse('2023-01-15')),
    new HistoricalBaseCurrencyDto('GBP', Carbon::parse('2023-01-16'))
];

// Access data
foreach ($historicalBaseCurrencies as $dto) {
    $base = $dto->getBaseCurrencyCode(); // 'USD', 'EUR', 'GBP'
    $date = $dto->getDateTime(); // Carbon instances
    
    // String representation
    $dtoString = (string) $dto; // 'USD_2023-01-15', 'EUR_2023-01-15', 'GBP_2023-01-16'
}

// Use in repository
$rates = $repository->getBulkHistoricalExchangeRates($historicalBaseCurrencies);
```

### When to Use

- Querying all exchange rates for a base currency on a specific date
- Bulk operations on historical base currencies
- API requests for historical base currency data

## DTO Validation

While the current DTOs don't include built-in validation, you can extend them to add validation:

```php
class ValidatedExchangeRatesDto extends ExchangeRatesDto
{
    public function __construct(string $base_currency_code, array $exchange_rates)
    {
        // Validate base currency code
        if (!preg_match('/^[A-Z]{3}$/', $base_currency_code)) {
            throw new \InvalidArgumentException('Base currency code must be 3 uppercase letters');
        }

        // Validate exchange rates
        foreach ($exchange_rates as $currency => $rate) {
            if (!preg_match('/^[A-Z]{3}$/', $currency)) {
                throw new \InvalidArgumentException("Invalid currency code: {$currency}");
            }
            if (!is_numeric($rate) || $rate <= 0) {
                throw new \InvalidArgumentException("Invalid exchange rate for {$currency}: {$rate}");
            }
        }

        parent::__construct($base_currency_code, $exchange_rates);
    }
}
```

## DTO Factory Pattern

For complex DTO creation, consider using a factory:

```php
class ExchangeRatesDtoFactory
{
    public static function fromApiResponse(array $response): ExchangeRatesDto
    {
        return new ExchangeRatesDto(
            base_currency_code: $response['base_code'],
            exchange_rates: $response['conversion_rates']
        );
    }

    public static function fromHistoricalApiResponse(array $response, CarbonInterface $date): HistoricalExchangeRatesDto
    {
        return new HistoricalExchangeRatesDto(
            base_currency_code: $response['base_code'],
            exchange_rates: $response['conversion_rates'],
            date_time: $date
        );
    }
}

// Usage
$dto = ExchangeRatesDtoFactory::fromApiResponse($apiResponse);
```

## Best Practices

### 1. Use DTOs for Bulk Operations

```php
// Good: Using DTOs for bulk operations
$dtos = [
    new ExchangeRatesDto('USD', ['EUR' => 0.85, 'GBP' => 0.73]),
    new ExchangeRatesDto('EUR', ['USD' => 1.18, 'GBP' => 0.86])
];
$repository->updateBulkExchangeRates($dtos);

// Avoid: Passing raw arrays
$repository->updateExchangeRates('USD', ['EUR' => 0.85]);
$repository->updateExchangeRates('EUR', ['USD' => 1.18]);
```

### 2. Use Type Hints

```php
// Good: Type hinting with DTOs
public function processExchangeRates(ExchangeRatesDto $dto): void
{
    $baseCurrency = $dto->getBaseCurrencyCode();
    $rates = $dto->getExchangeRates();
    // Process data...
}

// Avoid: Using arrays
public function processExchangeRates(array $data): void
{
    $baseCurrency = $data['base_currency_code'] ?? null;
    $rates = $data['exchange_rates'] ?? [];
    // Process data...
}
```

### 3. Use String Representations

```php
// Good: Using string representations for keys
$pairs = [
    new CurrenciesPairDto('USD', 'EUR'),
    new CurrenciesPairDto('USD', 'GBP')
];

$rates = $repository->getBulkExchangeRate($pairs);
// Returns: ['USD_EUR' => $rate1, 'USD_GBP' => $rate2]

foreach ($rates as $pairKey => $rate) {
    echo "Rate for {$pairKey}: {$rate->exchange_rate}\n";
}
```

### 4. Extend DTOs Carefully

```php
// Good: Extending with additional functionality
class ValidatedExchangeRatesDto extends ExchangeRatesDto
{
    public function validate(): bool
    {
        // Add validation logic
        return true;
    }
}

// Avoid: Changing existing method signatures
class BadExchangeRatesDto extends ExchangeRatesDto
{
    public function getBaseCurrencyCode(): int // Wrong! Changes return type
    {
        return (int) parent::getBaseCurrencyCode();
    }
}
```

## Testing DTOs

```php
use PHPUnit\Framework\TestCase;

class ExchangeRatesDtoTest extends TestCase
{
    public function test_exchange_rates_dto_creation()
    {
        $dto = new ExchangeRatesDto('USD', ['EUR' => 0.85]);

        $this->assertEquals('USD', $dto->getBaseCurrencyCode());
        $this->assertEquals(['EUR' => 0.85], $dto->getExchangeRates());
    }

    public function test_historical_exchange_rates_dto_creation()
    {
        $date = Carbon::parse('2023-01-15');
        $dto = new HistoricalExchangeRatesDto('USD', ['EUR' => 0.85], $date);

        $this->assertEquals('USD', $dto->getBaseCurrencyCode());
        $this->assertEquals(['EUR' => 0.85], $dto->getExchangeRates());
        $this->assertEquals($date, $dto->getDateTime());
    }

    public function test_currencies_pair_dto_string_representation()
    {
        $dto = new CurrenciesPairDto('USD', 'EUR');
        
        $this->assertEquals('USD_EUR', (string) $dto);
    }
}
```

## Related Documentation

- **[Repository Pattern](repository.md)** - How DTOs are used in repository operations
- **[Services & Contracts](services.md)** - Service layer documentation
- **[API Reference](api-reference.md)** - Complete method documentation
- **[Examples](examples.md)** - Practical usage examples 