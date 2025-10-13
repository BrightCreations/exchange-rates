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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * FallbackExchangeRateService provides a fallback mechanism for exchange rate services.
 * It tries multiple services in order until one succeeds.
 * 
 * @package BrightCreations\ExchangeRates\Concretes
 * @author Bright Creations <kareem.shaaban@brightcreations.com>
 * @license MIT
 */
class FallbackExchangeRateService extends BaseExchangeRateService implements ExchangeRateServiceInterface, HistoricalSupportExchangeRateServiceInterface
{
    /**
     * List of service classes to try in order
     * 
     * @var array<class-string<ExchangeRateServiceInterface>>
     */
    private array $fallbackServices;

    /**
     * Current active service instance
     * 
     * @var ExchangeRateServiceInterface|null
     */
    private ?ExchangeRateServiceInterface $currentService = null;

    public function __construct(
        private CurrencyExchangeRateRepositoryInterface $currencyExchangeRateRepository,
    ) {
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
     * @param callable $operation The operation to perform: function(ExchangeRateServiceInterface $service): mixed
     * @param string $operationName Name of the operation for logging
     * @return mixed
     * @throws \RuntimeException If all services fail
     */
    private function tryWithFallback(callable $operation, string $operationName)
    {
        $lastException = null;

        foreach ($this->fallbackServices as $serviceClass) {
            try {
                // Resolve the service from the container
                $service = app()->make($serviceClass);

                if (!($service instanceof ExchangeRateServiceInterface)) {
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
                continue;
            }
        }

        // All services failed
        Log::error("FallbackExchangeRateService: All services failed for {$operationName}");
        throw new \RuntimeException(
            "All exchange rate services failed for operation: {$operationName}. Last error: " .
                ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    /**
     * Store exchange rates in the database
     *
     * @param string $currency_code
     * 
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeExchangeRates(string $currency_code): Collection
    {
        return $this->tryWithFallback(
            fn($service) => $service->storeExchangeRates($currency_code),
            "storeExchangeRates({$currency_code})"
        );
    }

    /**
     * Store exchange rates for multiple currencies
     *
     * @param array $currencies_codes
     * 
     * @return Collection<CurrencyExchangeRate>
     */
    public function storeBulkExchangeRatesForMultipleCurrencies(array $currencies_codes): Collection
    {
        return $this->tryWithFallback(
            fn($service) => $service->storeBulkExchangeRatesForMultipleCurrencies($currencies_codes),
            "storeBulkExchangeRatesForMultipleCurrencies"
        );
    }

    /**
     * Get exchange rates from the database
     *
     * @param string $currency_code
     * 
     * @return Collection
     */
    public function getExchangeRates(string $currency_code): Collection
    {
        return $this->currencyExchangeRateRepository->getExchangeRates($currency_code);
    }

    /**
     * Get all exchange rates from the database
     * 
     * @return Collection
     */
    public function getAllExchangeRates(): Collection
    {
        return $this->currencyExchangeRateRepository->getAllExchangeRates();
    }

    /**
     * Store historical exchange rates in the database
     * 
     * @param string $currency_code
     * @param CarbonInterface $date_time
     * 
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function storeHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection
    {
        return $this->tryWithFallback(
            function ($service) use ($currency_code, $date_time) {
                if (!($service instanceof HistoricalSupportExchangeRateServiceInterface)) {
                    throw new \RuntimeException("Service does not support historical exchange rates");
                }
                return $service->storeHistoricalExchangeRates($currency_code, $date_time);
            },
            "storeHistoricalExchangeRates({$currency_code})"
        );
    }

    /**
     * Store historical exchange rates for multiple currencies
     *
     * @param HistoricalBaseCurrencyDto[] $historical_base_currencies
     * 
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function storeBulkHistoricalExchangeRatesForMultipleCurrencies(array $historical_base_currencies): Collection
    {
        return $this->tryWithFallback(
            function ($service) use ($historical_base_currencies) {
                if (!($service instanceof HistoricalSupportExchangeRateServiceInterface)) {
                    throw new \RuntimeException("Service does not support historical exchange rates");
                }
                return $service->storeBulkHistoricalExchangeRatesForMultipleCurrencies($historical_base_currencies);
            },
            "storeBulkHistoricalExchangeRatesForMultipleCurrencies"
        );
    }

    /**
     * Get historical exchange rates from the database
     * 
     * @param string $currency_code
     * @param CarbonInterface $date_time
     * 
     * @return Collection<CurrencyExchangeRateHistory>
     */
    public function getHistoricalExchangeRates(string $currency_code, CarbonInterface $date_time): Collection
    {
        return $this->currencyExchangeRateRepository->getHistoricalExchangeRates($currency_code, $date_time);
    }

    /**
     * Get a specific historical exchange rate from the database
     * 
     * @param string $currency_code
     * @param string $target_currency_code
     * @param CarbonInterface $date_time
     * 
     * @return CurrencyExchangeRateHistory
     */
    public function getHistoricalExchangeRate(string $currency_code, string $target_currency_code, CarbonInterface $date_time): CurrencyExchangeRateHistory
    {
        return $this->currencyExchangeRateRepository->getHistoricalExchangeRate($currency_code, $target_currency_code, $date_time);
    }

    /**
     * Get the current active service that succeeded.
     * 
     * @return ExchangeRateServiceInterface|null
     */
    public function getCurrentService(): ?ExchangeRateServiceInterface
    {
        return $this->currentService;
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
     * @param array<class-string<ExchangeRateServiceInterface>> $services
     * @return void
     */
    public function setFallbackServices(array $services): void
    {
        $this->fallbackServices = $services;
    }
}
