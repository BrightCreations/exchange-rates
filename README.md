# Exchange Rates Library

![Downloads](https://img.shields.io/github/downloads/BrightCreations/exchange-rates/total)
![License](https://img.shields.io/github/license/BrightCreations/exchange-rates)
![Last Commit](https://img.shields.io/github/last-commit/BrightCreations/exchange-rates)
![Stars](https://img.shields.io/github/stars/BrightCreations/exchange-rates?style=social)
![Tests](https://img.shields.io/github/actions/workflow/status/BrightCreations/exchange-rates/tests.yml?label=tests)

A comprehensive Laravel package for fetching, storing, and managing exchange rates from various external APIs. This library provides a clean, extensible architecture for handling currency exchange rates with support for both current and historical data.

## 🚀 Features

- **Multiple API Support**: Built-in support for Exchange Rate API and Open Exchange Rates
- **Historical Data**: Store and retrieve historical exchange rates
- **Bulk Operations**: Efficient bulk operations for multiple currencies
- **Database Storage**: Automatic storage and caching of exchange rates
- **Extensible Architecture**: Easy to add new exchange rate providers
- **DTO Pattern**: Clean data transfer objects for type-safe operations
- **Repository Pattern**: Clean separation between data access and business logic

## 📦 Installation

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
# Exchange Rate API (Default)
EXCHANGE_RATE_API_TOKEN=your_api_token_here
EXCHANGE_RATE_API_VERSION=v6
EXCHANGE_RATE_API_BASE_URL=https://v6.exchangerate-api.com/v6/

# Open Exchange Rates (Alternative)
OPEN_EXCHANGE_RATE_BASE_URL=https://openexchangerates.org/api/
OPEN_EXCHANGE_RATE_APP_ID=your_app_id_here
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
> The facades are the recommended and most convenient way for most use cases.

## 🔌 Supported APIs

### Exchange Rate API
- **Provider**: [Exchange Rate API](https://www.exchangerate-api.com/)
- **Features**: Current and historical rates
- **Default Service**: Yes

### Open Exchange Rates
- **Provider**: [Open Exchange Rates](https://openexchangerates.org/)
- **Features**: Current and historical rates
- **Default Service**: No (can be configured)

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
- **Company**: Bright Creations

---

**Made with ❤️ by Bright Creations**
