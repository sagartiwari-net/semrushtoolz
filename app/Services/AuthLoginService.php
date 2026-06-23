<?php

namespace App\Services;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthLoginService
{
    public function __construct(
        protected OtpService $otp,
    ) {}

    public function completeLogin(User $user, Request $request, bool $remember = false): void
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        app(SecurityMonitorService::class)->logActivity($user, $request, 'login');
    }

    public function redirectAfterLogin(User $user)
    {
        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.index'));
        }

        return redirect()->intended(route('dashboard.index'));
    }

    public function handlePostCredentialLogin(User $user, Request $request): \Illuminate\Http\RedirectResponse
    {
        if ($user->status === 'blocked') {
            Auth::logout();

            return back()->withErrors(['email' => 'Your account has been blocked. Contact support.']);
        }

        if (! $user->hasVerifiedEmail()) {
            Auth::logout();

            return redirect()->route('verification.notice')
                ->with('email', $user->email)
                ->withErrors(['email' => 'Please verify your email before signing in. Check your inbox.']);
        }

        if ($this->otp->needsPeriodicOtp($user)) {
            Auth::logout();
            $request->session()->put('login_otp_user_id', $user->id);
            $request->session()->put('login_otp_remember', $request->boolean('remember'));
            $this->otp->send($user, LoginOtp::PURPOSE_PERIODIC);

            return redirect()->route('login.otp.challenge')
                ->with('success', 'We sent a verification code to your email.');
        }

        app(SecurityMonitorService::class)->logActivity($user, $request, 'login');

        return $this->redirectAfterLogin($user);
    }

    public function pendingOtpUser(Request $request): ?User
    {
        $userId = $request->session()->get('login_otp_user_id');

        return $userId ? User::find($userId) : null;
    }

    public function clearPendingOtp(Request $request): void
    {
        $request->session()->forget(['login_otp_user_id', 'login_otp_remember']);
    }
}
