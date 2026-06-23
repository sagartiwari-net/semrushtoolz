<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\WalletExportService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $wallet,
    ) {}

    public function index(Request $request)
    {
        $type = $request->query('type');
        $search = trim((string) $request->query('q', ''));

        return view('admin.wallet.index', [
            'transactions' => $this->wallet->paginatedAll(
                filled($type) ? $type : null,
                $search !== '' ? $search : null,
            ),
            'types' => [
                WalletTransaction::TYPE_TOPUP => 'Top-up',
                WalletTransaction::TYPE_AFFILIATE_TRANSFER => 'Affiliate transfer',
                WalletTransaction::TYPE_AFFILIATE_BONUS => 'Affiliate bonus',
                WalletTransaction::TYPE_CASHBACK => 'Cashback',
                WalletTransaction::TYPE_PURCHASE => 'Purchase',
                WalletTransaction::TYPE_REFUND => 'Refund',
                WalletTransaction::TYPE_ADMIN_ADJUSTMENT => 'Admin adjustment',
            ],
            'filters' => [
                'type' => $type,
                'q' => $search,
            ],
            'walletEnabled' => SiteSetting::walletConfig()['enabled'],
            'active' => 'admin.wallet',
        ]);
    }

    public function adjust(Request $request)
    {
        if (! SiteSetting::walletConfig()['enabled']) {
            return back()->with('error', 'Wallet is disabled. Enable it in Settings before making adjustments.');
        }

        $data = $request->validate([
            'user_email' => ['required', 'email', 'exists:users,email'],
            'amount' => ['required', 'numeric', 'not_in:0'],
            'note' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $data['user_email'])->firstOrFail();

        try {
            $this->wallet->adminAdjust($user, (float) $data['amount'], $data['note'], $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Wallet adjusted for '.$user->email.'. New balance: '.$this->wallet->formatInr($this->wallet->balance($user)));
    }

    public function export(Request $request, WalletExportService $export)
    {
        $type = $request->query('type');
        $search = trim((string) $request->query('q', ''));

        return $export->download(
            filled($type) ? $type : null,
            $search !== '' ? $search : null,
        );
    }
}
