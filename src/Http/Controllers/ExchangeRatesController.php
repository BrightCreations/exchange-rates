<?php

namespace BrightCreations\ExchangeRates\Http\Controllers;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ExchangeRatesController extends Controller
{
    public function __construct(
        private ExchangeRateServiceInterface $exchangeRateService,
    ) {}

    /**
     * Return stored exchange rates for a given currency.
     *
     * Normal mode (reversed omitted / false):
     *   - {currency} is treated as the BASE currency.
     *   - Returns all target currencies and their stored rates.
     *   - Optional `currencies` filters the returned target currencies.
     *
     * Reversed mode (reversed=true):
     *   - {currency} is treated as the TARGET currency.
     *   - Returns all base currencies that store a rate to this target,
     *     with each rate inverted (1 / stored_rate) using precise decimal math.
     *   - Optional `currencies` filters the returned source currencies.
     *
     * @param  Request  $request
     * @param  string   $currency  ISO 4217 currency code (3 letters)
     * @return JsonResponse
     */
    public function index(Request $request, string $currency): JsonResponse
    {
        $request->merge([
            'currency' => strtoupper($currency),
            'reversed' => filter_var($request->input('reversed', false), FILTER_VALIDATE_BOOLEAN),
        ]);

        $validated = $request->validate([
            'currency'   => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'reversed'   => ['nullable', 'boolean'],
            'currencies' => ['nullable', 'string'],
        ]);

        $code     = $validated['currency'];
        $reversed = (bool) ($validated['reversed'] ?? false);

        if ($reversed) {
            return $this->reversedResponse($code, $validated['currencies'] ?? null);
        }

        return $this->normalResponse($code, $validated['currencies'] ?? null);
    }

    /**
     * Normal mode: {currency} is the base, return rates to all targets.
     */
    private function normalResponse(string $baseCurrency, ?string $currenciesParam): JsonResponse
    {
        try {
            $rates = $this->exchangeRateService->getExchangeRates($baseCurrency);
        } catch (\RuntimeException) {
            $rates = collect();
        }

        if (! empty($currenciesParam)) {
            $rates = $rates->whereIn('target_currency_code', $this->parseCurrencyList($currenciesParam))->values();
        }

        return response()->json([
            'data' => [
                'base_currency' => $baseCurrency,
                'rates'         => $rates->map(fn ($rate) => [
                    'target_currency' => $rate->target_currency_code,
                    'rate'            => $rate->exchange_rate,
                    'last_updated'    => $rate->last_update_date,
                ])->values(),
            ],
        ]);
    }

    /**
     * Reversed mode: {currency} is the target, return inverted rates from all sources.
     */
    private function reversedResponse(string $targetCurrency, ?string $currenciesParam): JsonResponse
    {
        try {
            $rates = $this->exchangeRateService->getExchangeRates($targetCurrency);
        } catch (\RuntimeException) {
            $rates = collect();
        }

        if (! empty($currenciesParam)) {
            $rates = $rates->whereIn('target_currency_code', $this->parseCurrencyList($currenciesParam))->values();
        }

        $rates = $rates->map(function ($rate) {
            $stored = BigDecimal::of($rate->exchange_rate);

            if ($stored->isZero()) {
                return null;
            }

            return [
                'source_currency' => $rate->target_currency_code,
                'rate'            => BigDecimal::of(1)
                    ->dividedBy($stored, 10, RoundingMode::HALF_UP)
                    ->__toString(),
                'last_updated'    => $rate->last_update_date,
            ];
        })->filter()->values();

        return response()->json([
            'data' => [
                'target_currency' => $targetCurrency,
                'rates'           => $rates,
            ],
        ]);
    }

    /**
     * Parse a comma-separated currency code string into a normalized uppercase array.
     *
     * @return string[]
     */
    private function parseCurrencyList(string $raw): array
    {
        return collect(explode(',', $raw))
            ->map(fn (string $c) => strtoupper(trim($c)))
            ->filter(fn (string $c) => strlen($c) === 3)
            ->values()
            ->all();
    }
}
