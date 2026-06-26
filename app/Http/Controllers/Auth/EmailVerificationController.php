<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice(Request $request)
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->route('dashboard.index');
        }

        return view('auth.verify-email', [
            'email' => $request->user()?->email ?? session('email'),
        ]);
    }

    public function verify(Request $request, int $id, string $hash, OtpService $otp)
    {
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('success', 'Email already verified. You can sign in.');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));
        $otp->markOtpVerified($user);

        return redirect()->route('login')
            ->with('success', 'Email verified successfully! You can now sign in.');
    }

    public function send(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            $request->validate(['email' => ['required', 'email']]);
            $user = User::where('email', $request->email)->whereNull('email_verified_at')->first();
        }

        if (! $user || $user->hasVerifiedEmail()) {
            return back()
                ->with('email_spam_tip', true)
                ->with('success', 'If your account is pending verification, we sent a new link.');
        }

        $user->sendEmailVerificationNotification();

        return back()
            ->with('email_spam_tip', true)
            ->with('success', 'Verification link sent! Check your inbox.');
    }
}
