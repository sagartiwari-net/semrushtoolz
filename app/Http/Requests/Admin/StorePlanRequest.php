<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:60', Rule::unique('plans', 'slug')],
            'product_type' => ['required', 'string', 'max:60'],
            'display_group' => ['required', Rule::in(['main', 'ahrefs'])],
            'tagline' => ['nullable', 'string', 'max:200'],
            'price_inr' => ['required', 'integer', 'min:0'],
            'price_usd' => ['required', 'integer', 'min:0'],
            'badge' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'features_text' => ['nullable', 'string'],
            'tool_ids' => ['nullable', 'array'],
            'tool_ids.*' => ['exists:tools,id'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'show_on_homepage' => ['nullable', 'boolean'],
            'is_bundle' => ['nullable', 'boolean'],
            'is_trial' => ['nullable', 'boolean'],
            'amember_product_ids' => ['nullable', 'string', 'max:500'],
        ];
    }
}
