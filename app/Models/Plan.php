<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'slug', 'product_type', 'display_group', 'name', 'tagline',
        'price_inr', 'price_usd', 'features', 'badge',
        'is_featured', 'is_active', 'show_on_homepage', 'is_bundle', 'sort_order', 'amember_product_ids',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'amember_product_ids' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'show_on_homepage' => 'boolean',
            'is_bundle' => 'boolean',
        ];
    }

    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(Tool::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function hasTool(string $slug): bool
    {
        if ($this->relationLoaded('tools')) {
            return $this->tools->contains('slug', $slug);
        }

        return $this->tools()->where('slug', $slug)->exists();
    }

    public function grantsSemrush(): bool
    {
        return $this->hasTool('semrush');
    }

    public function grantsAhrefs(): bool
    {
        return $this->hasTool('ahrefs');
    }

    public function grantsAhrefsBar(): bool
    {
        return $this->hasTool('ahrefs_bar');
    }

    public function toPricingArray(): array
    {
        $logos = match ($this->slug) {
            'combo' => [
                'logos' => [
                    ['src' => 'https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768', 'alt' => 'Semrush'],
                    ['src' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094', 'alt' => 'Ahrefs'],
                ],
            ],
            default => [],
        };

        $logo = match (true) {
            str_starts_with($this->product_type, 'ahrefs') => [
                'logo' => 'https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094',
                'logo_alt' => 'Ahrefs',
            ],
            in_array($this->product_type, ['semrush', 'semrush_site_audit', 'combo']) => [
                'logo' => 'https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768',
                'logo_alt' => 'Semrush',
            ],
            default => [],
        };

        return array_merge([
            'id' => $this->slug,
            'name' => $this->name,
            'tagline' => $this->tagline,
            'price_inr' => $this->price_inr,
            'price_usd' => $this->price_usd,
            'featured' => $this->is_featured,
            'badge' => $this->badge,
            'features' => collect($this->features ?? [])->map(fn ($f) => is_array($f) ? $f : ['text' => $f])->all(),
        ], $logos, $logo);
    }
}
