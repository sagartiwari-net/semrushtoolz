<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

class ExpirePendingOrders extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Cancel unpaid orders past their expiry time';

    public function handle(OrderService $orders): int
    {
        $expired = Order::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereIn('status', ['pending', 'awaiting_payment'])
            ->get();

        foreach ($expired as $order) {
            $orders->cancelOrder($order);
            $this->line("Cancelled {$order->order_number}");
        }

        $this->info("Expired {$expired->count()} order(s).");

        return self::SUCCESS;
    }
}
