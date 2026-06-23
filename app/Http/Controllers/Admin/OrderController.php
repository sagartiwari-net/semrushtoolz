<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderInvoiceService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orders,
        protected OrderInvoiceService $invoices,
    ) {}

    public function show(Order $order)
    {
        $order->load(['user', 'plan', 'tool']);

        return view('admin.orders.show', [
            'order' => $order,
            'statusLabel' => $this->orders->statusLabel($order->status),
            'paymentMethodLabel' => $this->orders->paymentMethodLabel($order),
            'formattedTotal' => $this->orders->formatAmount($order),
        ]);
    }

    public function approve(Request $request, Order $order)
    {
        if ($order->status === 'completed') {
            return back()->with('error', 'Order is already completed.');
        }

        $this->orders->completeOrder($order, $request->input('admin_note'));

        $message = $order->isWalletTopup()
            ? "Order {$order->order_number} approved. Wallet credited."
            : "Order {$order->order_number} approved. Subscription activated.";

        return back()->with('success', $message);
    }

    public function reject(Request $request, Order $order)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($order->status === 'completed') {
            return back()->with('error', 'Cannot reject a completed order.');
        }

        $this->orders->rejectOrder($order, $request->reason);

        return back()->with('success', "Order {$order->order_number} rejected.");
    }

    public function refund(Request $request, Order $order)
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $this->orders->refundOrder($order, $request->reason);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Order {$order->order_number} refunded. Affiliate commission reversed if applicable.");
    }

    public function invoice(Order $order): Response
    {
        return $this->invoices->download($order);
    }
}
