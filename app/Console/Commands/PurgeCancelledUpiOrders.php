<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class PurgeCancelledUpiOrders extends Command
{
    protected $signature = 'orders:purge-cancelled-upi {--days=3 : Days after cancel/reject before deletion} {--dry-run : List only, do not delete}';

    protected $description = 'Permanently delete cancelled and rejected orders older than N days';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $query = Order::query()
            ->whereIn('status', ['cancelled', 'rejected'])
            ->where('updated_at', '<=', $cutoff);

        if ($this->option('dry-run')) {
            $orders = $query->orderBy('updated_at')->get(['id', 'order_number', 'status', 'payment_method', 'updated_at']);

            if ($orders->isEmpty()) {
                $this->info("No cancelled/rejected orders older than {$days} day(s).");

                return self::SUCCESS;
            }

            $this->info("Found {$orders->count()} order(s) older than {$days} day(s).");

            foreach ($orders as $order) {
                $this->line("  [dry-run] {$order->order_number} ({$order->status}, {$order->payment_method}, {$order->updated_at->toDateTimeString()})");
            }

            return self::SUCCESS;
        }

        $deleted = 0;

        $query->orderBy('id')->chunkById(100, function ($orders) use (&$deleted) {
            foreach ($orders as $order) {
                $order->delete();
                $deleted++;
                $this->line("Deleted {$order->order_number} ({$order->status})");
            }
        });

        $this->info("Purged {$deleted} cancelled/rejected order(s).");

        return self::SUCCESS;
    }
}
