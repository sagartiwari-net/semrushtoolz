<?php

namespace App\Http\Requests;

use App\Models\Plan;
use App\Models\Tool;
use App\Services\OrderService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currency = $this->input('currency', 'inr');
        if ($this->input('payment_method') === 'paypal') {
            $currency = 'usd';
        }

        $isTrial = $this->isTrialCheckout();

        $methods = collect(app(OrderService::class)->availablePaymentMethods(
            $currency,
            $this->user(),
            $this->estimatedOrderTotal(),
            $isTrial,
        ))->pluck('id')->all();

        return [
            'plan' => ['nullable', 'string', 'exists:plans,slug', 'required_without:tool'],
            'tool' => ['nullable', 'string', 'exists:tools,slug', 'required_without:plan'],
            'duration_months' => [
                $isTrial ? 'nullable' : 'required',
                'integer',
                Rule::in(array_keys(config('pricing.durations'))),
            ],
            'duration_days' => [
                $isTrial ? 'required' : 'nullable',
                'integer',
                Rule::in(array_keys(config('pricing.trial_durations', []))),
            ],
            'currency' => ['required', 'string', Rule::in(['inr', 'usd'])],
            'payment_method' => ['required', 'string', Rule::in($methods)],
            'coupon_code' => $isTrial ? ['prohibited'] : ['nullable', 'string', 'max:40'],
            'terms' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('payment_method') === 'paypal') {
            $this->merge(['currency' => 'usd']);
        }
    }

    protected function isTrialCheckout(): bool
    {
        if (! $this->filled('plan')) {
            return false;
        }

        $plan = Plan::where('slug', $this->plan)->where('is_active', true)->first();

        return $plan?->isTrial() ?? false;
    }

    protected function estimatedOrderTotal(): ?float
    {
        try {
            $orders = app(OrderService::class);
            $user = $this->user();
            $currency = $this->input('currency', 'inr');
            $couponCode = filled($this->coupon_code) ? strtoupper(trim($this->coupon_code)) : null;

            if ($this->filled('tool')) {
                $tool = Tool::where('slug', $this->tool)->where('is_active', true)->first();
                if (! $tool) {
                    return null;
                }

                $durationMonths = (int) $this->input('duration_months', 1);
                $totals = $orders->finalizeCheckoutTotals(
                    $orders->calculateToolTotals($tool, $durationMonths, $currency),
                    $user,
                    $currency,
                    $durationMonths,
                    $couponCode,
                    toolId: $tool->id,
                );
            } elseif ($this->filled('plan')) {
                $plan = Plan::where('slug', $this->plan)->where('is_active', true)->first();
                if (! $plan) {
                    return null;
                }

                if ($plan->isTrial()) {
                    $durationDays = (int) $this->input('duration_days', 1);
                    $totals = $orders->finalizeTrialTotals(
                        $orders->calculateTrialTotals($durationDays, $currency),
                        $currency,
                    );
                } else {
                    $durationMonths = (int) $this->input('duration_months', 1);
                    $totals = $orders->finalizeCheckoutTotals(
                        $orders->calculateTotals($plan, $durationMonths, $currency),
                        $user,
                        $currency,
                        $durationMonths,
                        $couponCode,
                        planId: $plan->id,
                    );
                }
            } else {
                return null;
            }

            return (float) $totals['total'];
        } catch (ValidationException) {
            return null;
        }
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'You must accept the terms to continue.',
            'coupon_code.prohibited' => 'Coupon codes cannot be used on trial plans.',
        ];
    }
}
