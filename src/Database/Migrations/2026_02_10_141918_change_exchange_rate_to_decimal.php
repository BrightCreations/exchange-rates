<?php

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
        // Change exchange_rate in currency_exchange_rates table
        if (Schema::hasTable('currency_exchange_rates')) {
            Schema::table('currency_exchange_rates', function (Blueprint $table): void {
                $table->decimal('exchange_rate', 20, 10)->change();
            });
        }

        // Change exchange_rate in currency_exchange_rates_history table
        if (Schema::hasTable('currency_exchange_rates_history')) {
            Schema::table('currency_exchange_rates_history', function (Blueprint $table): void {
                $table->decimal('exchange_rate', 20, 10)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert exchange_rate in currency_exchange_rates table
        if (Schema::hasTable('currency_exchange_rates')) {
            Schema::table('currency_exchange_rates', function (Blueprint $table): void {
                $table->string('exchange_rate', 20)->change();
            });
        }

        // Revert exchange_rate in currency_exchange_rates_history table
        if (Schema::hasTable('currency_exchange_rates_history')) {
            Schema::table('currency_exchange_rates_history', function (Blueprint $table): void {
                $table->string('exchange_rate', 20)->change();
            });
        }
    }
};
