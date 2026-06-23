<?php

namespace App\Rules;

use App\Services\EmailPolicyService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AllowedEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $result = app(EmailPolicyService::class)->validate($value);

        if (! $result['allowed']) {
            $fail($result['message']);
        }
    }
}
