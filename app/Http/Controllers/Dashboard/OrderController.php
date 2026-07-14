<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Services\BuyahrefPaymentService;
use App\Services\DashboardPresenter;
use App\Services\OrderInvoiceService;
use App\Services\OrderService;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function __construct(
        protected DashboardPresenter $presenter,
        protected OrderService $orders,
        protected PayPalService $paypal,
        protected BuyahrefPaymentService $buyahref,
        protected OrderInvoiceService $invoices,
    ) {}

    protected function shared(): array
    {
        $user = Auth::user();

        return [
            'user' => $this->presenter->userContext($user),
            'notifications' => $this->presenter->notifications($user),
        ];
    }

    protected function authorizeOrder(Order $order): void
    {
        abort_unless($order->user_id === Auth::id(), 403);
    }

    public function show(Order $order)
    {
        $this->authorizeOrder($order);
        $order->load(['plan', 'tool']);

        return view('dashboard.orders.show', array_merge($this->shared(), [
            'order' => $order,
            'statusLabel' => $this->orders->statusLabel($order->status),
            'paymentMethodLabel' => $this->orders->paymentMethodLabel($order),
            'formattedTotal' => $this->orders->formatAmount($order),
            'activeNav' => 'dashboard.orders',
        ]));
    }

    public function invoice(Order $order)
    {
        $this->authorizeOrder($order);

        return $this->invoices->download($order);
    }

    public function payUpi(Order $order)
    {
        $this->authorizeOrder($order);
        abort_unless($order->payment_method === 'upi', 404);
        $order->load(['plan', 'tool']);

        if ($order->expires_at && $order->expires_at->isPast() && $order->status !== 'completed') {
            $this->orders->cancelOrder($order);
        }

        $order = $order->fresh();

        if ($order->status === 'completed') {
            return redirect()->route('dashboard.tools')->with('success', 'Payment already completed.');
        }

        if ($this->buyahref->isConfigured()) {
            try {
                $order = $this->buyahref->ensurePaymentUrlForOrder($order);

                if ($order->hub_payment_url && in_array($order->status, ['awaiting_payment', 'pending'], true)) {
                    return redirect()->away($order->hub_payment_url);
                }
            } catch (\RuntimeException $e) {
                report($e);

                return redirect()
                    ->route('dashboard.orders.show', $order)
                    ->with('error', $this->buyahref->userFacingError($e));
            } catch (\Throwable $e) {
                report($e);

                return redirect()
                    ->route('dashboard.orders.show', $order)
                    ->with('error', 'Could not start UPI payment. Please try again or contact support.');
            }
        }

        return view('dashboard.payment.upi', array_merge($this->shared(), [
            'order' => $order,
            'formattedTotal' => $this->orders->formatAmount($order),
            'upi' => config('payments.upi'),
            'expiryMinutes' => app(BuyahrefPaymentService::class)->orderExpiryMinutes(),
            'buyahrefEnabled' => $this->buyahref->isConfigured(),
            'activeNav' => 'dashboard.orders',
        ]));
    }

    public function paymentReturn(Request $request, Order $order)
    {
        $this->authorizeOrder($order);

        if ($this->buyahref->isConfigured()) {
            $verified = $this->buyahref->verify($order->order_number);

            if (($verified['status'] ?? '') === 'success') {
                if ($order->status !== 'completed') {
                    $this->orders->completeOrder($order);
                }

                return redirect()
                    ->route('dashboard.tools')
                    ->with('success', 'Payment successful! Your subscription is now active.');
            }
        }

        if ($request->query('status') === 'failed' || $order->fresh()->status === 'cancelled') {
            return redirect()
                ->route('dashboard.orders.show', $order)
                ->with('error', 'Payment was not completed in time. Please place a new order.');
        }

        return redirect()
            ->route('dashboard.orders.show', $order)
            ->with('info', 'Payment is still processing. Refresh this page in a moment.');
    }

    public function payOffline(Order $order)
    {
        $this->authorizeOrder($order);
        abort_unless($order->payment_method === 'offline', 404);
        $order->load(['plan', 'tool']);

        return view('dashboard.payment.offline', array_merge($this->shared(), [
            'order' => $order,
            'formattedTotal' => $this->orders->formatAmount($order),
            'offline' => config('payments.offline'),
            'whatsappDigits' => SiteSetting::whatsappDigits(),
            'activeNav' => 'dashboard.orders',
        ]));
    }

    public function payPaypal(Order $order)
    {
        try {
            $this->authorizeOrder($order);
            abort_unless($order->payment_method === 'paypal', 404);
            $order->load(['plan', 'tool']);

            if ($order->expires_at && $order->expires_at->isPast() && $order->status !== 'completed') {
                try {
                    $this->orders->cancelOrder($order);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $order = $order->fresh() ?? $order;

            $paypalPlanId = null;
            $monthlyUsd = null;
            $recurringNote = null;
            $paypalError = null;

            if ($this->paypal->isConfigured() && $order->status !== 'completed' && $order->status !== 'cancelled') {
                try {
                    $order = $this->paypal->prepareOrderForPayment($order);
                    $paypalPlanId = $order->paypal_billing_plan_id;
                    $monthlyUsd = $this->paypal->monthlyUsdForOrder($order);
                    $cycles = (int) ($this->paypal->totalCyclesForOrder($order) ?? 0);
                    $recurringNote = $cycles > 0
                        ? 'USD $'.$monthlyUsd.'/month for '.$cycles.' months (auto-billed)'
                        : 'USD $'.$monthlyUsd.'/month recurring until you cancel on PayPal';
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('PayPal prepare failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                    $paypalError = 'PayPal could not start this payment: '.$e->getMessage();
                }
            } elseif (! $this->paypal->isConfigured()) {
                $paypalError = 'PayPal is not configured. Ask admin to enable PayPal in Payment Integration.';
            }

            return view('dashboard.payment.paypal', array_merge($this->shared(), [
                'order' => $order,
                'formattedTotal' => $this->orders->formatAmount($order),
                'paypal' => $this->paypal->config(),
                'paypalPlanId' => $paypalPlanId,
                'monthlyUsd' => $monthlyUsd ?? (float) $order->total,
                'recurringNote' => $recurringNote,
                'paypalError' => $paypalError,
                'approveUrl' => route('dashboard.orders.paypal.approve', $order),
                'activeNav' => 'dashboard.orders',
            ]));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PayPal pay page crashed', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return response()->view('dashboard.payment.paypal-error', [
                'message' => $e->getMessage(),
                'orderId' => $order->id ?? null,
            ], 200);
        }
    }

    public function uploadProof(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        abort_unless($order->payment_method === 'offline', 404);
        abort_unless(in_array($order->status, ['awaiting_proof', 'rejected']), 422);

        $request->validate([
            'payment_proof' => ['required', 'image', 'max:5120'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($order->payment_proof) {
            Storage::disk('public')->delete($order->payment_proof);
        }

        $path = $request->file('payment_proof')->store('payment-proofs', 'public');

        $order->update([
            'payment_proof' => $path,
            'payment_note' => $request->input('payment_note'),
            'status' => 'verifying',
        ]);

        return back()->with('success', 'Payment proof uploaded. We will verify within 24 hours.');
    }

    public function status(Order $order)
    {
        $this->authorizeOrder($order);

        return response()->json([
            'status' => $order->status,
            'label' => $this->orders->statusLabel($order->status),
            'completed' => $order->status === 'completed',
            'expired' => $order->status === 'cancelled',
        ]);
    }
}
