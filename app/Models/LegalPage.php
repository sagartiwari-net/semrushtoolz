<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LegalPage extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'html_body',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function publicPath(): string
    {
        return in_array($this->slug, ['terms', 'privacy', 'refund'], true)
            ? '/'.$this->slug
            : '/legal/'.$this->slug;
    }

    public function publicUrl(): string
    {
        return url($this->publicPath());
    }

    public function seo(): array
    {
        return [
            'title' => $this->seo_title,
            'description' => $this->seo_description,
            'keywords' => $this->seo_keywords ?? '',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (LegalPage $page) {
            if (empty($page->slug) && $page->title) {
                $page->slug = Str::slug($page->title);
            }
        });
    }
}
