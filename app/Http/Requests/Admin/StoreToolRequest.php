<?php

namespace App\Http\Requests\Admin;

use App\Models\Tool;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreToolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('tools', 'slug')],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'thumbnail_url' => ['nullable', 'url', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'access_type' => ['required', Rule::in([Tool::ACCESS_CLOUD, Tool::ACCESS_EXTENSION, Tool::ACCESS_WHATSAPP, Tool::ACCESS_CREDENTIALS])],
            'price_inr' => ['nullable', 'integer', 'min:0'],
            'price_usd' => ['nullable', 'integer', 'min:0'],
            'prices_json' => ['nullable', 'string'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'whatsapp_message' => ['nullable', 'string', 'max:255'],
            'official_url' => ['nullable', 'url', 'max:2048'],
            'extension_download_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'show_in_shop' => ['nullable', 'boolean'],
            'category' => ['nullable', 'string', Rule::in(array_keys(Tool::categories()))],
            'shop_badge' => ['nullable', 'string', 'max:40'],
            'grants_tool_slug' => ['nullable', 'string', 'max:60'],
            'grants_tool_slugs_text' => ['nullable', 'string', 'max:2000'],
            'shop_features_text' => ['nullable', 'string'],
            'credentials' => ['nullable', 'array'],
        ];
    }
}
