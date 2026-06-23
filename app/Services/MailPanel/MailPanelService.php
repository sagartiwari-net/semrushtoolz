<?php

namespace App\Services\MailPanel;

use App\Exceptions\MailPanelException;
use App\Models\EmailPreset;
use App\Models\LoginOtp;
use App\Models\User;
use App\Support\MailPanelSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class MailPanelService
{
    public function __construct(
        private readonly MailPanelClient $client,
    ) {}

    public function isEnabled(): bool
    {
        return MailPanelSettings::isConfigured();
    }

    public function sendByPreset(string $presetKey, string $to, array $data = [], ?string $subjectOverride = null): array
    {
        $this->ensureConfigured();

        $preset = EmailPreset::findByKey($presetKey);

        if (! $preset) {
            throw new MailPanelException("Email preset \"{$presetKey}\" is not available.");
        }

        return $this->sendWithFallback(
            to: $to,
            template: $preset->slug,
            data: $data,
            subject: $subjectOverride ?? $preset->subject,
            fallbackTemplate: 'otp',
        );
    }

    public function sendLoginOtp(User $user, string $code, string $purpose): array
    {
        $this->ensureConfigured();

        $minutes = (int) config('auth_otp.expires_minutes', 10);
        $recheckDays = (int) config('auth_otp.recheck_days', 15);

        [$presetKey, $intro] = match ($purpose) {
            LoginOtp::PURPOSE_PERIODIC => [
                EmailPreset::KEY_PERIODIC_OTP,
                'For your security, please verify your login. It has been more than '.$recheckDays.' days since your last verification.',
            ],
            default => [
                EmailPreset::KEY_LOGIN_OTP,
                'Use this one-time code to sign in to your Semrushtoolz account.',
            ],
        };

        return $this->sendByPreset($presetKey, $user->email, [
            'name' => $user->name,
            'otp' => $code,
            'minutes' => $minutes,
            'intro' => $intro,
        ]);
    }

    public function sendEmailVerification(User $user): array
    {
        return $this->sendByPreset(EmailPreset::KEY_VERIFY_EMAIL, $user->email, [
            'name' => $user->name,
            'verification_url' => $this->verificationUrl($user),
        ]);
    }

    public function sendPasswordReset(User $user, string $token): array
    {
        $expireMinutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return $this->sendByPreset(EmailPreset::KEY_FORGOT_PASSWORD, $user->getEmailForPasswordReset(), [
            'name' => $user->name ?: 'there',
            'reset_url' => url(route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], false)),
            'expire_minutes' => $expireMinutes,
        ]);
    }

    private function sendWithFallback(
        string $to,
        string $template,
        array $data,
        ?string $subject,
        ?string $fallbackTemplate,
    ): array {
        try {
            return $this->client->send($to, $template, $data, $subject);
        } catch (MailPanelException $exception) {
            if (
                $fallbackTemplate
                && $template !== $fallbackTemplate
                && str_contains(strtolower($exception->getMessage()), 'not found')
            ) {
                return $this->client->send($to, $fallbackTemplate, $data, $subject);
            }

            throw $exception;
        }
    }

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );
    }

    private function ensureConfigured(): void
    {
        if (! $this->isEnabled()) {
            throw new MailPanelException('Mail Panel is not configured. Open Admin → Email Setup.');
        }
    }
}
