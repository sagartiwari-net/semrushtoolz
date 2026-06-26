<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TurnstileService
{
    public function isEnabled(): bool
    {
        return filled(config('captcha.turnstile_site_key'))
            && filled(config('captcha.turnstile_secret_key'));
    }

    public function siteKey(): ?string
    {
        return config('captcha.turnstile_site_key') ?: null;
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if (! $token) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('captcha.turnstile_secret_key'),
                    'response' => $token,
                    'remoteip' => $ip,
                ]);

            return $response->successful() && $response->json('success') === true;
        } catch (\Throwable) {
            return false;
        }
    }
}
