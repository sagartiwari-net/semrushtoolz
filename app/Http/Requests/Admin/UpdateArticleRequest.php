<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateArticleRequest extends StoreArticleRequest
{
    public function rules(): array
    {
        $article = $this->route('article');

        return array_merge(parent::rules(), [
            'url_path' => ['required', 'string', 'max:150', 'regex:/^[a-z0-9\-\/]+$/', Rule::unique('articles', 'url_path')->ignore($article)],
        ]);
    }
}
