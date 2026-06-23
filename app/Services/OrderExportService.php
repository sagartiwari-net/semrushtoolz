<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderExportService
{
    public function __construct(
        protected OrderService $orders,
    ) {}

    public function download(Request $request): StreamedResponse
    {
        $orders = $this->filteredQuery($request)->get();
        $filename = 'orders-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Order Number',
                'User Name',
                'User Email',
                'Item',
                'Order Type',
                'Duration (months)',
                'Currency',
                'Subtotal',
                'Discount',
                'Coupon Code',
                'GST Amount',
                'Total',
                'Wallet Used',
                'Cashback Awarded',
                'Payment Method',
                'Status',
                'Paid At',
                'Created At',
            ]);

            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->order_number,
                    $order->user?->name ?? '',
                    $order->user?->email ?? '',
                    $order->purchasedItemName(),
                    $order->order_type ?? 'subscription',
                    $order->duration_months,
                    strtoupper($order->currency),
                    $order->subtotal,
                    $order->discount,
                    $order->coupon_code ?? '',
                    $order->gst_amount,
                    $order->total,
                    $order->wallet_amount_used,
                    $order->wallet_cashback_amount,
                    $this->orders->paymentMethodLabel($order),
                    $order->status,
                    $order->paid_at?->format('Y-m-d H:i:s') ?? '',
                    $order->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function filteredQuery(Request $request)
    {
        $query = Order::with(['user', 'plan', 'tool'])->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('method') && $request->method !== 'all') {
            $query->where('payment_method', $request->method);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }
}
