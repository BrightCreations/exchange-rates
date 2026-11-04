<?php

namespace BrightCreations\ExchangeRates\Exceptions;

use Carbon\CarbonInterface;

/**
 * Thrown before any provider call is made when the requested historical rates cannot exist yet
 * (e.g. a date in the future), so no API quota is spent on a request that is guaranteed to fail.
 *
 * @author Bright Creations <kareem.shaaban@brightcreations.com>
 * @license MIT
 */
class HistoricalRatesUnavailableException extends \RuntimeException
{
    public static function forFutureDate(string $currency_code, CarbonInterface $date_time): self
    {
        return new self("Historical exchange rates for {$currency_code} on {$date_time->format('Y-m-d')} are not available yet: the date is in the future.");
    }
}
