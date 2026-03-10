# Exchange Rates Library

![Downloads](https://img.shields.io/github/downloads/BrightCreations/exchange-rates/total)
![License](https://img.shields.io/github/license/BrightCreations/exchange-rates)
![Last Commit](https://img.shields.io/github/last-commit/BrightCreations/exchange-rates)
![Stars](https://img.shields.io/github/stars/BrightCreations/exchange-rates?style=social)
![Tests](https://img.shields.io/github/actions/workflow/status/BrightCreations/exchange-rates/tests.yml?label=tests)

A comprehensive Laravel package for fetching, storing, and managing exchange rates from various external APIs. This library provides a clean, extensible architecture for handling currency exchange rates with support for both current and historical data.

## 🚀 Features

- **Multiple API Support**: Built-in support for Exchange Rate API, Open Exchange Rates, and World Bank
- **Automatic Fallback**: Intelligent fallback mechanism that tries services in order until one succeeds
- **Historical Data**: Store and retrieve historical exchange rates
- **Bulk Operations**: Efficient bulk operations for multiple currencies
- **Database Storage**: Automatic storage and caching of exchange rates
- **Smart Caching**: Automatic caching for World Bank yearly data
- **Extensible Architecture**: Easy to add new exchange rate providers
- **DTO Pattern**: Clean data transfer objects for type-safe operations
- **Repository Pattern**: Clean separation between data access and business logic

## 📦 Installation:-

### 1. Install via Composer

```bash
composer require brightcreations/exchange-rates
```

### 2. Publish Configuration and Migrations

```bash
# Publish configuration file
php artisan vendor:publish --tag=exchange-rates-config

# Publish migrations
php artisan vendor:publish --tag=exchange-rate-migrations

# Run migrations
php artisan migrate
```

### 3. Configure Environment Variables

Add the following to your `.env` file:

```env
# Exchange Rate API
EXCHANGE_RATE_API_TOKEN=your_api_token_here
EXCHANGE_RATE_API_VERSION=v6
EXCHANGE_RATE_API_BASE_URL=https://v6.exchangerate-api.com/v6/

# Open Exchange Rates
OPEN_EXCHANGE_RATE_BASE_URL=https://openexchangerates.org/api/
OPEN_EXCHANGE_RATE_APP_ID=your_app_id_here

# World Bank (No API key required - Free fallback service)
WORLD_BANK_EXCHANGE_RATE_BASE_URL=https://api.worldbank.org/v2
```

## 🏗️ Architecture Overview

This library follows a clean, layered architecture:

- **Services**: Handle API communication and business logic
- **Repositories**: Manage database operations
- **DTOs**: Type-safe data transfer objects
- **Contracts**: Define interfaces for extensibility
- **Models**: Eloquent models for database entities

## 📚 Documentation

- **[Installation & Configuration](docs/installation.md)** - Detailed setup instructions
- **[Services & Contracts](docs/services.md)** - Understanding the service layer and contracts
- **[Repository Pattern](docs/repository.md)** - Database operations and DTO usage
- **[DTOs Guide](docs/dtos.md)** - Data Transfer Objects explained
- **[API Reference](docs/api-reference.md)** - Complete method documentation
- **[Examples](docs/examples.md)** - Practical usage examples

## 🔧 Quick Start

### Basic Usage

```php
use BrightCreations\ExchangeRates\Facades\ExchangeRate;

class CurrencyController extends Controller
{
    public function getRates(string $currency = 'USD')
    {
        // Store and retrieve current exchange rates
        $rates = ExchangeRate::storeExchangeRates($currency);

        return response()->json($rates);
    }
}
```

### Historical Data

```php
use BrightCreations\ExchangeRates\Facades\HistoricalExchangeRate;
use Carbon\Carbon;

class HistoricalController extends Controller
{
    public function getHistoricalRates(string $currency, string $date)
    {
        $dateTime = Carbon::parse($date);

        $rates = HistoricalExchangeRate::getHistoricalExchangeRates($currency, $dateTime);

        return response()->json($rates);
    }
}
```

### Repository Usage

```php
use BrightCreations\ExchangeRates\Facades\ExchangeRateRepository;

$repository = ExchangeRateRepository::getExchangeRate('USD', 'EUR');
```

> **Note:** You can also use Laravel's `resolve()` or `app()` helpers to access the services directly:
>
> ```php
> $service = resolve(ExchangeRateServiceInterface::class);
> $service = app(ExchangeRateServiceInterface::class);
> ```
>
> The facades are the recommended and most convenient way for most use cases.

## 🔌 Supported APIs

The library uses an intelligent fallback mechanism. By default, it tries services in this order:

1. **Exchange Rate API** (primary)
2. **Open Exchange Rates** (secondary)
3. **World Bank** (tertiary fallback)

### Exchange Rate API

- **Provider**: [Exchange Rate API](https://www.exchangerate-api.com/)
- **Features**: Current and historical rates, real-time updates
- **Requires**: API Token
- **Cost**: Free tier available

### Open Exchange Rates

- **Provider**: [Open Exchange Rates](https://openexchangerates.org/)
- **Features**: Current and historical rates, real-time updates
- **Requires**: App ID
- **Cost**: Free tier available

### World Bank Exchange Rate API

- **Provider**: [World Bank Open Data](https://api.worldbank.org/)
- **Features**: Historical yearly average rates
- **Requires**: No API key (free and open)
- **Cost**: Free
- **Data**: Yearly averages (less precise than real-time services)
- **Coverage**: ~160+ currencies mapped from country data
- **Caching**: 24-hour cache for efficiency

> **Note on World Bank Data**: The World Bank service provides yearly average exchange rates based on country-level data. While less precise than real-time APIs, it serves as an excellent free fallback option. Exchange rates are computed by:
>
> 1. Fetching LCU (Local Currency Unit) per USD rates by country
> 2. Mapping countries to currencies using [pragmarx/countries](https://github.com/antonioribeiro/countries)
> 3. Computing cross-currency rates from USD-anchored values
>
> **Limitations**:
>
> - Yearly averages only (not daily/real-time)
> - Some currencies may not be available if country mapping fails
> - Aggregate regions (like "Euro Area") are filtered out automatically

## 🌐 Built-in HTTP Endpoints

The package ships with a default read-only REST endpoint that returns exchange rates already stored in the database. It does **not** call any external provider; use `storeExchangeRates(...)` (via Artisan commands or your own code) to populate the data first.

### Enabling / Disabling

Routes are **enabled by default**. To turn them off, publish the config and set:

```php
// config/exchange-rates.php
'routes' => [
    'enabled' => false,
],
```

### Configuration

```php
'routes' => [
    'enabled'    => true,
    'prefix'     => 'exchange-rates',   // Package-specific segment appended after 'api/'
    'middleware' => ['api'],            // Middleware applied to the route group
],
```

The final URL prefix is always `api/{prefix}`, so the default endpoint resolves to `/api/exchange-rates/{currency}`. Changing `prefix` to e.g. `rates` would produce `/api/rates/{currency}`.

### Get Exchange Rates

```
GET /api/exchange-rates/{currency}
```

| Parameter  | Location    | Required | Description                                                                                 |
|------------|-------------|----------|---------------------------------------------------------------------------------------------|
| `currency` | path        | yes      | ISO 4217 base currency code (3 letters, e.g. `USD`). Case-insensitive.                      |
| `targets`  | query string | no       | Comma-separated list of target currency codes to filter by (e.g. `EUR,GBP,SAR`). If omitted, all stored target currencies are returned. |

#### Example — all targets

```bash
GET /api/exchange-rates/USD
```

```json
{
    "data": {
        "base_currency": "USD",
        "rates": [
            { "target_currency": "EUR", "rate": "0.9200000000", "last_updated": "2026-03-09T00:00:00.000000Z" },
            { "target_currency": "GBP", "rate": "0.7800000000", "last_updated": "2026-03-09T00:00:00.000000Z" }
        ]
    }
}
```

#### Example — filtered targets

```bash
GET /api/exchange-rates/USD?targets=EUR,SAR
```

```json
{
    "data": {
        "base_currency": "USD",
        "rates": [
            { "target_currency": "EUR", "rate": "0.9200000000", "last_updated": "2026-03-09T00:00:00.000000Z" },
            { "target_currency": "SAR", "rate": "3.7500000000", "last_updated": "2026-03-09T00:00:00.000000Z" }
        ]
    }
}
```

#### Responses

| Status | Meaning                                                             |
|--------|---------------------------------------------------------------------|
| 200    | Rates returned. `rates` is an empty array when nothing is stored.  |
| 422    | Validation error — invalid currency code format.                   |

#### Reversed mode

Add `?reversed=true` to flip the lookup: the path `{currency}` becomes the **target** currency. The endpoint returns all stored source (base) currencies that have a rate to this target, with each rate inverted (`1 / stored_rate`) using precise decimal arithmetic. Use `sources` to filter which base currencies are included.

| Parameter  | Location     | Required | Description                                                                                    |
|------------|--------------|----------|------------------------------------------------------------------------------------------------|
| `reversed` | query string | no       | Set to `true` to treat the path currency as the target and return inverted rates.              |
| `sources`  | query string | no       | Comma-separated base currency codes to filter by (e.g. `USD,GBP`). Only used when `reversed=true`. |

```bash
GET /api/exchange-rates/EUR?reversed=true
```

```json
{
    "data": {
        "target_currency": "EUR",
        "rates": [
            { "source_currency": "USD", "rate": "1.0869565217", "last_updated": "2026-03-09T00:00:00.000000Z" },
            { "source_currency": "GBP", "rate": "0.8474576271", "last_updated": "2026-03-09T00:00:00.000000Z" }
        ]
    }
}
```

```bash
GET /api/exchange-rates/EUR?reversed=true&sources=USD,SAR
```

```json
{
    "data": {
        "target_currency": "EUR",
        "rates": [
            { "source_currency": "USD", "rate": "1.0869565217", "last_updated": "2026-03-09T00:00:00.000000Z" },
            { "source_currency": "SAR", "rate": "4.0765593966", "last_updated": "2026-03-09T00:00:00.000000Z" }
        ]
    }
}
```

---

## 🔄 Fallback Configuration

You can customize the fallback order in `config/exchange-rates.php`:

```php
'fallback_order' => [
    ExchangeRateApiService::class,
    OpenExchangeRateService::class,
    WorldBankExchangeRateApiService::class,
],
```

Or use a specific service directly:

```php
use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;

$worldBankService = app(WorldBankExchangeRateApiService::class);
$rates = $worldBankService->storeExchangeRates('EUR');
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, please contact:

- **Email**: kareem.shaaban@brightcreations.com
- **Developer at**: DAZU DPN
- **Company**: Bright Creations

---

**Made with ❤️ by Bright Creations:**
