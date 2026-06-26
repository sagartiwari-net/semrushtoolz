<?php

namespace App\Services;

use App\Exceptions\OtpDeliveryException;
use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthLoginService
{
    public function __construct(
        protected OtpService $otp,
        protected SecurityMonitorService $security,
        protected UserSessionService $sessions,
    ) {}

    public function completeLogin(User $user, Request $request, bool $remember = false): ?\Illuminate\Http\RedirectResponse
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        return $this->finalizeAuthenticatedSession($user, $request);
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

            try {
                $this->otp->send($user, LoginOtp::PURPOSE_PERIODIC);
            } catch (OtpDeliveryException $exception) {
                $this->clearPendingOtp($request);

                return back()->withErrors(['email' => $exception->getMessage()]);
            }

            return redirect()->route('login.otp.challenge')
                ->with('email_spam_tip', true)
                ->with('success', 'We sent a verification code to your email.');
        }

        $request->session()->regenerate();

        if ($error = $this->finalizeAuthenticatedSession($user, $request)) {
            return $error;
        }

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

    protected function finalizeAuthenticatedSession(User $user, Request $request): ?\Illuminate\Http\RedirectResponse
    {
        if ($message = $this->sessions->concurrentLoginMessage($user, $request)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => $message]);
        }

        $this->sessions->purgeOtherSessions($user->id, $request->session()->getId());
        $this->security->bindDeviceToSession($request);
        $this->sessions->syncSessionMeta($request, $user);
        $this->security->logActivity($user, $request, 'login');

        return null;
    }
}
