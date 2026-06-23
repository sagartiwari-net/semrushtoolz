<?php

namespace App\Console\Commands;

use App\Services\EmailBroadcastService;
use Illuminate\Console\Command;

class ProcessEmailBroadcastQueue extends Command
{
    protected $signature = 'email:process-broadcast-queue {--batch=8}';

    protected $description = 'Send queued promotional emails in small batches';

    public function handle(EmailBroadcastService $broadcasts): int
    {
        $pending = $broadcasts->pendingCount();

        if ($pending === 0) {
            return self::SUCCESS;
        }

        $batch = max(1, (int) $this->option('batch'));
        $result = $broadcasts->processBatch($batch);

        $this->info("Broadcast batch: sent {$result['sent']}, failed {$result['failed']}, {$result['remaining']} remaining.");

        return self::SUCCESS;
    }
}
