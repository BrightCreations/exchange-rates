<?php

namespace BrightCreations\ExchangeRates\Http\Controllers;

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
     * Return stored exchange rates for a given base currency.
     *
     * Query parameter:
     *   targets  Comma-separated list of target currency codes to filter by (e.g. EUR,GBP,SAR).
     *            When omitted every stored target currency is returned.
     *
     * @param  Request  $request
     * @param  string   $currency  ISO 4217 base currency code (3 letters)
     * @return JsonResponse
     */
    public function index(Request $request, string $currency): JsonResponse
    {
        $request->merge(['currency' => strtoupper($currency)]);

        $validated = $request->validate([
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'targets'  => ['nullable', 'string'],
        ]);

        $baseCurrency = $validated['currency'];

        try {
            $rates = $this->exchangeRateService->getExchangeRates($baseCurrency);
        } catch (\RuntimeException) {
            $rates = collect();
        }

        if (! empty($validated['targets'])) {
            $targetList = collect(explode(',', $validated['targets']))
                ->map(fn (string $t) => strtoupper(trim($t)))
                ->filter(fn (string $t) => strlen($t) === 3)
                ->values()
                ->all();

            $rates = $rates->whereIn('target_currency_code', $targetList)->values();
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
}
