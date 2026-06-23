<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends StorePlanRequest
{
    public function rules(): array
    {
        $plan = $this->route('plan');

        return array_merge(parent::rules(), [
            'slug' => ['required', 'string', 'max:60', Rule::unique('plans', 'slug')->ignore($plan)],
        ]);
    }
}
