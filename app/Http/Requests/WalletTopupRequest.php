<?php

namespace App\Http\Requests;

use App\Models\SiteSetting;
use App\Services\OrderService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletTopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return SiteSetting::walletConfig()['enabled'];
    }

    public function rules(): array
    {
        $methods = collect(app(OrderService::class)->availablePaymentMethods('inr'))
            ->pluck('id')
            ->all();

        return [
            'amount' => ['required', 'integer', Rule::in(SiteSetting::walletConfig()['topup_amounts'])],
            'payment_method' => ['required', 'string', Rule::in($methods)],
        ];
    }
}
