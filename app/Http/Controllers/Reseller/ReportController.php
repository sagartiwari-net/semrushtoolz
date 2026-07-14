<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerLedger;
use App\Models\ResellerProvision;
use App\Models\Tool;
use App\Services\ResellerCancelService;
use App\Support\TablePageSize;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ResellerCancelService $cancels,
    ) {}

    public function index(Request $request)
    {
        $reseller = $request->user()->load('resellerProfile');
        $perPage = TablePageSize::resolve($request);
        $tab = $request->query('tab', 'provisions');
        if (! in_array($tab, ['provisions', 'cancelled', 'ledger'], true)) {
            $tab = 'provisions';
        }

        $tools = Tool::query()
            ->whereIn('id', ResellerProvision::query()
                ->where('reseller_user_id', $reseller->id)
                ->whereNotNull('tool_id')
                ->distinct()
                ->pluck('tool_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $filters = [
            'q' => $request->q,
            'tool_id' => $request->tool_id,
            'flag' => $request->flag ?? 'all',
            'type' => $request->type ?? 'all',
            'from' => $request->from,
            'to' => $request->to,
        ];

        $provisions = null;
        $ledger = null;

        if ($tab === 'provisions' || $tab === 'cancelled') {
            $provisionsQuery = ResellerProvision::query()
                ->where('reseller_user_id', $reseller->id)
                ->with('tool');

            if ($tab === 'cancelled') {
                $provisionsQuery->whereNotNull('cancelled_at');
            } else {
                // Active access report: keep password resets + live grants; cancelled have their own tab.
                $provisionsQuery->whereNull('cancelled_at');
            }

            if ($q = trim((string) $request->q)) {
                $provisionsQuery->where('end_user_email', 'like', "%{$q}%");
            }
            if ($request->filled('tool_id')) {
                $provisionsQuery->where('tool_id', (int) $request->tool_id);
            }
            if ($tab === 'provisions' && $request->filled('flag') && $request->flag !== 'all') {
                match ($request->flag) {
                    'new' => $provisionsQuery->where('was_new_user', true)->where('password_reset', false),
                    'existing' => $provisionsQuery->where('was_new_user', false)->where('password_reset', false),
                    'password_reset' => $provisionsQuery->where('password_reset', true),
                    default => null,
                };
            }
            if ($request->filled('from')) {
                $dateCol = $tab === 'cancelled' ? 'cancelled_at' : 'created_at';
                $provisionsQuery->whereDate($dateCol, '>=', $request->from);
            }
            if ($request->filled('to')) {
                $dateCol = $tab === 'cancelled' ? 'cancelled_at' : 'created_at';
                $provisionsQuery->whereDate($dateCol, '<=', $request->to);
            }

            $provisions = $provisionsQuery
                ->orderByDesc($tab === 'cancelled' ? 'cancelled_at' : 'id')
                ->paginate($perPage)
                ->withQueryString();
        } else {
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
        }

        return view('reseller.reports', [
            'tab' => $tab,
            'provisions' => $provisions,
            'ledger' => $ledger,
            'perPage' => $perPage,
            'tools' => $tools,
            'filters' => $filters,
            'cancelLimit' => $this->cancels->monthlyCancelLimit($reseller),
            'cancelsRemaining' => $this->cancels->remainingCancelsThisMonth($reseller),
            'cancels' => $this->cancels,
        ]);
    }
}
