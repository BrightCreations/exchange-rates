# Examples

This guide provides practical examples of how to use the Exchange Rates Library in real-world scenarios.

## Basic Usage

### Getting Current Exchange Rates

```php
<?php

namespace App\Http\Controllers;

use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function __construct(
        private ExchangeRateServiceInterface $exchangeRateService
    ) {}

    public function getCurrentRates(Request $request)
    {
        $currency = $request->get('currency', 'USD');
        
        try {
            // Store and retrieve current exchange rates
            $rates = $this->exchangeRateService->storeExchangeRates($currency);
            
            return response()->json([
                'success' => true,
                'base_currency' => $currency,
                'rates' => $rates->map(function ($rate) {
                    return [
                        'target_currency' => $rate->target_currency_code,
                        'rate' => $rate->exchange_rate,
                        'last_updated' => $rate->last_update_date
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch exchange rates: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

### Getting Historical Exchange Rates

```php
<?php

namespace App\Http\Controllers;

use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HistoricalController extends Controller
{
    public function __construct(
        private HistoricalSupportExchangeRateServiceInterface $historicalService
    ) {}

    public function getHistoricalRates(Request $request)
    {
        $currency = $request->get('currency', 'USD');
        $date = $request->get('date', '2023-01-15');
        
        try {
            $dateTime = Carbon::parse($date);
            $rates = $this->historicalService->getHistoricalExchangeRates($currency, $dateTime);
            
            return response()->json([
                'success' => true,
                'base_currency' => $currency,
                'date' => $dateTime->format('Y-m-d'),
                'rates' => $rates->map(function ($rate) {
                    return [
                        'target_currency' => $rate->target_currency_code,
                        'rate' => $rate->exchange_rate,
                        'date' => $rate->date_time->format('Y-m-d')
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch historical rates: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

## Advanced Usage

### Bulk Operations with DTOs

```php
<?php

namespace App\Services;

use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;
use BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto;
use Illuminate\Support\Collection;

class CurrencyService
{
    public function __construct(
        private CurrencyExchangeRateRepositoryInterface $repository
    ) {}

    public function updateMultipleCurrencies(): bool
    {
        // Create DTOs for multiple currencies
        $exchangeRates = [
            new ExchangeRatesDto('USD', [
                'EUR' => 0.85,
                'GBP' => 0.73,
                'JPY' => 110.50,
                'CAD' => 1.25
            ]),
            new ExchangeRatesDto('EUR', [
                'USD' => 1.18,
                'GBP' => 0.86,
                'JPY' => 130.00,
                'CAD' => 1.47
            ]),
            new ExchangeRatesDto('GBP', [
                'USD' => 1.37,
                'EUR' => 1.16,
                'JPY' => 151.37,
                'CAD' => 1.71
            ])
        ];

        return $this->repository->updateBulkExchangeRates($exchangeRates);
    }

    public function getSpecificRates(): Collection
    {
        // Create DTOs for specific currency pairs
        $pairs = [
            new CurrenciesPairDto('USD', 'EUR'),
            new CurrenciesPairDto('USD', 'GBP'),
            new CurrenciesPairDto('EUR', 'USD'),
            new CurrenciesPairDto('GBP', 'EUR')
        ];

        return $this->repository->getBulkExchangeRate($pairs);
    }
}
```

### Historical Data with DTOs

```php
<?php

namespace App\Services;

use BrightCreations\ExchangeRates\Contracts\Repositories\CurrencyExchangeRateRepositoryInterface;
use BrightCreations\ExchangeRates\DTOs\HistoricalExchangeRatesDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalCurrenciesPairDto;
use BrightCreations\ExchangeRates\DTOs\HistoricalBaseCurrencyDto;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HistoricalCurrencyService
{
    public function __construct(
        private CurrencyExchangeRateRepositoryInterface $repository
    ) {}

    public function updateHistoricalData(): bool
    {
        $date = Carbon::parse('2023-01-15');
        
        $historicalRates = [
            new HistoricalExchangeRatesDto('USD', [
                'EUR' => 0.82,
                'GBP' => 0.70,
                'JPY' => 108.50
            ], $date),
            new HistoricalExchangeRatesDto('EUR', [
                'USD' => 1.22,
                'GBP' => 0.85,
                'JPY' => 132.32
            ], $date)
        ];

        return $this->repository->updateBulkExchangeRatesHistory($historicalRates);
    }

    public function getHistoricalRatesForDate(string $date): Collection
    {
        $dateTime = Carbon::parse($date);
        
        $baseCurrencies = [
            new HistoricalBaseCurrencyDto('USD', $dateTime),
            new HistoricalBaseCurrencyDto('EUR', $dateTime),
            new HistoricalBaseCurrencyDto('GBP', $dateTime)
        ];

        return $this->repository->getBulkHistoricalExchangeRates($baseCurrencies);
    }

    public function getSpecificHistoricalRates(string $date): Collection
    {
        $dateTime = Carbon::parse($date);
        
        $pairs = [
            new HistoricalCurrenciesPairDto('USD', 'EUR', $dateTime),
            new HistoricalCurrenciesPairDto('USD', 'GBP', $dateTime),
            new HistoricalCurrenciesPairDto('EUR', 'USD', $dateTime)
        ];

        return $this->repository->getBulkHistoricalExchangeRate($pairs);
    }
}
```

## Service Layer Examples

### Custom Exchange Rate Service

```php
<?php

namespace App\Services;

use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CustomExchangeRateService
{
    public function __construct(
        private ExchangeRateServiceInterface $exchangeRateService,
        private HistoricalSupportExchangeRateServiceInterface $historicalService
    ) {}

    public function getRatesForMultipleCurrencies(array $currencies): array
    {
        $results = [];
        
        foreach ($currencies as $currency) {
            try {
                $rates = $this->exchangeRateService->getExchangeRates($currency);
                $results[$currency] = [
                    'success' => true,
                    'rates' => $rates->toArray()
                ];
            } catch (\Exception $e) {
                $results[$currency] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    public function getHistoricalRatesForPeriod(string $currency, string $startDate, string $endDate): Collection
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $allRates = collect();
        
        $current = $start->copy();
        while ($current->lte($end)) {
            try {
                $rates = $this->historicalService->getHistoricalExchangeRates($currency, $current);
                $allRates = $allRates->merge($rates);
            } catch (\Exception $e) {
                // Log error and continue
                \Log::warning("Failed to get historical rates for {$currency} on {$current->format('Y-m-d')}: " . $e->getMessage());
            }
            
            $current->addDay();
        }
        
        return $allRates;
    }

    public function compareRates(string $baseCurrency, array $targetCurrencies, string $date = null): array
    {
        $comparison = [];
        
        if ($date) {
            // Historical comparison
            $dateTime = Carbon::parse($date);
            foreach ($targetCurrencies as $targetCurrency) {
                try {
                    $rate = $this->historicalService->getHistoricalExchangeRate($baseCurrency, $targetCurrency, $dateTime);
                    $comparison[$targetCurrency] = [
                        'rate' => $rate->exchange_rate,
                        'date' => $rate->date_time->format('Y-m-d'),
                        'type' => 'historical'
                    ];
                } catch (\Exception $e) {
                    $comparison[$targetCurrency] = [
                        'error' => $e->getMessage(),
                        'type' => 'historical'
                    ];
                }
            }
        } else {
            // Current comparison
            $rates = $this->exchangeRateService->getExchangeRates($baseCurrency);
            foreach ($targetCurrencies as $targetCurrency) {
                $rate = $rates->where('target_currency_code', $targetCurrency)->first();
                if ($rate) {
                    $comparison[$targetCurrency] = [
                        'rate' => $rate->exchange_rate,
                        'last_updated' => $rate->last_update_date,
                        'type' => 'current'
                    ];
                } else {
                    $comparison[$targetCurrency] = [
                        'error' => 'Rate not found',
                        'type' => 'current'
                    ];
                }
            }
        }
        
        return $comparison;
    }
}
```

## Command Line Examples

### Custom Artisan Command

```php
<?php

namespace App\Console\Commands;

use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use Illuminate\Console\Command;

class UpdateExchangeRatesCommand extends Command
{
    protected $signature = 'exchange-rates:update {currency?}';
    protected $description = 'Update exchange rates for specified currency or all currencies';

    public function __construct(
        private ExchangeRateServiceInterface $exchangeRateService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $currency = $this->argument('currency');
        
        if ($currency) {
            $this->updateSingleCurrency($currency);
        } else {
            $this->updateAllCurrencies();
        }
    }

    private function updateSingleCurrency(string $currency): void
    {
        $this->info("Updating exchange rates for {$currency}...");
        
        try {
            $rates = $this->exchangeRateService->storeExchangeRates($currency);
            
            $this->info("Successfully updated {$rates->count()} exchange rates for {$currency}");
            
            $this->table(
                ['Target Currency', 'Rate', 'Last Updated'],
                $rates->map(function ($rate) {
                    return [
                        $rate->target_currency_code,
                        $rate->exchange_rate,
                        $rate->last_update_date
                    ];
                })->toArray()
            );
        } catch (\Exception $e) {
            $this->error("Failed to update exchange rates for {$currency}: " . $e->getMessage());
        }
    }

    private function updateAllCurrencies(): void
    {
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD'];
        
        $this->info("Updating exchange rates for all currencies...");
        
        $progressBar = $this->output->createProgressBar(count($currencies));
        $progressBar->start();
        
        foreach ($currencies as $currency) {
            try {
                $this->exchangeRateService->storeExchangeRates($currency);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Failed to update {$currency}: " . $e->getMessage());
            }
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->info("Exchange rates update completed!");
    }
}
```

## API Controller Examples

### RESTful API Controller

```php
<?php

namespace App\Http\Controllers\Api;

use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\Contracts\HistoricalSupportExchangeRateServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ExchangeRatesController extends Controller
{
    public function __construct(
        private ExchangeRateServiceInterface $exchangeRateService,
        private HistoricalSupportExchangeRateServiceInterface $historicalService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $currency = $request->get('currency', 'USD');
        
        try {
            $rates = $this->exchangeRateService->getExchangeRates($currency);
            
            return response()->json([
                'data' => [
                    'base_currency' => $currency,
                    'rates' => $rates->map(function ($rate) {
                        return [
                            'target_currency' => $rate->target_currency_code,
                            'rate' => $rate->exchange_rate,
                            'last_updated' => $rate->last_update_date
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch exchange rates',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => 'required|string|size:3'
        ]);

        $currency = strtoupper($request->get('currency'));
        
        try {
            $rates = $this->exchangeRateService->storeExchangeRates($currency);
            
            return response()->json([
                'message' => 'Exchange rates updated successfully',
                'data' => [
                    'base_currency' => $currency,
                    'rates_count' => $rates->count(),
                    'rates' => $rates->map(function ($rate) {
                        return [
                            'target_currency' => $rate->target_currency_code,
                            'rate' => $rate->exchange_rate
                        ];
                    })
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update exchange rates',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function historical(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => 'required|string|size:3',
            'date' => 'required|date'
        ]);

        $currency = strtoupper($request->get('currency'));
        $date = Carbon::parse($request->get('date'));
        
        try {
            $rates = $this->historicalService->getHistoricalExchangeRates($currency, $date);
            
            return response()->json([
                'data' => [
                    'base_currency' => $currency,
                    'date' => $date->format('Y-m-d'),
                    'rates' => $rates->map(function ($rate) {
                        return [
                            'target_currency' => $rate->target_currency_code,
                            'rate' => $rate->exchange_rate,
                            'date' => $rate->date_time->format('Y-m-d')
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch historical rates',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'amount' => 'required|numeric|min:0',
            'date' => 'nullable|date'
        ]);

        $from = strtoupper($request->get('from'));
        $to = strtoupper($request->get('to'));
        $amount = $request->get('amount');
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : null;
        
        try {
            if ($date) {
                // Historical conversion
                $rate = $this->historicalService->getHistoricalExchangeRate($from, $to, $date);
                $convertedAmount = $amount * $rate->exchange_rate;
                
                return response()->json([
                    'data' => [
                        'from' => $from,
                        'to' => $to,
                        'amount' => $amount,
                        'rate' => $rate->exchange_rate,
                        'converted_amount' => $convertedAmount,
                        'date' => $date->format('Y-m-d'),
                        'type' => 'historical'
                    ]
                ]);
            } else {
                // Current conversion
                $rates = $this->exchangeRateService->getExchangeRates($from);
                $rate = $rates->where('target_currency_code', $to)->first();
                
                if (!$rate) {
                    return response()->json([
                        'error' => 'Exchange rate not found'
                    ], 404);
                }
                
                $convertedAmount = $amount * $rate->exchange_rate;
                
                return response()->json([
                    'data' => [
                        'from' => $from,
                        'to' => $to,
                        'amount' => $amount,
                        'rate' => $rate->exchange_rate,
                        'converted_amount' => $convertedAmount,
                        'last_updated' => $rate->last_update_date,
                        'type' => 'current'
                    ]
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to convert currency',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
```

## Testing Examples

### Unit Tests

```php
<?php

namespace Tests\Unit;

use BrightCreations\ExchangeRates\Contracts\ExchangeRateServiceInterface;
use BrightCreations\ExchangeRates\DTOs\ExchangeRatesDto;
use BrightCreations\ExchangeRates\DTOs\CurrenciesPairDto;
use Tests\TestCase;
use Mockery;

class ExchangeRatesTest extends TestCase
{
    public function test_can_get_exchange_rates()
    {
        $service = Mockery::mock(ExchangeRateServiceInterface::class);
        $service->shouldReceive('getExchangeRates')
            ->with('USD')
            ->once()
            ->andReturn(collect([
                (object) [
                    'target_currency_code' => 'EUR',
                    'exchange_rate' => 0.85,
                    'last_update_date' => now()
                ]
            ]));

        $rates = $service->getExchangeRates('USD');
        
        $this->assertCount(1, $rates);
        $this->assertEquals('EUR', $rates->first()->target_currency_code);
        $this->assertEquals(0.85, $rates->first()->exchange_rate);
    }

    public function test_exchange_rates_dto_creation()
    {
        $dto = new ExchangeRatesDto('USD', ['EUR' => 0.85, 'GBP' => 0.73]);
        
        $this->assertEquals('USD', $dto->getBaseCurrencyCode());
        $this->assertEquals(['EUR' => 0.85, 'GBP' => 0.73], $dto->getExchangeRates());
    }

    public function test_currencies_pair_dto_string_representation()
    {
        $dto = new CurrenciesPairDto('USD', 'EUR');
        
        $this->assertEquals('USD_EUR', (string) $dto);
    }
}
```

## Fallback & World Bank Examples

### Using Fallback Service (Automatic)

The default configuration uses the `FallbackExchangeRateService` which automatically tries services in order:

```php
<?php

use BrightCreations\ExchangeRates\Facades\ExchangeRate;
use Illuminate\Support\Facades\Log;

// The facade uses FallbackExchangeRateService by default
// It will try: ExchangeRateAPI → OpenExchangeRates → WorldBank
$rates = ExchangeRate::storeExchangeRates('EUR');

// Check logs to see which service succeeded
Log::info('Exchange rates fetched successfully', [
    'currency' => 'EUR',
    'count' => $rates->count()
]);
```

### Using World Bank Service Directly

```php
<?php

use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;

class FreeExchangeRateController extends Controller
{
    public function __construct(
        private WorldBankExchangeRateApiService $worldBankService
    ) {}

    public function getYearlyAverages(string $currency)
    {
        // World Bank provides yearly average rates (free, no API key)
        $rates = $this->worldBankService->storeExchangeRates($currency);
        
        return response()->json([
            'note' => 'These are yearly average rates from World Bank',
            'currency' => $currency,
            'rates' => $rates->map(fn($r) => [
                'target' => $r->target_currency_code,
                'rate' => $r->exchange_rate,
                'year' => $r->last_update_date->format('Y')
            ])
        ]);
    }

    public function getHistoricalYearlyRate(string $base, string $target, int $year)
    {
        $date = Carbon::create($year, 1, 1);
        
        // Fetch historical rates for a specific year
        $historicalRates = $this->worldBankService->storeHistoricalExchangeRates($base, $date);
        
        $targetRate = $historicalRates->firstWhere('target_currency_code', $target);
        
        if (!$targetRate) {
            return response()->json(['error' => 'Rate not found'], 404);
        }
        
        return response()->json([
            'base' => $base,
            'target' => $target,
            'year' => $year,
            'rate' => $targetRate->exchange_rate,
            'note' => 'Yearly average from World Bank data'
        ]);
    }
}
```

### Custom Fallback Order

```php
<?php

use BrightCreations\ExchangeRates\Concretes\FallbackExchangeRateService;
use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;
use BrightCreations\ExchangeRates\Concretes\OpenExchangeRateService;

class CustomFallbackController extends Controller
{
    public function useFreeServicesFirst()
    {
        $fallbackService = app(FallbackExchangeRateService::class);
        
        // Set custom order: try free services first
        $fallbackService->setFallbackServices([
            WorldBankExchangeRateApiService::class,  // Free
            OpenExchangeRateService::class,          // Has free tier
        ]);
        
        $rates = $fallbackService->storeExchangeRates('USD');
        
        // Check which service succeeded
        $currentService = $fallbackService->getCurrentService();
        $serviceName = class_basename($currentService);
        
        return response()->json([
            'rates' => $rates,
            'served_by' => $serviceName
        ]);
    }
}
```

### Bulk Operations with World Bank

```php
<?php

use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;

class BulkWorldBankController extends Controller
{
    public function __construct(
        private WorldBankExchangeRateApiService $worldBankService
    ) {}

    public function bulkFetchYearlyRates()
    {
        $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CNY'];
        
        // Efficient: fetches World Bank data once, computes rates for all currencies
        $bulkRates = $this->worldBankService
            ->storeBulkExchangeRatesForMultipleCurrencies($currencies);
        
        return response()->json([
            'currencies_count' => $currencies,
            'rates' => $bulkRates->mapWithKeys(function ($rates, $base) {
                return [$base => $rates->count()];
            }),
            'note' => 'World Bank fetches once and computes cross-rates efficiently'
        ]);
    }
}
```

### Monitoring Fallback Usage

```php
<?php

use BrightCreations\ExchangeRates\Facades\ExchangeRate;
use Illuminate\Support\Facades\Log;

class MonitoredExchangeController extends Controller
{
    public function getRatesWithMonitoring(string $currency)
    {
        try {
            $startTime = microtime(true);
            
            $rates = ExchangeRate::storeExchangeRates($currency);
            
            $duration = microtime(true) - $startTime;
            
            // Log metrics
            Log::info('Exchange rate fetch completed', [
                'currency' => $currency,
                'duration_ms' => round($duration * 1000, 2),
                'rates_count' => $rates->count(),
                'timestamp' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $rates,
                'meta' => [
                    'duration_ms' => round($duration * 1000, 2)
                ]
            ]);
            
        } catch (\Exception $e) {
            // Log failure
            Log::error('All exchange rate services failed', [
                'currency' => $currency,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'All services unavailable. Please try again later.'
            ], 503);
        }
    }
}
```

### Custom Country-to-Currency Mapping

```php
<?php

use BrightCreations\ExchangeRates\Concretes\WorldBankExchangeRateApiService;
use BrightCreations\ExchangeRates\Concretes\Helpers\WorldBankExchangeRateHelper;

class CustomMappingService
{
    use WorldBankExchangeRateHelper;
    
    public function __construct()
    {
        // Add custom overrides for special cases
        $this->addCurrencyOverride('XKX', 'EUR');  // Kosovo uses EUR
        $this->addCurrencyOverride('PSE', 'ILS');  // Palestine - prefer ILS
    }
    
    public function getAvailableCurrenciesForYear(int $year)
    {
        // Mock fetching World Bank data
        $worldBankData = $this->fetchWorldBankData($year);
        
        $currencies = $this->getAvailableCurrencies($worldBankData);
        
        return response()->json([
            'year' => $year,
            'available_currencies' => $currencies,
            'count' => count($currencies)
        ]);
    }
}
```

## Related Documentation

- **[Installation & Configuration](installation.md)** - Setup instructions
- **[Services & Contracts](services.md)** - Service layer documentation
- **[Repository Pattern](repository.md)** - Database operations
- **[DTOs Guide](dtos.md)** - Data Transfer Objects
- **[API Reference](api-reference.md)** - Complete method documentation 