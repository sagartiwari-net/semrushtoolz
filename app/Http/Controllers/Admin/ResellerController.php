<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ResellerBalanceRequest;
use App\Models\ResellerLedger;
use App\Models\ResellerProfile;
use App\Models\ResellerProvision;
use App\Models\Tool;
use App\Models\User;
use App\Services\ResellerBalanceService;
use App\Services\ResellerCancelService;
use App\Services\ResellerPricingService;
use App\Services\AdminAccountDeletionService;
use App\Support\TablePageSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class ResellerController extends Controller
{
    public function __construct(
        protected ResellerBalanceService $balances,
        protected ResellerPricingService $pricing,
        protected ResellerCancelService $cancels,
    ) {}

    public function index(Request $request)
    {
        $perPage = TablePageSize::resolve($request);

        $query = User::query()
            ->where('role', 'reseller')
            ->with(['resellerProfile', 'resellerBalance']);

        if ($q = trim((string) $request->q)) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('status', 'active')
                    ->whereHas('resellerProfile', fn ($p) => $p->where('is_active', true));
            } elseif ($request->status === 'inactive') {
                $query->where(function ($builder) {
                    $builder->where('status', 'blocked')
                        ->orWhereHas('resellerProfile', fn ($p) => $p->where('is_active', false))
                        ->orWhereDoesntHave('resellerProfile');
                });
            }
        }

        $resellers = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        $pendingRequests = ResellerBalanceRequest::query()
            ->where('status', ResellerBalanceRequest::STATUS_PENDING)
            ->count();

        return view('admin.resellers.index', [
            'resellers' => $resellers,
            'pendingRequests' => $pendingRequests,
            'perPage' => $perPage,
            'filters' => [
                'q' => $request->q,
                'status' => $request->status ?? 'all',
            ],
        ]);
    }

    public function create()
    {
        return view('admin.resellers.form', [
            'reseller' => new User(['role' => 'reseller', 'status' => 'active']),
            'profile' => new ResellerProfile(['is_active' => true]),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'monthly_cancel_limit' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $reseller = DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'role' => 'reseller',
                'status' => 'active',
                'email_verified_at' => now(),
                'referral_code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $data['name']), 0, 4).random_int(1000, 9999)),
            ]);

            $cancelLimit = array_key_exists('monthly_cancel_limit', $data) && $data['monthly_cancel_limit'] !== null && $data['monthly_cancel_limit'] !== ''
                ? (int) $data['monthly_cancel_limit']
                : null;
            if ($cancelLimit !== null && $cancelLimit <= 0) {
                $cancelLimit = null;
            }

            ResellerProfile::create([
                'user_id' => $user->id,
                'is_active' => $request->has('is_active'),
                'notes' => $data['notes'] ?? null,
                'monthly_cancel_limit' => $cancelLimit,
            ]);

            $this->balances->ensureBalanceRow($user);

            $opening = (float) ($data['opening_balance'] ?? 0);
            if ($opening > 0) {
                $this->balances->credit($user, $opening, ResellerLedger::TYPE_CREDIT_ADMIN, [
                    'admin_id' => $request->user()->id,
                    'note' => 'Opening balance',
                ]);
            }

            return $user;
        });

        return redirect()->route('admin.resellers.show', $reseller)
            ->with('success', 'Reseller account created.');
    }

    public function show(Request $request, User $reseller)
    {
        $this->assertReseller($reseller);

        $reseller->load(['resellerProfile', 'resellerBalance']);
        $perPage = TablePageSize::resolve($request);
        $tab = $request->query('tab', 'overview');
        if (! in_array($tab, ['overview', 'pricing', 'provisions', 'cancelled', 'ledger', 'requests'], true)) {
            $tab = 'overview';
        }

        $data = [
            'reseller' => $reseller,
            'tab' => $tab,
            'perPage' => $perPage,
            'ledger' => null,
            'provisions' => null,
            'requests' => null,
            'tools' => null,
            'defaults' => [],
            'overrides' => [],
            'filters' => [
                'q' => $request->q,
                'type' => $request->type ?? 'all',
                'flag' => $request->flag ?? 'all',
                'tool_id' => $request->tool_id,
                'status' => $request->status ?? 'all',
                'from' => $request->from,
                'to' => $request->to,
            ],
        ];

        if ($tab === 'overview') {
            $data['requests'] = ResellerBalanceRequest::query()
                ->where('reseller_user_id', $reseller->id)
                ->orderByDesc('id')
                ->limit(5)
                ->get();
        } elseif ($tab === 'pricing') {
            $toolsQuery = Tool::query()
                ->where('is_active', true)
                ->where('show_in_shop', true)
                ->orderBy('sort_order')
                ->orderBy('name');

            if ($q = trim((string) $request->q)) {
                $toolsQuery->where(function ($builder) use ($q) {
                    $builder->where('name', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            }
            if ($request->filled('sellable_type') && $request->sellable_type !== 'all') {
                $toolsQuery->where('sellable_type', $request->sellable_type);
            }

            $data['tools'] = $toolsQuery->paginate($perPage)->withQueryString();
            $data['defaults'] = $this->pricing->defaultsMap();
            $data['overrides'] = $this->pricing->overridesMap($reseller);
            $data['filters']['sellable_type'] = $request->sellable_type ?? 'all';
        } elseif ($tab === 'provisions' || $tab === 'cancelled') {
            $query = ResellerProvision::query()
                ->where('reseller_user_id', $reseller->id)
                ->with('tool');

            if ($tab === 'cancelled') {
                $query->whereNotNull('cancelled_at');
            } else {
                $query->whereNull('cancelled_at');
            }

            if ($q = trim((string) $request->q)) {
                $query->where('end_user_email', 'like', "%{$q}%");
            }
            if ($request->filled('tool_id')) {
                $query->where('tool_id', (int) $request->tool_id);
            }
            if ($tab === 'provisions' && $request->filled('flag') && $request->flag !== 'all') {
                match ($request->flag) {
                    'new' => $query->where('was_new_user', true)->where('password_reset', false),
                    'existing' => $query->where('was_new_user', false)->where('password_reset', false),
                    'password_reset' => $query->where('password_reset', true),
                    default => null,
                };
            }
            if ($request->filled('from')) {
                $dateCol = $tab === 'cancelled' ? 'cancelled_at' : 'created_at';
                $query->whereDate($dateCol, '>=', $request->from);
            }
            if ($request->filled('to')) {
                $dateCol = $tab === 'cancelled' ? 'cancelled_at' : 'created_at';
                $query->whereDate($dateCol, '<=', $request->to);
            }

            $data['provisions'] = $query
                ->orderByDesc($tab === 'cancelled' ? 'cancelled_at' : 'id')
                ->paginate($perPage)
                ->withQueryString();
            $data['tools'] = Tool::query()->orderBy('name')->get(['id', 'name']);
        } elseif ($tab === 'ledger') {
            $query = ResellerLedger::query()
                ->where('reseller_user_id', $reseller->id);

            if ($q = trim((string) $request->q)) {
                $query->where(function ($builder) use ($q) {
                    $builder->where('type', 'like', "%{$q}%")
                        ->orWhere('meta', 'like', "%{$q}%");
                });
            }
            if ($request->filled('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }
            if ($request->filled('from')) {
                $query->whereDate('created_at', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('created_at', '<=', $request->to);
            }

            $data['ledger'] = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
        } else {
            $query = ResellerBalanceRequest::query()
                ->where('reseller_user_id', $reseller->id);

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }
            if ($request->filled('from')) {
                $query->whereDate('created_at', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('created_at', '<=', $request->to);
            }

            $data['requests'] = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
        }

        return view('admin.resellers.show', $data);
    }

    public function edit(User $reseller)
    {
        $this->assertReseller($reseller);

        return view('admin.resellers.form', [
            'reseller' => $reseller,
            'profile' => $reseller->resellerProfile ?? new ResellerProfile(['is_active' => true]),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, User $reseller)
    {
        $this->assertReseller($reseller);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($reseller->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'blocked'])],
            'monthly_cancel_limit' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $reseller->fill([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $reseller->password = $data['password'];
        }

        $reseller->save();

        $cancelLimit = array_key_exists('monthly_cancel_limit', $data) && $data['monthly_cancel_limit'] !== null && $data['monthly_cancel_limit'] !== ''
            ? (int) $data['monthly_cancel_limit']
            : null;
        if ($cancelLimit !== null && $cancelLimit <= 0) {
            $cancelLimit = null;
        }

        $reseller->resellerProfile()->updateOrCreate(
            ['user_id' => $reseller->id],
            [
                'is_active' => $request->has('is_active'),
                'notes' => $data['notes'] ?? null,
                'monthly_cancel_limit' => $cancelLimit,
            ]
        );

        return redirect()->route('admin.resellers.show', $reseller)
            ->with('success', 'Reseller updated.');
    }

    public function credit(Request $request, User $reseller)
    {
        $this->assertReseller($reseller);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->balances->credit($reseller, (float) $data['amount'], ResellerLedger::TYPE_CREDIT_ADMIN, [
                'admin_id' => $request->user()->id,
                'note' => $data['note'] ?? null,
            ]);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.resellers.show', ['reseller' => $reseller, 'tab' => 'overview'])
            ->with('success', 'Balance credited.');
    }

    public function updatePricing(Request $request, User $reseller)
    {
        $this->assertReseller($reseller);

        $data = $request->validate([
            'prices' => ['nullable', 'array'],
            'prices.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $tools = Tool::query()->whereIn('id', array_keys($data['prices'] ?? []))->get()->keyBy('id');

        foreach ($data['prices'] ?? [] as $toolId => $price) {
            $tool = $tools->get((int) $toolId);
            if (! $tool) {
                continue;
            }

            $raw = $request->input("prices.{$toolId}");
            if ($raw === null || $raw === '') {
                $this->pricing->upsertResellerPrice($reseller, $tool, null);
            } else {
                $this->pricing->upsertResellerPrice($reseller, $tool, (int) $price);
            }
        }

        return redirect()->route('admin.resellers.show', array_merge(
            ['reseller' => $reseller, 'tab' => 'pricing'],
            $request->only(['q', 'sellable_type', 'per_page', 'page'])
        ))->with('success', 'Per-reseller pricing saved.');
    }

    public function cancelProvision(Request $request, User $reseller, ResellerProvision $provision)
    {
        $this->assertReseller($reseller);
        abort_unless((int) $provision->reseller_user_id === (int) $reseller->id, 404);

        try {
            $result = $this->cancels->cancelByAdmin($request->user(), $provision);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.resellers.show', [
            'reseller' => $reseller,
            'tab' => 'cancelled',
        ])->with(
            'success',
            'Provision cancelled. ₹'.number_format($result['refund'], 2).' refunded to reseller balance. Record kept in Cancelled report.'
        );
    }

    public function requests(Request $request)
    {
        $perPage = TablePageSize::resolve($request);

        $query = ResellerBalanceRequest::query()
            ->with(['reseller', 'reviewer']);

        if ($q = trim((string) $request->q)) {
            $query->whereHas('reseller', function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('reseller_id')) {
            $query->where('reseller_user_id', (int) $request->reseller_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $requests = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $resellers = User::query()->where('role', 'reseller')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.resellers.requests', [
            'requests' => $requests,
            'resellers' => $resellers,
            'perPage' => $perPage,
            'filters' => [
                'q' => $request->q,
                'status' => $request->status ?? 'all',
                'reseller_id' => $request->reseller_id,
                'from' => $request->from,
                'to' => $request->to,
            ],
        ]);
    }

    public function approveRequest(Request $request, ResellerBalanceRequest $balanceRequest)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->balances->approveRequest($balanceRequest, $request->user(), $data['admin_note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Balance request approved and credited.');
    }

    public function rejectRequest(Request $request, ResellerBalanceRequest $balanceRequest)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->balances->rejectRequest($balanceRequest, $request->user(), $data['admin_note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Balance request rejected.');
    }

    public function reports(Request $request)
    {
        $perPage = TablePageSize::resolve($request);
        $query = ResellerProvision::query()->with(['reseller', 'tool']);

        if ($q = trim((string) $request->q)) {
            $query->where('end_user_email', 'like', "%{$q}%");
        }
        if ($request->filled('reseller_id')) {
            $query->where('reseller_user_id', (int) $request->reseller_id);
        }
        if ($request->filled('tool_id')) {
            $query->where('tool_id', (int) $request->tool_id);
        }
        if ($request->filled('flag') && $request->flag !== 'all') {
            match ($request->flag) {
                'new' => $query->where('was_new_user', true)->where('password_reset', false)->whereNull('cancelled_at'),
                'existing' => $query->where('was_new_user', false)->where('password_reset', false)->whereNull('cancelled_at'),
                'password_reset' => $query->where('password_reset', true)->whereNull('cancelled_at'),
                'cancelled' => $query->whereNotNull('cancelled_at'),
                default => null,
            };
        }
        if ($request->filled('from')) {
            $dateCol = ($request->flag ?? '') === 'cancelled' ? 'cancelled_at' : 'created_at';
            $query->whereDate($dateCol, '>=', $request->from);
        }
        if ($request->filled('to')) {
            $dateCol = ($request->flag ?? '') === 'cancelled' ? 'cancelled_at' : 'created_at';
            $query->whereDate($dateCol, '<=', $request->to);
        }

        $provisions = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        $resellers = User::query()->where('role', 'reseller')->orderBy('name')->get(['id', 'name', 'email']);
        $tools = Tool::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.resellers.reports', [
            'provisions' => $provisions,
            'resellers' => $resellers,
            'tools' => $tools,
            'perPage' => $perPage,
            'filters' => [
                'q' => $request->q,
                'reseller_id' => $request->reseller_id,
                'tool_id' => $request->tool_id,
                'flag' => $request->flag ?? 'all',
                'from' => $request->from,
                'to' => $request->to,
            ],
        ]);
    }

    public function destroy(Request $request, User $reseller, AdminAccountDeletionService $deletion)
    {
        $this->assertReseller($reseller);
        $email = $reseller->email;

        try {
            $deletion->deleteReseller($reseller, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.resellers.index')
            ->with('success', "Deleted reseller {$email}. End-user accounts they created were kept.");
    }

    protected function assertReseller(User $user): void
    {
        abort_unless($user->isReseller(), 404);
    }
}
