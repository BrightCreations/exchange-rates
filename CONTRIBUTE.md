# Contributing to Exchange Rates Package

Thank you for your interest in contributing to the Exchange Rates package! This guide will help you get started with setting up your development environment and running tests.

## Prerequisites

Before you begin, ensure you have the following installed on your system:

- **PHP 8.1 or higher** - Check your version with `php -v`
- **Composer** - PHP dependency manager
- **SQLite3** - Required for running tests

### Installing System Dependencies

#### SQLite3 (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install -y php8.2-sqlite3
```

Replace `8.2` with your PHP version if different. Verify the installation:

```bash
php -m | grep -i sqlite
```

You should see `pdo_sqlite` and `sqlite3` in the output.

#### SQLite3 (macOS)

```bash
brew install php
# SQLite is usually included with PHP on macOS
```

#### SQLite3 (Windows)

SQLite support is typically included with PHP on Windows. If not, you may need to enable it in your `php.ini` file:

```ini
extension=pdo_sqlite
extension=sqlite3
```

## Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/brightcreations/exchange-rates.git
   cd exchange-rates
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Set up environment variables**

   Copy the example environment file to create your own:

   ```bash
   cp .env.example .env
   ```

4. **Configure API keys (optional for basic tests)**

   Edit the `.env` file and add your API keys if you want to test against live APIs:

   ```env
   EXCHANGE_RATE_API_TOKEN=your_api_key_here
   EXCHANGE_RATE_API_VERSION=v6
   EXCHANGE_RATE_API_BASE_URL=https://v6.exchangerate-api.com/v6/

   OPEN_EXCHANGE_RATE_BASE_URL=https://openexchangerates.org/api/
   OPEN_EXCHANGE_RATE_APP_ID=your_app_id_here
   ```

   > **Note:** Most tests use mocked responses, so API keys are not required for basic testing.

## Running Tests

The project uses [Pest PHP](https://pestphp.com/) for testing.

### Run all tests

```bash
composer test
```

Or alternatively:

```bash
./vendor/bin/pest
```

### Run specific test files

```bash
./vendor/bin/pest tests/Unit/ExchangeRateApiServiceTest.php
```

### Run tests with coverage (if you have Xdebug installed)

```bash
./vendor/bin/pest --coverage
```

### Run tests in parallel

```bash
./vendor/bin/pest --parallel
```

## Development Workflow

1. **Create a new branch** for your feature or bug fix:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** and ensure they follow the existing code style

3. **Write tests** for your changes

4. **Run tests** to make sure everything passes:
   ```bash
   composer test
   ```

5. **Commit your changes** with clear, descriptive commit messages

6. **Push your branch** and create a Pull Request

## Troubleshooting

### "could not find driver" error

This means SQLite PDO extension is not installed. Follow the [Installing System Dependencies](#installing-system-dependencies) section above.

### Tests fail with connection errors

Ensure your `.env` file is properly configured and SQLite is installed.

### Permission issues on Linux

If you encounter permission issues, you may need to adjust file permissions:

```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## Code Style

- Follow PSR-12 coding standards
- Write clear, descriptive variable and method names
- Add PHPDoc comments for classes and methods
- Keep methods focused and single-purpose

## Questions?

If you have any questions or run into issues, please:

- Check existing [Issues](https://github.com/brightcreations/exchange-rates/issues)
- Open a new issue if your problem hasn't been addressed
- Reach out to the maintainers at kareem.shaaban@brightcreations.com

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

