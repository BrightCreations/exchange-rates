<?php

namespace BrightCreations\ExchangeRates\Traits;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

trait TimeLoggable
{
    /**
     * Logs the time taken by the closure to execute and if the result is an HTTP client response,
     * it logs the response as well.
     *
     * @param  Closure  $closure
     * @return mixed
     */
    public function logTime($closure)
    {
        $start = microtime(true);
        $result = $closure();
        $end = microtime(true);
        $time = $end - $start;
        Log::debug("[BrightCreations\ExchangeRates] >>>>> Time: ".$time);
        if ($result instanceof Response) {
            Log::debug("[BrightCreations\ExchangeRates] >>>>> Response: ".json_encode($result->json()));
        } elseif (is_scalar($result) || $result === null || $result instanceof \Stringable) {
            Log::debug("[BrightCreations\ExchangeRates] >>>>> Closure Return: ".$result);
        } else {
            // e.g. the World Bank dataset array: concatenating it would raise "Array to string conversion"
            $size = is_countable($result) ? ' ('.count($result).' items)' : '';
            Log::debug("[BrightCreations\ExchangeRates] >>>>> Closure Return: ".get_debug_type($result).$size);
        }

        return $result;
    }
}
