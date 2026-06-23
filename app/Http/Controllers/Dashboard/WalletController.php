<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\WalletTopupRequest;
use App\Models\SiteSetting;
use App\Services\DashboardPresenter;
use App\Services\OrderService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function __construct(
        protected DashboardPresenter $presenter,
        protected WalletService $wallet,
        protected OrderService $orders,
    ) {}

    protected function shared(): array
    {
        $user = Auth::user();

        return [
            'user' => $this->presenter->userContext($user),
            'notifications' => $this->presenter->notifications($user),
        ];
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $config = SiteSetting::walletConfig();

        abort_unless($config['enabled'], 404);

        return view('dashboard.wallet.index', array_merge($this->shared(), [
            'balance' => $this->wallet->balance($user),
            'balanceLabel' => $this->wallet->formatInr($this->wallet->balance($user)),
            'transactions' => $this->wallet->paginatedHistory($user, 15),
            'config' => $config,
            'activeNav' => 'dashboard.wallet',
        ]));
    }

    public function topup()
    {
        $user = Auth::user();
        $config = SiteSetting::walletConfig();

        abort_unless($config['enabled'], 404);

        return view('dashboard.wallet.topup', array_merge($this->shared(), [
            'balance' => $this->wallet->balance($user),
            'balanceLabel' => $this->wallet->formatInr($this->wallet->balance($user)),
            'topupAmounts' => $config['topup_amounts'],
            'paymentMethods' => $this->orders->availablePaymentMethods('inr'),
            'activeNav' => 'dashboard.wallet',
        ]));
    }

    public function storeTopup(WalletTopupRequest $request)
    {
        $order = $this->orders->createTopupOrder(
            Auth::user(),
            (int) $request->amount,
            $request->payment_method,
        );

        return redirect($this->orders->paymentRoute($order))
            ->with('success', 'Top-up order created. Complete payment to credit your wallet.');
    }
}
