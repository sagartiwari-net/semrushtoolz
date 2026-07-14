<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BuyahrefPaymentService;
use App\Services\OrderService;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected OrderService $orders,
        protected BuyahrefPaymentService $buyahref,
        protected PayPalService $paypal,
    ) {}

    protected function authorizeTopup(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless($order->isResellerBalanceTopup(), 404);
    }

    public function payUpi(Request $request, Order $order)
    {
        $this->authorizeTopup($request, $order);
        abort_unless($order->payment_method === 'upi', 404);

        if ($order->expires_at && $order->expires_at->isPast() && $order->status !== 'completed') {
            $this->orders->cancelOrder($order);
            $order = $order->fresh();
        }

        if ($order->status === 'completed') {
            return redirect()->route('reseller.balance.index')
                ->with('success', 'Payment already completed. Balance updated.');
        }

        if ($this->buyahref->isConfigured()) {
            try {
                $order = $this->buyahref->ensurePaymentUrlForOrder($order);

                if ($order->hub_payment_url && in_array($order->status, ['awaiting_payment', 'pending'], true)) {
                    return redirect()->away($order->hub_payment_url);
                }
            } catch (\RuntimeException $e) {
                report($e);

                return redirect()->route('reseller.balance.index')
                    ->with('error', $this->buyahref->userFacingError($e));
            } catch (\Throwable $e) {
                report($e);

                return redirect()->route('reseller.balance.index')
                    ->with('error', 'Could not start UPI payment. Please try again.');
            }
        }

        return view('reseller.payment.upi', [
            'order' => $order,
            'formattedTotal' => $this->orders->formatAmount($order),
            'creditInr' => $order->creditAmountInr(),
            'expiryMinutes' => $this->buyahref->orderExpiryMinutes(),
        ]);
    }

    public function paymentReturn(Request $request, Order $order)
    {
        $this->authorizeTopup($request, $order);

        if ($this->buyahref->isConfigured()) {
            $verified = $this->buyahref->verify($order->order_number);

            if (($verified['status'] ?? '') === 'success') {
                if ($order->status !== 'completed') {
                    $this->orders->completeOrder($order);
                }

                return redirect()->route('reseller.balance.index')
                    ->with('success', 'Payment successful. ₹'.number_format($order->fresh()->creditAmountInr(), 2).' added to your balance.');
            }
        }

        if ($request->query('status') === 'failed' || $order->fresh()->status === 'cancelled') {
            return redirect()->route('reseller.balance.index')
                ->with('error', 'Payment was not completed. Please try again.');
        }

        return redirect()->route('reseller.balance.index')
            ->with('info', 'Payment is still processing. Refresh balance in a moment.');
    }

    public function payPaypal(Request $request, Order $order)
    {
        $this->authorizeTopup($request, $order);
        abort_unless($order->payment_method === 'paypal', 404);

        if ($order->expires_at && $order->expires_at->isPast() && $order->status !== 'completed') {
            $this->orders->cancelOrder($order);
            $order = $order->fresh();
        }

        $paypalPlanId = null;
        $chargeUsd = null;
        $paypalError = null;

        if ($this->paypal->isConfigured() && $order->status !== 'completed' && $order->status !== 'cancelled') {
            try {
                $order = $this->paypal->prepareOrderForPayment($order);
                $paypalPlanId = $order->paypal_billing_plan_id;
                $chargeUsd = $this->paypal->monthlyUsdForOrder($order);
            } catch (\Throwable $e) {
                report($e);
                $paypalError = 'PayPal could not start this payment: '.$e->getMessage();
            }
        }

        return view('reseller.payment.paypal', [
            'order' => $order,
            'paypal' => $this->paypal->config(),
            'paypalPlanId' => $paypalPlanId,
            'chargeUsd' => $chargeUsd,
            'creditInr' => $order->creditAmountInr(),
            'paypalError' => $paypalError,
            'approveUrl' => route('reseller.balance.paypal.approve', $order),
        ]);
    }

    public function approvePaypal(Request $request, Order $order): JsonResponse
    {
        $this->authorizeTopup($request, $order);
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
            return response()->json(['message' => 'PayPal payment is not active yet.'], 422);
        }

        if (! $this->paypal->subscriptionMatchesOrder($subscription, $order)) {
            return response()->json(['message' => 'PayPal plan does not match this top-up.'], 422);
        }

        $order->update(['paypal_subscription_id' => $request->subscription_id]);

        if ($order->status !== 'completed') {
            $this->orders->completeOrder($order);
        }

        // One-cycle plan — cancel so it does not linger as an open subscription.
        try {
            $this->paypal->cancelSubscription($request->subscription_id, 'Reseller balance top-up (one-time)');
        } catch (\Throwable) {
            // ignore — cycles=1 already ends after first charge
        }

        return response()->json([
            'success' => true,
            'redirect' => route('reseller.balance.index'),
        ]);
    }

    public function status(Request $request, Order $order): JsonResponse
    {
        $this->authorizeTopup($request, $order);

        return response()->json([
            'status' => $order->fresh()->status,
            'completed' => $order->status === 'completed',
            'expired' => $order->expires_at?->isPast() && $order->status !== 'completed',
        ]);
    }
}
