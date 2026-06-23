<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class BuyahrefWebhookService
{
    public function __construct(
        protected OrderService $orders,
    ) {}

    public function handle(array $payload): void
    {
        $event = $payload['event'] ?? '';
        $status = $payload['status'] ?? '';
        $orderId = $payload['order_id'] ?? '';

        if ($event !== 'payment.success' || $status !== 'success' || $orderId === '') {
            return;
        }

        $order = Order::where('order_number', $orderId)->first();

        if (! $order) {
            Log::warning('Buyahref webhook: order not found', ['order_id' => $orderId]);

            return;
        }

        if ($order->status === 'completed') {
            return;
        }

        $this->orders->completeOrder($order);

        Log::info('Buyahref webhook: order completed', [
            'order_number' => $order->order_number,
            'hub_order_id' => $payload['hub_order_id'] ?? null,
        ]);
    }
}
