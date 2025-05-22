# Exchange Rates Service Package

![Downloads](https://img.shields.io/github/downloads/BrightCreations/exchange-rates/total)
![License](https://img.shields.io/github/license/BrightCreations/exchange-rates)
![Last Commit](https://img.shields.io/github/last-commit/BrightCreations/exchange-rates)
![Stars](https://img.shields.io/github/stars/BrightCreations/exchange-rates?style=social)

## Overview
The Exchange Rates Service package provides a simple and efficient way to retrieve and manage exchange rates in your application. This package allows you to easily integrate exchange rate data into your project, making it ideal for e-commerce, financial, and other applications that require currency conversions.

## Features
- Retrieves exchange rates from a reliable data source
- Caches exchange rates for improved performance
- Provides a simple and intuitive API for accessing exchange rates
- Supports multiple currencies and conversion scenarios

## Installation
To install the Exchange Rates Service package, run the following command in your terminal:

```bash
composer require brightcreations/exchange-rates
```

## Configuration
To configure the package, publish the configuration file using the following command:

```bash
php artisan vendor:publish --provider="BrightCreations\ExchangeRates\ExchangeRatesServiceProvider"
```

Then, update the `exchange-rates.php` configuration file to suit your needs.

## Usage
To retrieve exchange rates, use the ExchangeRates facade:

```php
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;

// get exchange rates of USD with all other currencies as a laravel collection
$exchangeRates = $service->getExchangeRates('USD');
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
0.0.0
