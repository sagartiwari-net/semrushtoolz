@extends('layouts.public')

@php
    $seo = $homepage['seo'];
    $hero = $homepage['hero'];
    $plansSection = $homepage['plans'];
    $planNotes = $homepage['plan_notes'];
    $ahrefsSection = $homepage['ahrefs_plans'];
    $semrushBlock = $homepage['semrush_block'];
    $ahrefsBlock = $homepage['ahrefs_block'];
    $comboBlock = $homepage['combo_block'];
    $howSection = $homepage['how_it_works'];
    $featuresSection = $homepage['features'];
    $faqSection = $homepage['faq'];
    $ctaSection = $homepage['cta'];
    $stats = $homepage['stats'];
    $faqs = $faqSection['items'];
    $visibility = $homepage['section_visibility'] ?? [];
@endphp

@section('seo_meta')
    <x-seo-meta
        :title="$seo['title']"
        :description="$seo['description']"
        :keywords="$seo['keywords']"
        :ogImage="$seo['og_image'] ?: null"
    />
@endsection

@push('schema')
@php
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faqs)->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq['q'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['a'],
            ],
        ])->values()->all(),
    ];
@endphp
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'Semrushtoolz',
    'url' => url('/'),
    'description' => $seo['description'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'Semrushtoolz',
    'url' => url('/'),
    'description' => $seo['organization_description'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
    <section class="gradient-hero relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23f05a28%22 fill-opacity=%220.03%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-60"></div>

        <div class="relative mx-auto max-w-7xl px-4 pb-20 pt-16 sm:px-6 sm:pt-24 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                @if ($hero['badge'])
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-accent/20 bg-accent/5 px-4 py-1.5 text-sm font-medium text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        {{ $hero['badge'] }}
                    </div>
                @endif

                <h1 class="text-4xl font-extrabold tracking-tight text-ink sm:text-5xl lg:text-6xl">
                    @if ($hero['heading_line1'])
                        <span class="text-gradient">{{ $hero['heading_line1'] }}</span>
                    @endif
                    @if ($hero['heading_line2'])
                        &amp; {{ $hero['heading_line2'] }}
                    @endif
                </h1>

                @if ($hero['subtext_html'])
                    <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-ink-secondary">{!! $hero['subtext_html'] !!}</p>
                @endif

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    @if ($hero['cta_primary_label'])
                        <a href="{{ $hero['cta_primary_url'] ?: '#plans' }}" class="ui-btn-primary px-8 py-3 text-base">
                            {{ $hero['cta_primary_label'] }}
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    @endif
                    @if ($hero['cta_secondary_label'])
                        <a href="{{ $hero['cta_secondary_url'] ?: route('register') }}" class="ui-btn-outline px-8 py-3 text-base">{{ $hero['cta_secondary_label'] }}</a>
                    @endif
                </div>
            </div>

            @if (count($stats) > 0)
                <div class="mx-auto mt-16 grid max-w-3xl grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ($stats as $stat)
                        <div class="ui-card px-4 py-5 text-center">
                            <div class="text-2xl font-bold text-ink">{{ $stat['value'] }}</div>
                            <div class="mt-1 text-xs font-medium text-ink-muted">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="plans" class="py-20">
        <div class="plans-section">
            <div class="text-center">
                <h2 class="section-heading">{{ $plansSection['heading'] }}</h2>
                <p class="section-sub mx-auto">{{ $plansSection['subheading'] }}</p>

                @if ($plansSection['banner_image'])
                    <div class="mx-auto mt-8 max-w-5xl">
                        <img
                            src="{{ $plansSection['banner_image'] }}"
                            alt="{{ $plansSection['banner_alt'] ?: 'Plans banner' }}"
                            class="w-full rounded-2xl border border-line shadow-sm"
                            loading="lazy"
                            width="1280"
                            height="427"
                        >
                    </div>
                @endif
            </div>

            <div class="mt-10">
                <x-pricing-controls />
            </div>

            @if ($plansSection['billing_note'])
                <p class="mt-3 text-center text-xs text-ink-muted">{{ $plansSection['billing_note'] }}</p>
            @endif

            <div class="plans-grid plans-grid-semrush mt-8">
                @foreach ($semrushPlans as $index => $plan)
                    <x-plan-card :plan="$plan" :index="$index" variant="main" />
                @endforeach
            </div>

            @if (count($planNotes) > 0)
                <div class="mx-auto mt-10 max-w-3xl space-y-3 rounded-2xl border border-line bg-surface/60 p-5">
                    @foreach ($planNotes as $note)
                        <p class="text-xs leading-relaxed text-ink-secondary">
                            <sup class="font-bold text-accent">{{ $note['marker'] }}</sup>
                            {{ $note['text'] }}
                        </p>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @if ($visibility['ahrefs_plans'] ?? true)
    <section id="ahrefs-plans" class="border-t border-line bg-white py-20">
        <div class="plans-section">
            <div class="text-center">
                @if ($ahrefsSection['badge'])
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-blue/10 px-3 py-1 text-xs font-semibold text-blue">
                        <img src="https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094" alt="" class="h-4 w-4 object-contain" loading="lazy">
                        {{ $ahrefsSection['badge'] }}
                    </div>
                @endif
                <h2 class="section-heading">{{ $ahrefsSection['heading'] }}</h2>
                <p class="section-sub mx-auto">{{ $ahrefsSection['subheading'] }}</p>
            </div>

            <div class="shop-tools-grid shop-tools-grid-ahrefs mt-12">
                @foreach ($ahrefsPlans as $index => $tool)
                    <x-shop-tool-card :tool="$tool" :index="$index" />
                @endforeach
            </div>

            @if ($trialPlan)
                <x-trial-plan-section :plan="$trialPlan" />
            @endif

            @if ($ahrefsSection['footnote'])
                <p class="mx-auto mt-8 max-w-2xl text-center text-xs text-ink-muted">{{ $ahrefsSection['footnote'] }}</p>
            @endif
        </div>
    </section>
    @endif

    @if ($visibility['semrush_block'] ?? true)
    <section id="semrush-group-buy" class="border-t border-line bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    @if ($semrushBlock['logo_url'])
                        <img src="{{ $semrushBlock['logo_url'] }}" alt="{{ $semrushBlock['logo_alt'] }}" class="mb-6 h-12 w-auto" loading="lazy">
                    @endif
                    <h2 class="section-heading">{{ $semrushBlock['heading'] }}</h2>
                    @if ($semrushBlock['intro_html'])
                        <p class="section-sub mt-3">{!! $semrushBlock['intro_html'] !!}</p>
                    @endif
                    @if (! empty($semrushBlock['bullets']))
                        <ul class="mt-6 space-y-3 text-sm text-ink-secondary">
                            @foreach ($semrushBlock['bullets'] as $bullet)
                                <li class="flex gap-2"><span class="text-success">✓</span> {{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($semrushBlock['cta_label'])
                        <a href="{{ $semrushBlock['cta_url'] ?: url('/tools/semrush-group-buy') }}" class="ui-btn-primary mt-8 inline-flex">{{ $semrushBlock['cta_label'] }}</a>
                    @endif
                </div>
                <div class="ui-card p-8">
                    <h3 class="text-lg font-bold text-ink">{{ $semrushBlock['sidebar_heading'] }}</h3>
                    @if ($semrushBlock['sidebar_html'])
                        <p class="mt-3 text-sm leading-relaxed text-ink-secondary">{!! $semrushBlock['sidebar_html'] !!}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>
    @endif

    @if ($visibility['ahrefs_block'] ?? true)
    <section id="ahrefs-group-buy" class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="ui-card order-2 p-8 lg:order-1">
                    <h3 class="text-lg font-bold text-ink">{{ $ahrefsBlock['sidebar_heading'] }}</h3>
                    @if ($ahrefsBlock['sidebar_html'])
                        <p class="mt-3 text-sm leading-relaxed text-ink-secondary">{!! $ahrefsBlock['sidebar_html'] !!}</p>
                    @endif
                </div>
                <div class="order-1 lg:order-2">
                    @if ($ahrefsBlock['logo_url'])
                        <img src="{{ $ahrefsBlock['logo_url'] }}" alt="{{ $ahrefsBlock['logo_alt'] }}" class="mb-6 h-10 w-auto" loading="lazy">
                    @endif
                    <h2 class="section-heading">{{ $ahrefsBlock['heading'] }}</h2>
                    @if ($ahrefsBlock['intro_html'])
                        <p class="section-sub mt-3">{!! $ahrefsBlock['intro_html'] !!}</p>
                    @endif
                    @if (! empty($ahrefsBlock['bullets']))
                        <ul class="mt-6 space-y-3 text-sm text-ink-secondary">
                            @foreach ($ahrefsBlock['bullets'] as $bullet)
                                <li class="flex gap-2"><span class="text-success">✓</span> {{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($ahrefsBlock['cta_label'])
                        <a href="{{ $ahrefsBlock['cta_url'] ?: url('/tools/ahrefs-group-buy') }}" class="ui-btn-primary mt-8 inline-flex">{{ $ahrefsBlock['cta_label'] }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
    @endif

    @if ($visibility['combo_block'] ?? true)
    <section id="group-buy-seo-tools" class="border-y border-line bg-white py-20">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="section-heading">{{ $comboBlock['heading'] }}</h2>
            @if ($comboBlock['body_html'])
                <p class="section-sub mx-auto mt-4 leading-relaxed">{!! $comboBlock['body_html'] !!}</p>
            @endif
            @if (count($comboTags) > 0)
                <div class="mt-8 flex flex-wrap justify-center gap-2">
                    @foreach ($comboTags as $tag)
                        <span class="ui-badge bg-surface text-ink-secondary">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

    @if ($visibility['how_it_works'] ?? true)
    <section id="how-it-works" class="border-y border-line bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="section-heading">{{ $howSection['heading'] }}</h2>
                <p class="section-sub mx-auto">{{ $howSection['subheading'] }}</p>
            </div>

            <div class="mt-14 grid gap-8 md:grid-cols-3">
                @foreach ($howSection['steps'] as $item)
                    <div class="ui-card relative p-8">
                        <span class="text-5xl font-black text-accent/10">{{ $item['step'] }}</span>
                        <div class="mt-4 flex h-12 w-12 items-center justify-center rounded-xl bg-accent/10 text-accent">
                            @if ($item['icon'] === 'shopping-cart')
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                            @elseif ($item['icon'] === 'credit-card')
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                            @endif
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-ink">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink-secondary">{{ $item['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @if ($visibility['features'] ?? true)
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="ui-card overflow-hidden">
                <div class="grid lg:grid-cols-2">
                    <div class="p-8 lg:p-12">
                        <h2 class="section-heading">{{ $featuresSection['heading'] }}</h2>
                        <p class="section-sub">{{ $featuresSection['subheading'] }}</p>

                        <div class="mt-8 space-y-5">
                            @foreach ($featuresSection['items'] as $feat)
                                <div class="flex gap-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-ink">{{ $feat['title'] }}</h4>
                                        <p class="mt-0.5 text-sm text-ink-secondary">{{ $feat['desc'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center justify-center bg-gradient-to-br from-ink to-ink/90 p-8 lg:p-12">
                        <div class="w-full max-w-sm space-y-3">
                            <div class="ui-card !border-0 p-4 shadow-lg animate-fade-up" style="animation-delay: 100ms">
                                <div class="flex items-center gap-3">
                                    <img src="https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768" alt="Semrush" class="h-8 w-8 object-contain">
                                    <div class="flex-1">
                                        <div class="text-sm font-semibold">Semrush</div>
                                        <div class="text-xs text-ink-muted">Cloud Access</div>
                                    </div>
                                    <span class="ui-badge-success">Online</span>
                                </div>
                                <button type="button" class="ui-btn-primary mt-3 w-full py-2 text-xs">Access Now</button>
                            </div>
                            <div class="ui-card !border-0 p-4 shadow-lg animate-fade-up" style="animation-delay: 250ms">
                                <div class="flex items-center gap-3">
                                    <img src="https://ik.imagekit.io/webfiles/ahrefs.avif?updatedAt=1753593854334" alt="Ahrefs" class="h-8 w-8 rounded-lg object-contain">
                                    <div class="flex-1">
                                        <div class="text-sm font-semibold">Ahrefs</div>
                                        <div class="text-xs text-ink-muted">Cloud Access</div>
                                    </div>
                                    <span class="ui-badge-success">Online</span>
                                </div>
                                <button type="button" class="ui-btn-outline mt-3 w-full py-2 text-xs">Access Now</button>
                            </div>
                            <p class="text-center text-xs text-white/50">Dashboard preview — your tools, one click away</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    @foreach ($customSections as $section)
        <x-home-custom-section :section="$section" />
    @endforeach

    @if ($visibility['faq'] ?? true)
    <section id="faq" class="border-t border-line bg-white py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="section-heading">{{ $faqSection['heading'] }}</h2>
                <p class="section-sub mx-auto">{{ $faqSection['subheading'] }}</p>
            </div>

            <div class="mt-10 space-y-3">
                @foreach ($faqs as $i => $faq)
                    <div class="collapse collapse-arrow ui-card">
                        <input type="radio" name="faq-accordion" {{ $i === 0 ? 'checked' : '' }} />
                        <div class="collapse-title text-base font-semibold text-ink">{{ $faq['q'] }}</div>
                        <div class="collapse-content text-sm leading-relaxed text-ink-secondary">
                            <p>{{ $faq['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @if ($visibility['cta'] ?? true)
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl bg-ink px-8 py-14 text-center sm:px-16">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(240,90,40,0.25),transparent_60%)]"></div>
                <div class="relative">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">{{ $ctaSection['heading'] }}</h2>
                    @if ($ctaSection['subtext_html'])
                        <p class="mx-auto mt-4 max-w-lg text-white/70">{!! $ctaSection['subtext_html'] !!}</p>
                    @endif
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        @if ($ctaSection['primary_label'])
                            <a href="{{ $ctaSection['primary_url'] ?: route('register') }}" class="ui-btn-primary px-8 py-3 text-base">{{ $ctaSection['primary_label'] }}</a>
                        @endif
                        @if ($ctaSection['secondary_label'])
                            <a href="{{ $ctaSection['secondary_url'] ?: '#plans' }}" class="ui-btn border border-white/20 bg-transparent text-white hover:bg-white/10">{{ $ctaSection['secondary_label'] }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif
@endsection
