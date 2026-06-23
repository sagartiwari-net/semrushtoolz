<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tool extends Model
{
    public const ACCESS_CLOUD = 'cloud';

    public const ACCESS_EXTENSION = 'extension';

    public const ACCESS_WHATSAPP = 'whatsapp';

    public const ACCESS_CREDENTIALS = 'credentials';

    public const CATEGORY_SEO = 'seo';

    public const CATEGORY_AI = 'ai';

    public const CATEGORY_WRITING = 'writing';

    public const CATEGORY_DESIGN = 'design';

    public static function categories(): array
    {
        return [
            self::CATEGORY_SEO => 'SEO',
            self::CATEGORY_AI => 'AI',
            self::CATEGORY_WRITING => 'Writing',
            self::CATEGORY_DESIGN => 'Design',
            'other' => 'Other',
        ];
    }

    public function categoryLabel(): string
    {
        return self::categories()[$this->category] ?? ucfirst($this->category ?? 'Other');
    }

    protected $fillable = [
        'slug', 'name', 'description', 'logo_url', 'thumbnail_url', 'access_type',
        'price_inr', 'price_usd', 'prices',
        'whatsapp_number', 'whatsapp_message', 'official_url', 'extension_download_url',
        'is_extension', 'sort_order', 'is_active',
        'show_in_shop', 'shop_features', 'category', 'grants_tool_slug', 'shop_badge',
        'seo_title', 'seo_description', 'seo_keywords',
    ];

    protected function casts(): array
    {
        return [
            'prices' => 'array',
            'shop_features' => 'array',
            'is_extension' => 'boolean',
            'is_active' => 'boolean',
            'show_in_shop' => 'boolean',
        ];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }

    public function accessGroup(): HasOne
    {
        return $this->hasOne(ToolAccessGroup::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(ToolCredential::class)->orderBy('sort_order');
    }

    public function activeCredentials(): HasMany
    {
        return $this->credentials()->where('is_active', true);
    }

    public function isCloud(): bool
    {
        return $this->access_type === self::ACCESS_CLOUD;
    }

    public function isExtensionAccess(): bool
    {
        return $this->access_type === self::ACCESS_EXTENSION;
    }

    public function isWhatsapp(): bool
    {
        return $this->access_type === self::ACCESS_WHATSAPP;
    }

    public function isCredentials(): bool
    {
        return $this->access_type === self::ACCESS_CREDENTIALS;
    }

    public function accessTypeLabel(): string
    {
        return match ($this->access_type) {
            self::ACCESS_EXTENSION => 'Extension',
            self::ACCESS_WHATSAPP => 'WhatsApp Activation',
            self::ACCESS_CREDENTIALS => 'Username & Password',
            default => 'Cloud (One-Click)',
        };
    }

    public function grantSlug(): string
    {
        return $this->grants_tool_slug ?: $this->slug;
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_url ?: $this->logo_url;
    }

    public function seo(): array
    {
        return [
            'title' => $this->seo_title ?? '',
            'description' => $this->seo_description ?? '',
            'keywords' => $this->seo_keywords ?? '',
        ];
    }

    public function resolvedSeo(): array
    {
        if ($this->seo_title && $this->seo_description) {
            return $this->seo();
        }

        $generated = \App\Services\ToolSeoService::suggestionsForTool($this);

        return [
            'title' => $this->seo_title ?: $generated['seo_title'],
            'description' => $this->seo_description ?: $generated['seo_description'],
            'keywords' => $this->seo_keywords ?: $generated['seo_keywords'],
        ];
    }

    public function isSellable(): bool
    {
        return $this->is_active
            && $this->show_in_shop
            && ($this->price_inr > 0 || $this->price_usd > 0);
    }

    public function toShopArray(): array
    {
        return [
            'id' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->description,
            'price_inr' => $this->price_inr,
            'price_usd' => $this->price_usd,
            'logo' => $this->thumbnailUrl(),
            'thumbnail' => $this->thumbnailUrl(),
            'logo_alt' => $this->name,
            'featured' => false,
            'badge' => $this->shop_badge,
            'shop_group' => $this->category,
            'category' => $this->category,
            'access_type' => $this->access_type,
            'features' => collect($this->shop_features ?? [])->map(fn ($f) => is_array($f) ? $f : ['text' => $f])->all(),
        ];
    }
}
