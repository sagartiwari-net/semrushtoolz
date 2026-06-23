<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'url_path' => ['required', 'string', 'max:150', 'regex:/^[a-z0-9\-\/]+$/', Rule::unique('articles', 'url_path')],
            'breadcrumb_label' => ['nullable', 'string', 'max:120'],
            'seo_title' => ['required', 'string', 'max:200'],
            'seo_description' => ['required', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'hero_heading' => ['required', 'string', 'max:255'],
            'hero_subtext' => ['nullable', 'string'],
            'hero_image' => ['nullable', 'url', 'max:500'],
            'hero_cta_label' => ['nullable', 'string', 'max:120'],
            'hero_cta_url' => ['nullable', 'string', 'max:500'],
            'hero_secondary_label' => ['nullable', 'string', 'max:120'],
            'hero_secondary_url' => ['nullable', 'string', 'max:500'],
            'tool_id' => ['nullable', 'exists:tools,id'],
            'plan_slugs' => ['nullable', 'array'],
            'pricing_heading' => ['nullable', 'string', 'max:200'],
            'pricing_subtext' => ['nullable', 'string'],
            'show_pricing' => ['nullable', 'boolean'],
            'content_blocks' => ['nullable', 'array'],
            'footer_cta_heading' => ['nullable', 'string', 'max:255'],
            'footer_cta_subtext' => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }
}
