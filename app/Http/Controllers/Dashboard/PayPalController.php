<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayPalController extends Controller
{
    public function __construct(
        protected PayPalService $paypal,
        protected OrderService $orders,
    ) {}

    public function approve(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === Auth::id(), 403);
        abort_unless($order->payment_method === 'paypal', 404);
        abort_if($order->status === 'completed', 422);

        $request->validate([
            'subscription_id' => ['required', 'string', 'max:64'],
        ]);

        if (! $this->paypal->isConfigured()) {
            return response()->json(['message' => 'PayPal is not configured.'], 503);
        }

        $this->paypal->prepareOrderForPayment($order);
        $order->refresh();

        $subscription = $this->paypal->getSubscription($request->subscription_id);

        if (! in_array($subscription['status'] ?? '', ['ACTIVE', 'APPROVED'], true)) {
            return response()->json(['message' => 'PayPal subscription is not active yet.'], 422);
        }

        if (! $this->paypal->subscriptionMatchesOrder($subscription, $order)) {
            return response()->json(['message' => 'Subscription plan does not match this order.'], 422);
        }

        $order->update(['paypal_subscription_id' => $request->subscription_id]);

        if ($order->status !== 'completed') {
            $this->orders->completeOrder($order);
        }

        return response()->json([
            'success' => true,
            'redirect' => route('dashboard.tools'),
        ]);
    }
}
