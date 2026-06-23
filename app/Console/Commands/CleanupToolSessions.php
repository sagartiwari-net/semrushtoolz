<?php

namespace App\Console\Commands;

use App\Services\ToolAccessService;
use Illuminate\Console\Command;

class CleanupToolSessions extends Command
{
    protected $signature = 'tools:cleanup-sessions';

    protected $description = 'End stale tool sessions past max duration';

    public function handle(ToolAccessService $tools): int
    {
        $count = $tools->cleanupStaleSessions();
        $this->info("Ended {$count} stale session(s).");

        return self::SUCCESS;
    }
}
