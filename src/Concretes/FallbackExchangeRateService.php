<?php

namespace BrightCreations\ExchangeRates\Concretes;

use BrightCreations\ExchangeRates\Contracts\BaseExchangeRateService;
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRate;
use BrightCreations\ExchangeRates\Models\CurrencyExchangeRateHistory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * FallbackExchangeRateService provides a fallback mechanism for exchange rate services.
 * It tries multiple services in order until one succeeds.
 * All operations — both store and read — are routed through the configured fallback services;
 * each individual service is responsible for its own repository calls. The only exception is
 * the historical read-through getters, which check the database once up front so that a
 * temporarily suspended provider (see `fallback_suspension` config) never blocks a stored rate.
 *
 * A provider that reports an exhausted quota or rejected credentials is skipped for
 * `fallback_suspension.unavailable_seconds`; one whose plan rejects historical requests is
 * skipped for historical operations for `fallback_suspension.historical_unsupported_seconds`.
 *
 * @author Bright Creations <kareem.shaaban@brightcreations.com>
 * @license MIT
 */
class FallbackExchangeRateService extends BaseExchangeRateService implements ExchangeRateServiceInterface, HistoricalSupportExchangeRateServiceInterface
{
    /**
     * Operation scopes used for provider suspensions.
     */
    private const SCOPE_LATEST = 'latest';

    private const SCOPE_HISTORICAL = 'historical';

    /**
     * Provider error codes (ExchangeRate-API `error-type`, Open Exchange Rates `message`) meaning
     * every request will keep failing until the quota resets or the credentials are fixed.
     */
    private const UNAVAILABLE_ERROR_CODES = [
        'quota-reached',
        'invalid-key',
        'inactive-account',
        'access_restricted',
        'invalid_app_id',
        'missing_app_id',
    ];

    /**
     * Provider error codes meaning the current plan does not include historical rates.
     */
    private const HISTORICAL_UNSUPPORTED_ERROR_CODES = [
        'plan-upgrade-required',
    ];

    /**
     * List of service classes to try in order
     *
     * @var array<class-string<ExchangeRateServiceInterface>>
     */
    private array $fallbackServices;

    /**
     * Current active service instance
     */
    private ?ExchangeRateServiceInterface $currentService = null;

    public function __construct()
    {
        // Get fallback order from config
        $this->fallbackServices = Config::get('exchange-rates.fallback_order', [
            ExchangeRateApiService::class,
            OpenExchangeRateService::class,
            WorldBankExchangeRateApiService::class,
        ]);
    }

    /**
     * Try services in fallback order until one succeeds.
     *
     * @param  callable  $operation  The operation to perform: function(ExchangeRateServiceInterface $service): mixed
     * @param  string  $operationName  Name of the operation for logging
     * @param  string|null  $scope  SCOPE_LATEST / SCOPE_HISTORICAL for operations that may call a provider API
     *                              (suspended providers are skipped and new suspensions recorded); null for
     *                              database-only reads, which always try every service
     * @return mixed
     *
     * @throws \RuntimeException If all services fail
     */
    private function tryWithFallback(callable $operation, string $operationName, ?string $scope = null)
    {
        $lastException = null;
        $skippedServices = [];

        foreach ($this->fallbackServices as $serviceClass) {
            if ($scope !== null && $this->isSuspended($serviceClass, $scope)) {
                Log::info("FallbackExchangeRateService: Skipping {$serviceClass} for {$operationName}: temporarily suspended");
                $skippedServices[] = $serviceClass;

                continue;
            }

            try {
                // Resolve the service from the container
                $service = app()->make($serviceClass);

                if (! ($service instanceof ExchangeRateServiceInterface)) {
                    continue;
                }

                // Try the operation
                Log::info("FallbackExchangeRateService: Trying {$serviceClass} for {$operationName}");
                $result = $operation($service);

                // Check if result is empty/null
                if ($result instanceof Collection && $result->isEmpty()) {
                    Log::warning("FallbackExchangeRateService: {$serviceClass} returned empty result for {$operationName}");

                    continue;
                }

                if ($result === null || $result === false) {
                    Log::warning("FallbackExchangeRateService: {$serviceClass} returned null/false for {$operationName}");

                    continue;
                }

                // Success!
                Log::info("FallbackExchangeRateService: {$serviceClass} succeeded for {$operationName}");
                $this->currentService = $service;

                return $result;
            } catch (\Exception $e) {
                Log::warning("FallbackExchangeRateService: {$serviceClass} failed for {$operationName}: {$e->getMessage()}");
                $lastException = $e;

                if ($scope !== null) {
                    $this->suspendIfUnusable($serviceClass, $scope, $e);
                }

                continue;
            }
        }

        // All services failed
        Log::error("FallbackExchangeRateService: All services failed for {$operationName}");
        throw new \RuntimeException(
            "All exchange rate services failed for operation: {$operationName}. Last error: ".
                ($lastException ? $lastException->getMessage() : 'Unknown error').
                ($skippedServices !== [] ? '. Skipped (temporarily suspended): '.implode(', ', $skippedServices) : '')
        );
    }

    /**
     * Whether the service is currently suspended for the given scope.
     * A cache failure never blocks a provider.
     */
    private function isSuspended(string $serviceClass, string $scope): bool
    {
        try {
            if (Cache::has($this->suspensionKey($serviceClass, self::SCOPE_LATEST))) {
                return true;
            }

            return $scope === self::SCOPE_HISTORICAL
                && Cache::has($this->suspensionKey($serviceClass, self::SCOPE_HISTORICAL));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Suspend the service when its error means retrying is pointless for a while: an exhausted
     * quota or rejected credentials suspend it for every operation, a plan that excludes
     * historical rates suspends it for historical operations only.
     */
    private function suspendIfUnusable(string $serviceClass, string $scope, \Throwable $e): void
    {
        if (! ($e instanceof RequestException)) {
            return;
        }

        $status = $e->response->status();
        $body = $e->response->json();
        $errorCode = is_array($body) ? ($body['error-type'] ?? $body['message'] ?? null) : null;

        if ($status === 401 || $status === 429 || in_array($errorCode, self::UNAVAILABLE_ERROR_CODES, true)) {
            $this->suspend($serviceClass, self::SCOPE_LATEST, (int) Config::get('exchange-rates.fallback_suspension.unavailable_seconds', 3600), $errorCode ?? "HTTP {$status}");

            return;
        }

        if ($scope === self::SCOPE_HISTORICAL && in_array($errorCode, self::HISTORICAL_UNSUPPORTED_ERROR_CODES, true)) {
            $this->suspend($serviceClass, self::SCOPE_HISTORICAL, (int) Config::get('exchange-rates.fallback_suspension.historical_unsupported_seconds', 86400), $errorCode);
        }
    }

    private function suspend(string $serviceClass, string $scope, int $seconds, string $reason): void
    {
        if ($seconds <= 0) {
            return;
        }

        try {
            Cache::put($this->suspensionKey($serviceClass, $scope), $reason, $seconds);
            Log::warning("FallbackExchangeRateService: Suspending {$serviceClass} for {$scope} operations for {$seconds}s ({$reason})");
        } catch (\Throwable $e) {
            Log::warning("FallbackExchangeRateService: Could not suspend {$serviceClass}: {$e->getMessage()}");
        }
    }

    private function suspensionKey(string $serviceClass, string $scope): string
    {
        return "exchange-rates:fallback:suspended:{$scope}:{$serviceClass}";
    }

    private function repository(): CurrencyExchangeRateRepositoryInterface
    {
        return app()->make(CurrencyExchangeRateRepositoryInterface::class);
    }

    /**
     * Store exchange rates in the database
     *
     *
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeExchangeRates(string $currency_code): Collection
    {
        return $this->tryWithFallback(
            fn ($service) => $service->storeExchangeRates($currency_code),
            "storeExchangeRates({$currency_code})",
            self::SCOPE_LATEST
        );
    }

    /**
     * Store exchange rates for multiple currencies
     *
     *
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeBulkExchangeRatesForMultipleCurrencies(array $currencies_codes): Collection
    {
        return $this->tryWithFallback(
            fn ($service) => $service->storeBulkExchangeRatesForMultipleCurrencies($currencies_codes),
            'storeBulkExchangeRatesForMultipleCurrencies',
            self::SCOPE_LATEST
        );
    }

    /**
     * Get exchange rates from the database via fallback services
     */
    public function getExchangeRates(string $currency_code): Collection
    {
        return $this->tryWithFallback(
            fn ($service) => $service->getExchangeRates($currency_code),
            "getExchangeRates({$currency_code})"
        );
    }

    /**
     * Get all exchange rates from the database via fallback services
     */
    public function getAllExchangeRates(): Collection
    {
        return $this->tryWithFallback(
            fn ($service) => $service->getAllExchangeRates(),
            'getAllExchangeRates'
        );
    }

    /**
     * Store historical exchange rates in the database
     *
     *
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function storeHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection
    {
        return $this->tryWithFallback(
            function ($service) use ($currency_code, $date_time) {
                if (! ($service instanceof HistoricalSupportExchangeRateServiceInterface)) {
                    throw new \RuntimeException('Service does not support historical exchange rates');
                }

                return $service->storeHistoricalExchangeRates($currency_code, $date_time);
            },
            "storeHistoricalExchangeRates({$currency_code})",
            self::SCOPE_HISTORICAL
        );
    }

    /**
     * Store historical exchange rates for multiple currencies
     *
     * @param  HistoricalBaseCurrencyDto[]  $historical_base_currencies
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function storeBulkHistoricalExchangeRatesForMultipleCurrencies(array $historical_base_currencies): Collection
    {
        return $this->tryWithFallback(
            function ($service) use ($historical_base_currencies) {
                if (! ($service instanceof HistoricalSupportExchangeRateServiceInterface)) {
                    throw new \RuntimeException('Service does not support historical exchange rates');
                }

                return $service->storeBulkHistoricalExchangeRatesForMultipleCurrencies($historical_base_currencies);
            },
            'storeBulkHistoricalExchangeRatesForMultipleCurrencies',
            self::SCOPE_HISTORICAL
        );
    }

    /**
     * Get historical exchange rates from the database via fallback services
     *
     *
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function getHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection
    {
        // Every service reads this same table first; doing it once here keeps stored rates
        // readable even when every provider is suspended.
        $stored = $this->repository()->getHistoricalExchangeRates($currency_code, $date_time);
        if ($stored->isNotEmpty()) {
            return $stored;
        }

        return $this->tryWithFallback(
            function ($service) use ($currency_code, $date_time) {
                if (! ($service instanceof HistoricalSupportExchangeRateServiceInterface)) {
                    throw new \RuntimeException('Service does not support historical exchange rates');
                }

                return $service->getHistoricalExchangeRates($currency_code, $date_time);
            },
            "getHistoricalExchangeRates({$currency_code})",
            self::SCOPE_HISTORICAL
        );
    }

    /**
     * Get a specific historical exchange rate from the database via fallback services
     */
    public function getHistoricalExchangeRate(string $currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory
    {
        // See getHistoricalExchangeRates()
        try {
            return $this->repository()->getHistoricalExchangeRate($currency_code, $target_currency_code, $date_time);
        } catch (ModelNotFoundException) {
            // Not stored yet: fetch through the providers
        }

        return $this->tryWithFallback(
            function ($service) use ($currency_code, $target_currency_code, $date_time) {
                if (! ($service instanceof HistoricalSupportExchangeRateServiceInterface)) {
                    throw new \RuntimeException('Service does not support historical exchange rates');
                }

                return $service->getHistoricalExchangeRate($currency_code, $target_currency_code, $date_time);
            },
            "getHistoricalExchangeRate({$currency_code}, {$target_currency_code})",
            self::SCOPE_HISTORICAL
        );
    }

    /**
     * Get the daily historical exchange rates for a currency pair within a date period via fallback services.
     *
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function getHistoricalExchangeRatesForPeriod(string $currency_code, string $target_currency_code, CarbonInterface $start_date, CarbonInterface $end_date): Collection
    {
        return $this->tryWithFallback(
            function ($service) use ($currency_code, $target_currency_code, $start_date, $end_date) {
                if (! ($service instanceof HistoricalSupportExchangeRateServiceInterface)) {
                    throw new \RuntimeException('Service does not support historical exchange rates');
                }

                return $service->getHistoricalExchangeRatesForPeriod($currency_code, $target_currency_code, $start_date, $end_date);
            },
            "getHistoricalExchangeRatesForPeriod({$currency_code}, {$target_currency_code})"
        );
    }

    /**
     * Get the current active service that succeeded.
     */
    public function getCurrentService(): ?ExchangeRateServiceInterface
    {
        return $this->currentService;
    }

    public function supportsHistoricalApiFetch(): bool
    {
        if ($this->currentService !== null) {
            return $this->currentService->supportsHistoricalApiFetch();
        }

        foreach ($this->fallbackServices as $serviceClass) {
            $service = app()->make($serviceClass);

            if ($service instanceof ExchangeRateServiceInterface && $service->supportsHistoricalApiFetch()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the list of fallback services.
     *
     * @return array<class-string<ExchangeRateServiceInterface>>
     */
    public function getFallbackServices(): array
    {
        return $this->fallbackServices;
    }

    /**
     * Set the fallback services order.
     *
     * @param  array<class-string<ExchangeRateServiceInterface>>  $services
     */
    public function setFallbackServices(array $services): void
    {
        $this->fallbackServices = $services;
    }
}
