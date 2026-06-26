<?php

namespace App\Http\Requests;

use App\Rules\AllowedEmail;
use App\Services\TurnstileService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', new AllowedEmail],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $turnstile = app(TurnstileService::class);

            if ($turnstile->isEnabled() && ! $turnstile->verify($this->input('cf-turnstile-response'), $this->ip())) {
                $validator->errors()->add('captcha', 'Please complete the security check.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'You must agree to the Terms of Service and Privacy Policy.',
            'website.prohibited' => 'Registration failed.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->email))]);
        }
    }
}
