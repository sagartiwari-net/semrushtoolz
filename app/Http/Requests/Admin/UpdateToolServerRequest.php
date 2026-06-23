<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateToolServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $server = $this->route('tool_server');

        return [
            'tool_access_group_id' => ['required', 'exists:tool_access_groups,id'],
            'slug' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('tool_access_servers', 'slug')->ignore($server)],
            'label' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['proxy', 'direct'])],
            'domain' => ['nullable', 'required_if:type,proxy', 'string', 'max:200'],
            'website_id' => ['nullable', 'integer', 'min:1'],
            'secret_key' => ['nullable', 'string', 'max:200'],
            'direct_url' => ['nullable', 'required_if:type,direct', 'url', 'max:2048'],
            'section_title' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
