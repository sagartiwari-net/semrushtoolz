<?php

namespace App\Services;

use App\Exceptions\MailPanelException;
use App\Exceptions\OtpDeliveryException;
use App\Models\LoginOtp;
use App\Models\User;
use App\Services\MailPanel\MailPanelService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OtpService
{
    public function __construct(
        protected MailPanelService $mailPanel,
    ) {}

    public function needsPeriodicOtp(User $user): bool
    {
        if ($user->isAdmin()) {
            return false;
        }

        if (! $user->last_login_otp_at) {
            return true;
        }

        return $user->last_login_otp_at->lt(now()->subDays(config('auth_otp.recheck_days', 15)));
    }

    public function markOtpVerified(User $user): void
    {
        $user->update(['last_login_otp_at' => now()]);
    }

    public function send(User $user, string $purpose): string
    {
        $this->invalidateActive($user, $purpose);

        $code = $this->generateCode();
        $plain = $code;

        LoginOtp::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(config('auth_otp.expires_minutes', 10)),
            'ip_address' => request()->ip(),
        ]);

        $this->deliverOtp($user, $plain, $purpose);

        $at = strpos($user->email, '@');

        return Str::mask($user->email, '*', 3, max(1, $at !== false ? $at - 3 : 1));
    }

    public function verify(User $user, string $code, string $purpose): bool
    {
        $otp = LoginOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp || ! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->update(['used_at' => now()]);
        $this->markOtpVerified($user);

        return true;
    }

    public function canResend(User $user, string $purpose): bool
    {
        $latest = LoginOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        if (! $latest) {
            return true;
        }

        return $latest->created_at->lt(now()->subSeconds(config('auth_otp.resend_cooldown_seconds', 60)));
    }

    protected function invalidateActive(User $user, string $purpose): void
    {
        LoginOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);
    }

    protected function deliverOtp(User $user, string $code, string $purpose): void
    {
        if (! $this->mailPanel->isEnabled()) {
            throw new OtpDeliveryException(
                'Email delivery is not configured yet. Please sign in with your password or contact support.',
            );
        }

        try {
            $this->mailPanel->sendLoginOtp($user, $code, $purpose);
        } catch (MailPanelException $exception) {
            Log::error('Mail Panel OTP send failed', [
                'user_id' => $user->id,
                'purpose' => $purpose,
                'status' => $exception->statusCode,
                'message' => $exception->getMessage(),
            ]);

            $message = str_contains(strtolower($exception->getMessage()), 'daily send cap')
                ? 'Daily email limit reached. Please sign in with your password or try again tomorrow.'
                : 'Could not send login code. Please try again in a moment or use password login.';

            throw new OtpDeliveryException($message);
        }
    }

    protected function generateCode(): string
    {
        $length = config('auth_otp.length', 6);

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', strtolower(trim($email)))->first();
    }
}
