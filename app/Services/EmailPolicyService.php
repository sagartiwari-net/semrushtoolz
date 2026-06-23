<?php

namespace App\Services;

class EmailPolicyService
{
    /** @var array<int, string>|null */
    private static ?array $blockedDomains = null;

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

        if (substr_count($email, '.') > 3) {
            return $this->deny('This email address is not allowed. Too many dots in the address.');
        }

        if ($this->isDisposableDomain($domain)) {
            return $this->deny('Temporary or disposable email addresses are not allowed. Please use a real email (Gmail, Outlook, etc.).');
        }

        if ($this->matchesBlockedPattern($local, $domain)) {
            return $this->deny('Temporary or disposable email addresses are not allowed. Please use a real email.');
        }

        return ['allowed' => true, 'message' => ''];
    }

    public function isAllowed(string $email): bool
    {
        return $this->validate($email)['allowed'];
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
        if (self::$blockedDomains === null) {
            self::$blockedDomains = array_values(array_unique(array_map(
                static fn (string $domain) => strtolower(trim($domain)),
                config('email_policy.disposable_domains', []),
            )));
        }

        return self::$blockedDomains;
    }

    /** @return array{allowed: false, message: string} */
    private function deny(string $message): array
    {
        return ['allowed' => false, 'message' => $message];
    }
}
