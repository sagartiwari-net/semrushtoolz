<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class PurgeCancelledUpiOrders extends Command
{
    protected $signature = 'orders:purge-cancelled-upi {--days=3 : Days after cancel/reject before deletion} {--dry-run : List only, do not delete}';

    protected $description = 'Permanently delete cancelled and rejected orders whose order date is older than N days';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        // Match admin Orders "Date" column (created_at), not last updated_at.
        $cutoffDate = now()->subDays($days)->toDateString();

        $query = Order::query()
            ->whereIn('status', ['cancelled', 'rejected'])
            ->whereDate('created_at', '<=', $cutoffDate);

        if ($this->option('dry-run')) {
            $orders = $query->orderBy('created_at')->get(['id', 'order_number', 'status', 'payment_method', 'created_at', 'updated_at']);

            if ($orders->isEmpty()) {
                $this->info("No cancelled/rejected orders with order date on or before {$cutoffDate}.");

                return self::SUCCESS;
            }

            $this->info("Found {$orders->count()} order(s) with order date on or before {$cutoffDate} (older than {$days} day(s)).");

            foreach ($orders as $order) {
                $this->line("  [dry-run] {$order->order_number} ({$order->status}, {$order->payment_method}, ordered {$order->created_at->toDateString()})");
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
