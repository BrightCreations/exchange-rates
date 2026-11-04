<?php

namespace BrightCreations\ExchangeRates\Contracts;

use BrightCreations\ExchangeRates\Exceptions\HistoricalRatesUnavailableException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Config;

abstract class BaseExchangeRateService implements ExchangeRateServiceInterface
{
    public function isSupportHistoricalExchangeRate(): bool
    {
        return $this instanceof HistoricalSupportExchangeRateServiceInterface;
    }

    public function supportsHistoricalApiFetch(): bool
    {
        return false;
    }

    /**
     * Get the provider name (short class name) for this service
     */
    protected function getProviderName(): string
    {
        return basename(str_replace('\\', '/', static::class));
    }

    /**
     * Apply the configured HTTP timeouts (seconds) to a provider client; unset ones keep the
     * HTTP client's defaults.
     */
    protected function applyHttpTimeouts(PendingRequest $http): PendingRequest
    {
        $timeout = Config::get('exchange-rates.http.timeout');
        $connectTimeout = Config::get('exchange-rates.http.connect_timeout');

        if (is_numeric($timeout) && $timeout > 0) {
            $http->timeout((int) $timeout);
        }

        if (is_numeric($connectTimeout) && $connectTimeout > 0) {
            $http->connectTimeout((int) $connectTimeout);
        }

        return $http;
    }

    /**
     * Whether the date is after today in UTC — the day providers publish rates for.
     * Compares the date as it is sent to providers (its own `Y-m-d`), not an instant.
     */
    protected function isFutureDate(CarbonInterface $date_time): bool
    {
        return $date_time->format('Y-m-d') > Carbon::now('UTC')->format('Y-m-d');
    }

    /**
     * Fail fast, before any HTTP call, when no provider can have rates for the date yet.
     *
     * @throws HistoricalRatesUnavailableException
     */
    protected function ensureHistoricalRatesCanExist(string $currency_code, CarbonInterface $date_time): void
    {
        if ($this->isFutureDate($date_time)) {
            throw HistoricalRatesUnavailableException::forFutureDate($currency_code, $date_time);
        }
    }
}
