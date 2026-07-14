<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerBalanceRequest;
use App\Models\ResellerLedger;
use App\Services\BuyahrefPaymentService;
use App\Services\OrderService;
use App\Services\ResellerBalanceService;
use App\Support\TablePageSize;
use Illuminate\Http\Request;
use RuntimeException;

class BalanceController extends Controller
{
    public function __construct(
        protected ResellerBalanceService $balances,
        protected OrderService $orders,
        protected BuyahrefPaymentService $buyahref,
    ) {}

    public function index(Request $request)
    {
        $reseller = $request->user();
        $perPage = TablePageSize::resolve($request);
        $balance = $this->balances->balance($reseller);
        $tab = $request->query('tab', 'ledger');
        if (! in_array($tab, ['requests', 'ledger'], true)) {
            $tab = 'ledger';
        }

        $filters = [
            'q' => $request->q,
            'type' => $request->type ?? 'all',
            'request_status' => $request->request_status ?? 'all',
            'from' => $request->from,
            'to' => $request->to,
        ];

        $ledger = null;
        $requests = null;

        if ($tab === 'ledger') {
            $ledgerQuery = ResellerLedger::query()
                ->where('reseller_user_id', $reseller->id);

            if ($q = trim((string) $request->q)) {
                $ledgerQuery->where(function ($builder) use ($q) {
                    $builder->where('type', 'like', "%{$q}%")
                        ->orWhere('meta', 'like', "%{$q}%");
                });
            }
            if ($request->filled('type') && $request->type !== 'all') {
                $ledgerQuery->where('type', $request->type);
            }
            if ($request->filled('from')) {
                $ledgerQuery->whereDate('created_at', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $ledgerQuery->whereDate('created_at', '<=', $request->to);
            }

            $ledger = $ledgerQuery->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $requestsQuery = ResellerBalanceRequest::query()
                ->where('reseller_user_id', $reseller->id);

            if ($request->filled('request_status') && $request->request_status !== 'all') {
                $requestsQuery->where('status', $request->request_status);
            }
            if ($request->filled('from')) {
                $requestsQuery->whereDate('created_at', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $requestsQuery->whereDate('created_at', '<=', $request->to);
            }

            $requests = $requestsQuery->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString();
        }

        $hasPending = ResellerBalanceRequest::query()
            ->where('reseller_user_id', $reseller->id)
            ->where('status', ResellerBalanceRequest::STATUS_PENDING)
            ->exists();

        $paymentMethods = $this->resellerPaymentMethods();

        return view('reseller.balance', [
            'tab' => $tab,
            'balance' => $balance,
            'ledger' => $ledger,
            'requests' => $requests,
            'hasPending' => $hasPending,
            'perPage' => $perPage,
            'filters' => $filters,
            'paymentMethods' => $paymentMethods,
            'upiAvailable' => $this->buyahref->isConfigured(),
        ]);
    }

    public function pay(Request $request)
    {
        $methods = collect($this->resellerPaymentMethods())->pluck('id')->all();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:upi'],
        ]);

        if ($methods === [] || ! in_array($data['payment_method'], $methods, true)) {
            return back()->withInput()->with('error', 'UPI is not available. Use admin top-up request for cash / manual credit.');
        }

        try {
            $order = $this->orders->createResellerBalanceTopupOrder(
                $request->user(),
                (float) $data['amount'],
                $data['payment_method'],
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to($this->orders->paymentRoute($order));
    }

    public function requestTopUp(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->balances->requestTopUp($request->user(), (float) $data['amount'], $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('reseller.balance.index', ['tab' => 'requests'])
            ->with('success', 'Top-up request submitted. Admin will approve after cash / manual payment.');
    }

    /** @return list<array{id: string, name: string, desc: string}> */
    protected function resellerPaymentMethods(): array
    {
        // Reseller balance top-up: UPI only. Cash goes through admin request.
        if (! $this->buyahref->isConfigured()) {
            return [];
        }

        return [
            [
                'id' => 'upi',
                'name' => 'UPI',
                'desc' => 'Pay in INR — auto-verified, credited instantly',
            ],
        ];
    }
}
