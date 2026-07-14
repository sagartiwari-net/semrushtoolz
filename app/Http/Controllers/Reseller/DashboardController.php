<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerBalanceRequest;
use App\Models\ResellerProvision;
use App\Services\ResellerBalanceService;
use App\Services\ResellerCancelService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected ResellerBalanceService $balances,
        protected ResellerCancelService $cancels,
    ) {}

    public function index(Request $request)
    {
        $reseller = $request->user()->load('resellerProfile');

        $balance = $this->balances->balance($reseller);
        $pendingRequests = ResellerBalanceRequest::query()
            ->where('reseller_user_id', $reseller->id)
            ->where('status', ResellerBalanceRequest::STATUS_PENDING)
            ->count();

        $recentProvisions = ResellerProvision::query()
            ->where('reseller_user_id', $reseller->id)
            ->where('password_reset', false)
            ->with('tool')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('reseller.dashboard', [
            'balance' => $balance,
            'pendingRequests' => $pendingRequests,
            'recentProvisions' => $recentProvisions,
            'cancelLimit' => $this->cancels->monthlyCancelLimit($reseller),
            'cancelsRemaining' => $this->cancels->remainingCancelsThisMonth($reseller),
            'cancels' => $this->cancels,
        ]);
    }
}
