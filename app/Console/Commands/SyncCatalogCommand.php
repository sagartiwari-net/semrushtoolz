<?php

namespace App\Console\Commands;

use Database\Seeders\PlanSeeder;
use Database\Seeders\ToolAccessSeeder;
use Database\Seeders\ToolSeeder;
use Illuminate\Console\Command;

class SyncCatalogCommand extends Command
{
    protected $signature = 'catalog:sync';

    protected $description = 'Sync tools, bundle plans, and tool access from seeders (safe to re-run)';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => ToolSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => PlanSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => ToolAccessSeeder::class, '--force' => true]);

        $this->info('Catalog synced.');

        return self::SUCCESS;
    }
}
