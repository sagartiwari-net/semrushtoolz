<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $fillable = [
        'title', 'url_path', 'breadcrumb_label',
        'seo_title', 'seo_description', 'seo_keywords',
        'hero_heading', 'hero_subtext', 'hero_image',
        'hero_cta_label', 'hero_cta_url',
        'hero_secondary_label', 'hero_secondary_url',
        'tool_id', 'plan_slugs', 'pricing_heading', 'pricing_subtext', 'show_pricing',
        'content_blocks', 'faqs', 'features', 'steps', 'highlights',
        'footer_cta_heading', 'footer_cta_subtext',
        'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'plan_slugs' => 'array',
            'content_blocks' => 'array',
            'faqs' => 'array',
            'features' => 'array',
            'steps' => 'array',
            'highlights' => 'array',
            'show_pricing' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function seo(): array
    {
        return [
            'title' => $this->seo_title,
            'description' => $this->seo_description,
            'keywords' => $this->seo_keywords ?? '',
        ];
    }

    public function resolvedSeo(): array
    {
        if ($this->seo_title && $this->seo_description) {
            return $this->seo();
        }

        if ($this->tool) {
            $generated = \App\Services\ToolSeoService::suggestionsForToolPage($this->tool, $this->title);

            return [
                'title' => $this->seo_title ?: $generated['seo_title'],
                'description' => $this->seo_description ?: $generated['seo_description'],
                'keywords' => $this->seo_keywords ?: $generated['seo_keywords'],
            ];
        }

        return $this->seo();
    }

    public function resolvedHeroImage(): ?string
    {
        if ($this->hero_image) {
            return $this->hero_image;
        }

        return $this->tool?->thumbnailUrl();
    }

    public function publicUrl(): string
    {
        return url('/'.trim($this->url_path, '/'));
    }

    public static function publishedForTool(string $toolSlug): ?self
    {
        return static::query()
            ->with('tool')
            ->where('is_published', true)
            ->whereHas('tool', fn ($q) => $q->where('slug', $toolSlug))
            ->first();
    }

    public function isToolProductPage(): bool
    {
        return str_starts_with($this->url_path, 'tools/') || $this->tool_id !== null;
    }

    public static function booted(): void
    {
        static::saving(function (Article $article) {
            if (empty($article->url_path) && $article->title) {
                $article->url_path = Str::slug($article->title);
            }
            $article->url_path = trim($article->url_path, '/');

            if ($article->tool_id && ! str_starts_with($article->url_path, 'tools/')) {
                $article->url_path = 'tools/'.$article->url_path;
            }
        });
    }
}
