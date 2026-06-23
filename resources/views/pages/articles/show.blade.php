@extends('layouts.article')

@php
    $faqs = $article->faqs ?? [];
    $features = $article->features ?? [];
    $steps = $article->steps ?? [];
    $highlights = $article->highlights ?? [];
    $blocks = $article->content_blocks ?? [];
    $pricingVariant = ($article->tool?->slug === 'ahrefs') ? 'ahrefs' : 'main';
    $articleUrl = $article->publicUrl();
    $heroImage = $article->resolvedHeroImage();
    $lowestInr = collect($plans)->min(fn ($p) => $p['price_inr'] ?? $p['monthly_inr'] ?? null);
@endphp

@push('schema')
@php
    $faqSchema = count($faqs) ? [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faqs)->map(fn ($f) => [
            '@type' => 'Question',
            'name' => $f['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
        ])->values()->all(),
    ] : null;

    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tools', 'item' => url('/#plans')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $article->breadcrumb_label ?? $article->title, 'item' => $articleUrl],
        ],
    ];

    $productSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $article->title,
        'description' => $seo['description'],
        'image' => $heroImage,
        'brand' => ['@type' => 'Brand', 'name' => 'Semrushtoolz'],
        'sku' => $article->url_path,
        'url' => $articleUrl,
    ];

    if ($lowestInr) {
        $productSchema['offers'] = [
            '@type' => 'AggregateOffer',
            'priceCurrency' => 'INR',
            'lowPrice' => (string) $lowestInr,
            'highPrice' => (string) collect($plans)->max(fn ($p) => $p['price_inr'] ?? $p['monthly_inr'] ?? $lowestInr),
            'offerCount' => (string) count($plans),
            'availability' => 'https://schema.org/InStock',
            'url' => route('register'),
        ];
    }

    $webPageSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $article->hero_heading,
        'description' => $seo['description'],
        'url' => $articleUrl,
        'inLanguage' => 'en-IN',
        'isPartOf' => ['@type' => 'WebSite', 'name' => 'Semrushtoolz', 'url' => url('/')],
    ];

    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article->hero_heading,
        'description' => $seo['description'],
        'author' => ['@type' => 'Organization', 'name' => 'Semrushtoolz'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Semrushtoolz', 'url' => url('/')],
        'datePublished' => $article->published_at?->toDateString() ?? now()->toDateString(),
        'dateModified' => $article->updated_at->toDateString(),
        'mainEntityOfPage' => $articleUrl,
        'image' => $heroImage,
    ];

    $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
@endphp
<script type="application/ld+json">{!! json_encode($webPageSchema, $jsonFlags) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, $jsonFlags) !!}</script>
<script type="application/ld+json">{!! json_encode($productSchema, $jsonFlags) !!}</script>
<script type="application/ld+json">{!! json_encode($articleSchema, $jsonFlags) !!}</script>
@if ($faqSchema)
<script type="application/ld+json">{!! json_encode($faqSchema, $jsonFlags) !!}</script>
@endif
@endpush

@section('content')
    <x-preview-banner :is-preview="!empty($isPreview)" :back-url="$backUrl ?? null" />

    <section class="gradient-hero border-b border-line py-14">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <nav class="mb-6 text-sm text-ink-muted">
                <a href="{{ url('/') }}" class="hover:text-accent">Home</a>
                <span class="mx-2">/</span>
                <a href="{{ url('/#plans') }}" class="hover:text-accent">Tools</a>
                <span class="mx-2">/</span>
                <span class="text-ink-secondary">{{ $article->breadcrumb_label ?? $article->title }}</span>
            </nav>
            @if ($heroImage)
                <img src="{{ $heroImage }}" alt="{{ $article->title }}" class="mb-6 h-10 w-auto" loading="lazy">
            @endif
            <h1 class="text-3xl font-extrabold tracking-tight text-ink sm:text-4xl lg:text-5xl">{{ $article->hero_heading }}</h1>
            @if ($article->hero_subtext)
                <p class="mt-4 text-lg leading-relaxed text-ink-secondary">{!! $article->hero_subtext !!}</p>
            @endif
            <div class="mt-8 flex flex-wrap gap-3">
                @if ($article->hero_cta_label)
                    <a href="{{ $article->tool ? \App\Support\CheckoutLink::forTool($article->tool->slug) : ($article->hero_cta_url ?: route('subscribe')) }}" class="ui-btn-primary px-6 py-3">{{ $article->hero_cta_label }}</a>
                @endif
                @if ($article->hero_secondary_label)
                    <a href="{{ $article->hero_secondary_url ?: url('/#plans') }}" class="ui-btn-outline px-6 py-3">{{ $article->hero_secondary_label }}</a>
                @endif
            </div>
        </div>
    </section>

    <article class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
        @if (count($blocks) || ($article->show_pricing && count($plans)) || count($features) || count($steps) || count($faqs))
            <nav class="ui-card mb-12 p-6">
                <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-ink-muted">Table of Contents</h2>
                <ol class="space-y-2 text-sm text-ink-secondary">
                    @foreach ($blocks as $i => $block)
                        <li><a href="#{{ $block['id'] ?? 'section-'.$i }}" class="hover:text-accent">{{ $i + 1 }}. {{ $block['heading'] }}</a></li>
                    @endforeach
                    @if ($article->show_pricing && count($plans))
                        <li><a href="#plans-pricing" class="hover:text-accent">{{ count($blocks) + 1 }}. {{ $article->pricing_heading ?? 'Plans & Pricing' }}</a></li>
                    @endif
                    @if (count($features))
                        <li><a href="#features" class="hover:text-accent">Features</a></li>
                    @endif
                    @if (count($steps))
                        <li><a href="#how-to-use" class="hover:text-accent">How to Use</a></li>
                    @endif
                    @if (count($highlights))
                        <li><a href="#why-us" class="hover:text-accent">Why Semrushtoolz</a></li>
                    @endif
                    @if (count($faqs))
                        <li><a href="#faq" class="hover:text-accent">FAQ</a></li>
                    @endif
                </ol>
            </nav>
        @endif

        @foreach ($blocks as $i => $block)
            <section id="{{ $block['id'] ?? 'section-'.$i }}" class="mb-14">
                <h2 class="section-heading">{{ $block['heading'] }}</h2>
                <div class="prose-section mt-4 space-y-4 text-sm leading-relaxed text-ink-secondary">{!! $block['html'] ?? '' !!}</div>
            </section>
        @endforeach

        @if ($article->show_pricing && count($plans))
            <section id="plans-pricing" class="relative left-1/2 right-1/2 -mx-[50vw] mb-14 w-screen bg-canvas/40 py-10">
                <div class="plans-section">
                    <h2 class="section-heading">{{ $article->pricing_heading ?? 'Plans & Pricing' }}</h2>
                    @if ($article->pricing_subtext)
                        <p class="section-sub mt-2">{!! $article->pricing_subtext !!}</p>
                    @endif
                    <div class="mt-8"><x-pricing-controls /></div>
                    <div @class(['mt-8', 'plans-grid-3' => count($plans) <= 3, 'plans-grid-4' => count($plans) === 4])>
                        @foreach ($plans as $index => $plan)
                            <x-plan-card :plan="$plan" :index="$index" :variant="$pricingVariant" />
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if (count($features))
            <section id="features" class="mb-14">
                <h2 class="section-heading">Features</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($features as $feat)
                        <div class="ui-card p-5">
                            <h3 class="font-semibold text-ink">{{ $feat[0] ?? $feat['title'] ?? '' }}</h3>
                            <p class="mt-1 text-sm text-ink-secondary">{{ $feat[1] ?? $feat['desc'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (count($steps))
            <section id="how-to-use" class="mb-14">
                <h2 class="section-heading">How to Get Started</h2>
                <div class="mt-6 space-y-4">
                    @foreach ($steps as $step)
                        <div class="flex gap-4 rounded-xl border border-line bg-white p-5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent/10 text-sm font-bold text-accent">{{ $step[0] ?? '' }}</span>
                            <div>
                                <h3 class="font-semibold text-ink">{{ $step[1] ?? '' }}</h3>
                                <p class="mt-1 text-sm text-ink-secondary">{{ $step[2] ?? '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (count($highlights))
            <section id="why-us" class="mb-14">
                <h2 class="section-heading">Why Choose Semrushtoolz?</h2>
                <ul class="mt-6 space-y-3">
                    @foreach ($highlights as $item)
                        <li class="flex items-center gap-2 text-sm text-ink-secondary">
                            <svg class="shrink-0 text-success" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (count($faqs))
            <section id="faq" class="mb-14">
                <h2 class="section-heading">FAQ</h2>
                <div class="mt-8 space-y-3">
                    @foreach ($faqs as $i => $faq)
                        <div class="collapse collapse-arrow ui-card">
                            <input type="radio" name="article-faq" {{ $i === 0 ? 'checked' : '' }} />
                            <div class="collapse-title text-sm font-semibold text-ink">{{ $faq['q'] }}</div>
                            <div class="collapse-content text-sm leading-relaxed text-ink-secondary"><p>{{ $faq['a'] }}</p></div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if (isset($relatedToolPages) && $relatedToolPages->isNotEmpty())
            <section class="mb-14 rounded-2xl border border-line bg-canvas p-6">
                <h2 class="text-lg font-semibold text-ink">Other SEO Tools</h2>
                <ul class="mt-4 flex flex-wrap gap-3">
                    @foreach ($relatedToolPages as $related)
                        <li>
                            <a href="{{ $related->publicUrl() }}" class="ui-btn-ghost text-sm">{{ $related->breadcrumb_label ?? $related->title }}</a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($article->footer_cta_heading)
            <section class="rounded-2xl bg-ink px-8 py-12 text-center">
                <h2 class="text-2xl font-bold text-white">{{ $article->footer_cta_heading }}</h2>
                @if ($article->footer_cta_subtext)
                    <p class="mx-auto mt-3 max-w-lg text-sm text-white/70">{{ $article->footer_cta_subtext }}</p>
                @endif
                <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ $article->tool ? \App\Support\CheckoutLink::forTool($article->tool->slug) : route('subscribe') }}" class="ui-btn-primary px-8 py-3">Get Started</a>
                    <a href="{{ route('login') }}" class="ui-btn border border-white/20 bg-transparent text-white hover:bg-white/10">Login</a>
                </div>
            </section>
        @endif
    </article>
@endsection
