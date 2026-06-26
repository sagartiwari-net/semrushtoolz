<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\OtpDeliveryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\OtpSendRequest;
use App\Models\LoginOtp;
use App\Models\User;
use App\Rules\AllowedEmail;
use App\Services\AuthLoginService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OtpAuthController extends Controller
{
    public function __construct(
        protected OtpService $otp,
        protected AuthLoginService $authLogin,
    ) {}

    /** After password login — 15-day security OTP */
    public function challengeForm(Request $request)
    {
        $user = $this->authLogin->pendingOtpUser($request);

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please sign in again.']);
        }

        return view('auth.login-otp-challenge', [
            'email' => $user->email,
            'purpose' => LoginOtp::PURPOSE_PERIODIC,
        ]);
    }

    public function verifyChallenge(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:'.config('auth_otp.length', 6)],
        ]);

        $user = $this->authLogin->pendingOtpUser($request);

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please sign in again.']);
        }

        if (! $this->otp->verify($user, $request->code, LoginOtp::PURPOSE_PERIODIC)) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }

        $remember = $request->session()->pull('login_otp_remember', false);
        $this->authLogin->clearPendingOtp($request);

        if ($error = $this->authLogin->completeLogin($user, $request, $remember)) {
            return $error;
        }

        return $this->authLogin->redirectAfterLogin($user)
            ->with('success', 'Verified successfully. Welcome back!');
    }

    public function resendChallenge(Request $request)
    {
        $user = $this->authLogin->pendingOtpUser($request);

        if (! $user) {
            return back()->withErrors(['code' => 'Session expired. Please sign in again.']);
        }

        if (! $this->otp->canResend($user, LoginOtp::PURPOSE_PERIODIC)) {
            return back()->withErrors(['code' => 'Please wait a minute before requesting a new code.']);
        }

        try {
            $this->otp->send($user, LoginOtp::PURPOSE_PERIODIC);
        } catch (OtpDeliveryException $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()
            ->with('email_spam_tip', true)
            ->with('success', 'A new code has been sent to your email.');
    }

    /** Login with email + OTP only */
    public function sendLoginOtp(OtpSendRequest $request)
    {
        $email = strtolower(trim($request->email));
        $user = $this->otp->findUserByEmail($email);

        if (! $user) {
            return redirect()->route('login')
                ->withInput(['email' => $email])
                ->with('login_mode', 'otp')
                ->withErrors([
                    'email' => 'No account found with this email. Please create an account first.',
                ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('login')
                ->withInput(['email' => $email])
                ->with('login_mode', 'otp')
                ->withErrors([
                    'email' => 'Your email is not verified yet. Please check your inbox or sign up again.',
                ]);
        }

        if ($user->status === 'blocked') {
            return redirect()->route('login')
                ->withInput(['email' => $email])
                ->with('login_mode', 'otp')
                ->withErrors(['email' => 'Your account has been blocked. Contact support.']);
        }

        if (! $this->otp->canResend($user, LoginOtp::PURPOSE_LOGIN)) {
            return redirect()->route('login')
                ->with('login_mode', 'otp-verify')
                ->with('otp_login_email', $user->email)
                ->withErrors(['email' => 'Please wait a minute before requesting a new code.']);
        }

        try {
            $this->otp->send($user, LoginOtp::PURPOSE_LOGIN);
        } catch (OtpDeliveryException $exception) {
            return redirect()->route('login')
                ->withInput(['email' => $email])
                ->with('login_mode', 'otp')
                ->withErrors(['email' => $exception->getMessage()]);
        }

        return redirect()->route('login')
            ->with('login_mode', 'otp-verify')
            ->with('otp_login_email', $user->email)
            ->with('email_spam_tip', true)
            ->with('success', 'Login code sent! Enter the 6-digit code below.');
    }

    public function loginForm(Request $request)
    {
        return redirect()->route('login')
            ->with('login_mode', session('otp_login_email') ? 'otp-verify' : 'otp')
            ->with('otp_login_email', session('otp_login_email'));
    }

    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', new AllowedEmail],
            'code' => ['required', 'string', 'size:'.config('auth_otp.length', 6)],
        ]);

        $user = $this->otp->findUserByEmail($request->email);

        if (! $user || ! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }

        if ($user->status === 'blocked') {
            throw ValidationException::withMessages(['email' => 'Your account has been blocked.']);
        }

        if (! $this->otp->verify($user, $request->code, LoginOtp::PURPOSE_LOGIN)) {
            return redirect()->route('login')
                ->with('login_mode', 'otp-verify')
                ->with('otp_login_email', $request->email)
                ->withErrors(['code' => 'Invalid or expired code. Please try again.']);
        }

        if ($error = $this->authLogin->completeLogin($user, $request, $request->boolean('remember'))) {
            $request->session()->forget(['otp_login_email', 'login_mode']);

            return $error;
        }

        $request->session()->forget(['otp_login_email', 'login_mode']);

        return $this->authLogin->redirectAfterLogin($user)
            ->with('success', 'Signed in with email code.');
    }

    public function resendLoginOtp(OtpSendRequest $request)
    {
        $email = strtolower(trim($request->email));
        $user = $this->otp->findUserByEmail($email);

        if (! $user || ! $user->hasVerifiedEmail()) {
            return redirect()->route('login')
                ->with('login_mode', 'otp')
                ->withErrors(['email' => 'No verified account found. Please sign up first.']);
        }

        if (! $this->otp->canResend($user, LoginOtp::PURPOSE_LOGIN)) {
            return redirect()->route('login')
                ->with('login_mode', 'otp-verify')
                ->with('otp_login_email', $user->email)
                ->withErrors(['email' => 'Please wait a minute before requesting a new code.']);
        }

        try {
            $this->otp->send($user, LoginOtp::PURPOSE_LOGIN);
        } catch (OtpDeliveryException $exception) {
            return redirect()->route('login')
                ->with('login_mode', 'otp-verify')
                ->with('otp_login_email', $user->email)
                ->withErrors(['email' => $exception->getMessage()]);
        }

        return redirect()->route('login')
            ->with('login_mode', 'otp-verify')
            ->with('otp_login_email', $user->email)
            ->with('email_spam_tip', true)
            ->with('success', 'A new login code has been sent.');
    }
}
