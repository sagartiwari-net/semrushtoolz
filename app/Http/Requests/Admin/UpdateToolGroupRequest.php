<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateToolGroupRequest extends StoreToolGroupRequest
{
    public function rules(): array
    {
        $group = $this->route('toolGroup');

        return array_merge(parent::rules(), [
            'slug' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('tool_access_groups', 'slug')->ignore($group)],
        ]);
    }
}
