<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark expired subscriptions as inactive';

    public function handle(): int
    {
        $count = Subscription::query()
            ->where('status', 'active')
            ->where('ends_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
