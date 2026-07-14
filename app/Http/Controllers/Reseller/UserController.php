<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerProvision;
use App\Models\Tool;
use App\Services\ResellerProvisionService;
use App\Support\TablePageSize;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected ResellerProvisionService $provisions,
    ) {}

    public function index(Request $request)
    {
        $perPage = TablePageSize::resolve($request);
        $reseller = $request->user();

        $query = $this->provisions->managedUsersQuery($reseller)
            ->with(['subscriptions' => fn ($q) => $q->where('status', 'active')->where('ends_at', '>', now())->with('tool')]);

        if ($q = trim((string) $request->q)) {
            $query->where(function ($builder) use ($q) {
                $builder->where('email', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            });
        }

        if ($request->filled('tool_id')) {
            $toolId = (int) $request->tool_id;
            $query->where(function ($builder) use ($toolId, $reseller) {
                $builder->whereHas('subscriptions', function ($sub) use ($toolId) {
                    $sub->where('tool_id', $toolId)
                        ->where('status', 'active')
                        ->where('ends_at', '>', now());
                })->orWhereIn('id', ResellerProvision::query()
                    ->where('reseller_user_id', $reseller->id)
                    ->where('tool_id', $toolId)
                    ->where('password_reset', false)
                    ->select('end_user_id'));
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $users = $query->paginate($perPage, ['*'], 'users_page')->withQueryString();

        $lastProvisions = ResellerProvision::query()
            ->where('reseller_user_id', $reseller->id)
            ->where('password_reset', false)
            ->whereIn('end_user_id', $users->pluck('id'))
            ->with('tool')
            ->orderByDesc('id')
            ->get()
            ->unique('end_user_id')
            ->keyBy('end_user_id');

        $tools = Tool::query()
            ->whereIn('id', ResellerProvision::query()
                ->where('reseller_user_id', $reseller->id)
                ->whereNotNull('tool_id')
                ->distinct()
                ->pluck('tool_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reseller.users', [
            'users' => $users,
            'lastProvisions' => $lastProvisions,
            'perPage' => $perPage,
            'tools' => $tools,
            'filters' => [
                'q' => $request->q,
                'tool_id' => $request->tool_id,
                'from' => $request->from,
                'to' => $request->to,
            ],
        ]);
    }

    public function resetPasswordForm()
    {
        return view('reseller.password-reset');
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
        ]);

        try {
            $result = $this->provisions->resetPassword($request->user(), $data['email']);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with([
            'success' => 'Password reset successfully.',
            'credentials' => [
                'access_url' => $result['access_url'],
                'email' => $result['user']->email,
                'password' => $result['plain_password'],
            ],
        ]);
    }
}
