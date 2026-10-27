# CLAUDE.md

This file provides guidance to Claude Code when working in this repository.

## Project

`brightcreations/exchange-rates` is a Laravel package (not a standalone app) for fetching,
storing, and serving currency exchange rates from multiple external providers, with automatic
fallback, database caching, and historical-rate support. It is a Composer dependency consumed
by DAZU apps (currently `dazu-core`), not deployed on its own.

- **PHP**: `>=8.1`
- **Laravel**: `illuminate/support|database|http|console` `^9.0|^10.0|^11.0|^12.0|^13.0` (framework
  version-agnostic within that range; auto-discovered via `extra.laravel.providers` in
  `composer.json`). Laravel 13 needs PHP 8.3+ and brings Guzzle 8 and `brick/math` 1.0.
- **`brick/math`**: declared directly (`>=0.11 <2.0`). 1.0 no longer accepts floats in `BigDecimal::of()`
  (PHP truncates `0.83` to `0`), so every rate goes through `CurrencyExchangeRateRepository::toBigDecimal()`,
  which casts floats to string. Don't call `BigDecimal::of()` on a provider rate directly.
- **Public API entry points**:
  - `BrightCreations\ExchangeRates\Facades\ExchangeRate` — current-rate operations
    (`storeExchangeRates()`, `storeBulkExchangeRatesForMultipleCurrencies()`,
    `getExchangeRates()`, `getAllExchangeRates()`, `supportsHistoricalApiFetch()`,
    `isSupportHistoricalExchangeRate()`)
  - `BrightCreations\ExchangeRates\Facades\HistoricalExchangeRate` — historical-rate operations
  - `BrightCreations\ExchangeRates\Facades\ExchangeRateRepository` — direct DB reads (e.g.
    `getExchangeRate('USD', 'EUR')`)
  - Or resolve `ExchangeRateServiceInterface` / `HistoricalSupportExchangeRateServiceInterface`
    from the container directly (`src/Contracts/`)
  - Built-in read-only REST endpoints (`routes/api.php`, controllers in `src/Http/Controllers`):
    `GET /api/exchange-rates/{currency}` (optional `reversed=true`, `currencies=` filter)

## Commands

```bash
composer install          # install dependencies
composer test              # ./vendor/bin/pest — Pest test suite (tests/Unit, tests/Feature)
composer pint               # ./vendor/bin/pint — Laravel Pint code style fixer
```

No static analysis tool (PHPStan/Psalm) is configured. Tests use Orchestra Testbench
(`tests/TestCase.php`) with an in-memory sqlite DB (`phpunit.xml`).

## Architecture

- **Contracts** (`src/Contracts/`): `ExchangeRateServiceInterface` (current rates),
  `HistoricalSupportExchangeRateServiceInterface` (historical rates), plus
  `BaseExchangeRateService` and `Repositories/CurrencyExchangeRateRepositoryInterface`.
- **Concretes** (`src/Concretes/`): one class per provider —
  `OpenExchangeRateService`, `ExchangeRateApiService`, `WorldBankExchangeRateApiService` — plus
  `FallbackExchangeRateService`, which tries each in the configured order until one succeeds.
  Adding a new provider means implementing the contracts and adding it to `fallback_order`.
- **Repositories** (`src/Concretes/Repositories/CurrencyExchangeRateRepository.php`): all DB
  reads/writes against the `Models\CurrencyExchangeRate` / `CurrencyExchangeRateHistory`
  Eloquent models; migrations live in `src/Database/Migrations` and are auto-loaded plus
  publishable.
- **DTOs** (`src/DTOs/`): typed transfer objects (`ExchangeRatesDto`, `CurrenciesPairDto`,
  `HistoricalExchangeRatesDto`, etc.) returned/consumed by services and repositories instead of
  raw arrays.
- **Service provider** (`src/ExchangeRatesServiceProvider.php`): publishes config
  (`exchange-rates-config` tag) and migrations (`exchange-rate-migrations` tag), binds
  `ExchangeRateServiceInterface` / `HistoricalSupportExchangeRateServiceInterface` to whatever
  class `config('exchange-rates.default_service')` names (default
  `FallbackExchangeRateService`), registers the `exchange-rates:backfill` and
  `exchange-rates:migrate` Artisan commands (`src/Console/Commands/`), and conditionally
  registers the HTTP routes.
- **Consumer usage pattern**: a consuming app (e.g. `dazu-core`) requires the package via
  Composer, publishes config/migrations, sets provider credentials in `.env`, then calls the
  facades or resolves the interfaces via `app()`/`resolve()` — no manual service instantiation
  needed. See `README.md` "Quick Start" and `docs/examples.md`.

## Conventions worth knowing

- **Fallback order** is configured in `config/exchange-rates.php` (`fallback_order`,
  `default_service`); default order is Open Exchange Rates → Exchange Rate API → World Bank.
- **Historical fetch behavior differs per provider**: `OpenExchangeRateService` and
  `ExchangeRateApiService` auto-fetch-and-store on a DB miss; `WorldBankExchangeRateApiService`
  is DB-only for historical data and must be pre-populated with
  `php artisan exchange-rates:backfill`. Check capability at runtime via
  `ExchangeRate::supportsHistoricalApiFetch()` / `isSupportHistoricalExchangeRate()`.
- **World Bank provider** returns yearly averages only (not daily), derives cross-rates from
  USD-anchored LCU-per-USD country data via `pragmarx/countries`, filters out aggregate regions
  (e.g. "Euro Area"), and caches responses for 24 hours.
- **Currency codes**: ISO 4217, 3-letter, case-insensitive at the HTTP layer.
- **Built-in HTTP endpoints are read-only** — they serve whatever is already stored in the DB
  and never call an external provider directly; populate data first via `storeExchangeRates()`
  or the Artisan commands. Routes are enabled by default (`config('exchange-rates.routes')`) and
  always mount at `api/{prefix}/...`.
- **Errors**: unresolvable/misconfigured `default_service` bindings throw `RuntimeException` at
  container-resolution time if the configured class doesn't implement the expected interface
  (see `ExchangeRatesServiceProvider::register()`).
- Recommended companion package for currency *conversion* (as opposed to rate fetching/storage)
  is `brightcreations/money-converter`, which reads the same `currency_exchange_rates` tables
  this package writes; version compatibility is pinned (money-converter `^0.6.0` needs
  exchange-rates `^0.8.0`).

## Ecosystem

Part of the DAZU fintech ecosystem's 8 sibling repos. This package is a Composer dependency
consumed by DAZU Core (`../dazu-core`, required as `brightcreations/exchange-rates`) and
potentially other DAZU services needing currency rates. Each sibling has its own CLAUDE.md:

- `../dazu-core` — main DAZU app; consumes this package
- `../dazu-id-api` — identity API
- `../shikaty-core` — Shikaty app
- `../dazu-wealth-api` — wealth API
- `../money-converter` — closely related sibling package (currency conversion, reads this
  package's DB tables)
- `../dazu-orchestrator` — orchestration service
- `../dazu-infra` — infrastructure/IaC
- `../cicd-scripts` — shared CI/CD scripts
