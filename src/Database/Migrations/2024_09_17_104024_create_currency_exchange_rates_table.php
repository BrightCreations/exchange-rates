<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('currency_exchange_rates')) {
            Schema::create('currency_exchange_rates', function (Blueprint $table): void {
                $table->id();
                $table->string('base_currency_code', 3);
                $table->string('target_currency_code', 3);
                $table->string('exchange_rate', 20);
                $table->timestamp('last_update_date')->default(Carbon::now());

                $table->unique(['base_currency_code', 'target_currency_code'], 'currency_exchange_rates_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_exchange_rates');
    }
};
