<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AdminUserService;
use App\Services\SecurityMonitorService;
use App\Services\UserSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct(
        protected AdminUserService $users,
        protected SecurityMonitorService $security,
        protected UserSessionService $sessions,
    ) {}

    public function show(User $user)
    {
        $data = $this->users->profile($user);

        return view('admin.users.show', $data);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'referral_code' => $data['referral_code'] ?: $user->referral_code,
        ]);

        if ($request->boolean('email_verified') && ! $user->email_verified_at) {
            $user->email_verified_at = now();
        } elseif (! $request->boolean('email_verified')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($data['status'] === 'blocked' && ! $user->isBlocked()) {
            $this->security->blockUser($user, 'Blocked by admin from user profile.');
        } elseif ($data['status'] === 'active' && $user->isBlocked()) {
            $this->security->unblockUser($user);
        }

        return back()->with('success', 'User profile updated.');
    }

    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        $this->sessions->killAllSessions($user->id);

        return back()->with('success', 'Password updated. All sessions ended — user must sign in again.');
    }

    public function grantSubscription(Request $request, User $user)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:24', 'required_without:duration_days'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:90', 'required_without:duration_months'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);

        if ($plan->isTrial() && empty($data['duration_days'])) {
            return back()->with('error', 'Trial plans need duration in days.');
        }

        $this->users->grantSubscription(
            $user,
            $plan,
            (int) ($data['duration_months'] ?? 1),
            isset($data['duration_days']) ? (int) $data['duration_days'] : null,
        );

        return back()->with('success', "Plan “{$plan->name}” activated for {$user->name}.");
    }

    public function extendSubscription(Request $request, User $user, Subscription $subscription)
    {
        abort_unless($subscription->user_id === $user->id, 404);

        $data = $request->validate([
            'extend_months' => ['nullable', 'integer', 'min:1', 'max:24'],
            'extend_days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $this->users->extendSubscription(
            $subscription,
            (int) ($data['extend_months'] ?? 0),
            (int) ($data['extend_days'] ?? 0),
        );

        return back()->with('success', 'Subscription extended.');
    }

    public function cancelSubscription(User $user, Subscription $subscription)
    {
        abort_unless($subscription->user_id === $user->id, 404);

        $this->users->cancelSubscription($subscription);

        return back()->with('success', 'Subscription cancelled.');
    }

    public function killSessions(User $user)
    {
        $count = $this->sessions->killAllSessions($user->id);

        return back()->with('success', "Ended {$count} active session(s).");
    }
}
