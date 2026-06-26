<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\AuthLoginService;
use App\Services\ReferralService;
use App\Services\ReferralSignupBonusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        protected AuthLoginService $authLogin,
        protected ReferralService $referrals,
        protected ReferralSignupBonusService $referralBonus,
    ) {}

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Invalid email or password.',
            ])->onlyInput('email');
        }

        $user = Auth::user();

        $result = $this->authLogin->handlePostCredentialLogin($user, $request);

        return $result;
    }

    public function register(RegisterRequest $request)
    {
        $referralCode = $this->referrals->codeFromRequest($request);
        $referrer = $referralCode ? $this->referrals->resolveReferrer($referralCode) : null;

        if ($referralCode && ! $referrer) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['referral' => 'Invalid referral link. Please use a valid referral URL.']);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'user',
            'status' => 'active',
            'referral_code' => $this->generateReferralCode($request->name),
            'referred_by_user_id' => $referrer?->id,
            'referral_bonus_expires_at' => $referrer ? $this->referralBonus->expiresAtForNewReferral() : null,
        ]);

        if ($referrer) {
            $this->referrals->clearCookie();
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            Log::error('Register verification email failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'message' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('verification.notice')
            ->with('email', $user->email)
            ->with('email_spam_tip', true)
            ->with('success', 'Account created! Check your email to verify your account before signing in.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function adminLogin(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        if (! Auth::user()->isAdmin()) {
            Auth::logout();

            return back()->withErrors(['email' => 'You do not have admin access.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.index'));
    }

    protected function generateReferralCode(string $name): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
        $base = substr($base, 0, 6) ?: 'USER';

        do {
            $code = $base.random_int(100, 999);
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
