<?php

namespace Brights\ExchangeRates\Console\Commands;

use Illuminate\Console\Command;

class MigrateExchangeRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rates:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the exchange rates szpackage migrations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->call('migrate', [
            '--path' => 'vendor/brights/exchange-rates/Database/Migrations',
            '--realpath' => true,
        ]);

        $this->info('Exchange rate migrations ran successfully.');

        return self::SUCCESS;
    }
}
