<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add provider column to currency_exchange_rates table
        if (Schema::hasTable('currency_exchange_rates')) {
            Schema::table('currency_exchange_rates', function (Blueprint $table): void {
                $table->string('provider', 100)->nullable()->after('exchange_rate');
            });
        }

        // Add provider column to currency_exchange_rates_history table
        if (Schema::hasTable('currency_exchange_rates_history')) {
            Schema::table('currency_exchange_rates_history', function (Blueprint $table): void {
                $table->string('provider', 100)->nullable()->after('exchange_rate');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove provider column from currency_exchange_rates table
        if (Schema::hasTable('currency_exchange_rates')) {
            Schema::table('currency_exchange_rates', function (Blueprint $table): void {
                $table->dropColumn('provider');
            });
        }

        // Remove provider column from currency_exchange_rates_history table
        if (Schema::hasTable('currency_exchange_rates_history')) {
            Schema::table('currency_exchange_rates_history', function (Blueprint $table): void {
                $table->dropColumn('provider');
            });
        }
    }
};
