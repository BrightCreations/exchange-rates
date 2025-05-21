<?php

namespace BC\ExchangeRates\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

trait TimeLoggable
{
    /**
     * Logs the time taken by the closure to execute and if the result is a PSR7 ResponseInterface,
     * it logs the response as well.
     *
     * @param Closure $closure
     * @return mixed
     */
    public function logTime($closure)
    {
        $start = microtime(true);
        $result = $closure();
        $end = microtime(true);
        $time = $end - $start;
        Log::debug("[BC\ExchangeRates] >>>>> Time: " . $time);
        if ($result instanceof Response) {
            Log::debug("[BC\ExchangeRates] >>>>> Response: " . json_encode($result->json()));
        } else {
            Log::debug("[BC\ExchangeRates] >>>>> Closure Return: " . $result);
        }
        return $result;
    }

}
