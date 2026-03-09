<?php

use BrightCreations\ExchangeRates\Http\Controllers\ExchangeRatesController;
use Illuminate\Support\Facades\Route;

Route::get('{currency}', [ExchangeRatesController::class, 'index'])
    ->name('exchange-rates.index');
