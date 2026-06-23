<?php

namespace App\Console\Commands;

use App\Services\BuyahrefPaymentService;
use Illuminate\Console\Command;

class VerifyUpiPayments extends Command
{
    protected $signature = 'upi:verify';

    protected $description = 'UPI payment verification status';

    public function handle(BuyahrefPaymentService $buyahref): int
    {
        if ($buyahref->isConfigured()) {
            $this->info('Buyahref Payment Hub handles UPI verification via webhook.');

            return self::SUCCESS;
        }

        $this->comment('Buyahref not configured. Admin → Payment Integration.');

        return self::SUCCESS;
    }
}
