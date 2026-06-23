<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubscribeController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'plan' => ['nullable', 'string', 'exists:plans,slug', 'required_without:tool'],
            'tool' => ['nullable', 'string', 'exists:tools,slug', 'required_without:plan'],
            'duration_months' => ['nullable', 'integer', Rule::in(array_keys(config('pricing.durations')))],
            'duration_days' => ['nullable', 'integer', Rule::in(array_keys(config('pricing.trial_durations', [])))],
            'currency' => ['nullable', 'string', Rule::in(['inr', 'usd'])],
        ]);

        $params = array_filter([
            'plan' => $request->input('plan'),
            'tool' => $request->input('tool'),
            'duration_months' => $request->filled('duration_months') ? (int) $request->input('duration_months') : null,
            'duration_days' => $request->filled('duration_days') ? (int) $request->input('duration_days') : null,
            'currency' => $request->input('currency', 'inr'),
        ], fn ($value) => $value !== null && $value !== '');

        $checkoutUrl = route('dashboard.checkout', $params);

        if (! Auth::check()) {
            session()->put('url.intended', $checkoutUrl);

            return redirect()
                ->route('login')
                ->with('success', 'Sign in to continue to checkout.');
        }

        if (! Auth::user()->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice')
                ->with('email', Auth::user()->email);
        }

        return redirect($checkoutUrl);
    }
}
