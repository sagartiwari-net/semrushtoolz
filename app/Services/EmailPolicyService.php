<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class EmailPolicyService
{
    public function validate(string $email): array
    {
        $email = strtolower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->deny('Please enter a valid email address.');
        }

        $parts = explode('@', $email, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return $this->deny('Please enter a valid email address.');
        }

        [$local, $domain] = $parts;

        if (str_contains($local, '+')) {
            return $this->deny('Email aliases with + are not allowed. Use your primary email address.');
        }

        if (strlen($local) < 2) {
            return $this->deny('Please use a valid personal email address.');
        }

        if (substr_count($email, '.') > 4) {
            return $this->deny('This email address is not allowed.');
        }

        if ($this->isBlockedTld($domain)) {
            return $this->deny('Temporary or disposable email addresses are not allowed. Please use Gmail, Outlook, Yahoo, or your work email.');
        }

        if ($this->isDisposableDomain($domain)) {
            return $this->deny('Temporary or disposable email addresses are not allowed. Please use a real email (Gmail, Outlook, etc.).');
        }

        if ($this->matchesBlockedPattern($local, $domain)) {
            return $this->deny('Temporary or disposable email addresses are not allowed. Please use a real email.');
        }

        if ($this->looksLikeDisposableDomain($domain)) {
            return $this->deny('Temporary or disposable email addresses are not allowed. Please use a real email.');
        }

        return ['allowed' => true, 'message' => ''];
    }

    public function isAllowed(string $email): bool
    {
        return $this->validate($email)['allowed'];
    }

    public function refreshBlocklistCache(): void
    {
        Cache::forget('email_policy.disposable_domains');
    }

    private function isDisposableDomain(string $domain): bool
    {
        $domain = strtolower($domain);

        if (in_array($domain, $this->blockedDomains(), true)) {
            return true;
        }

        foreach ($this->blockedDomains() as $blocked) {
            if (str_ends_with($domain, '.'.$blocked)) {
                return true;
            }
        }

        return false;
    }

    private function isBlockedTld(string $domain): bool
    {
        $tld = strtolower((string) strrchr($domain, '.'));

        return in_array($tld, config('email_policy.blocked_tlds', []), true);
    }

    private function looksLikeDisposableDomain(string $domain): bool
    {
        foreach (config('email_policy.disposable_keywords', []) as $keyword) {
            if (str_contains($domain, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function matchesBlockedPattern(string $local, string $domain): bool
    {
        $haystack = $local.'@'.$domain;

        foreach (config('email_policy.blocked_patterns', []) as $pattern) {
            if (preg_match($pattern, $haystack)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    private function blockedDomains(): array
    {
        return Cache::remember('email_policy.disposable_domains', now()->addDay(), function () {
            $domains = config('email_policy.disposable_domains', []);

            $path = 'blocklists/disposable_domains.txt';

            if (Storage::disk('local')->exists($path)) {
                $lines = preg_split('/\R+/', Storage::disk('local')->get($path) ?: '') ?: [];

                foreach ($lines as $line) {
                    $domain = strtolower(trim($line));

                    if ($domain !== '' && ! str_starts_with($domain, '#')) {
                        $domains[] = $domain;
                    }
                }
            }

            return array_values(array_unique(array_map(
                static fn (string $domain) => strtolower(trim($domain)),
                $domains,
            )));
        });
    }

    /** @return array{allowed: false, message: string} */
    private function deny(string $message): array
    {
        return ['allowed' => false, 'message' => $message];
    }
}
