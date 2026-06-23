<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateToolRequest extends StoreToolRequest
{
    public function rules(): array
    {
        $tool = $this->route('tool');

        return array_merge(parent::rules(), [
            'slug' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('tools', 'slug')->ignore($tool)],
        ]);
    }
}
