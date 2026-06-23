<?php

namespace App\Console\Commands;

use App\Services\AffiliateService;
use Illuminate\Console\Command;

class ReleaseHeldAffiliateCommissions extends Command
{
    protected $signature = 'affiliate:release-held-commissions';

    protected $description = 'Release PayPal affiliate commissions after hold period expires';

    public function handle(AffiliateService $affiliates): int
    {
        $count = $affiliates->releaseHeldCommissions();
        $this->info("Released {$count} held commission(s).");

        return self::SUCCESS;
    }
}
