<?php

namespace App\Http\Requests;

use App\Models\SiteSetting;
use Illuminate\Foundation\Http\FormRequest;

class AffiliateWalletTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return SiteSetting::walletConfig()['enabled'];
    }

    public function rules(): array
    {
        $config = SiteSetting::walletConfig();

        return [
            'amount' => ['required', 'numeric', 'min:'.$config['min_affiliate_transfer']],
        ];
    }
}
