@extends('layouts.public')

@section('seo_meta')
    <x-seo-meta
        :title="$seo['title']"
        :description="$seo['description']"
        :keywords="$seo['keywords']"
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
    'description' => 'Best Semrush group buy and Ahrefs group buy platform in India.',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
    {{-- Hero --}}
    <section class="gradient-hero relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23f05a28%22 fill-opacity=%220.03%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-60"></div>

        <div class="relative mx-auto max-w-7xl px-4 pb-20 pt-16 sm:px-6 sm:pt-24 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-accent/20 bg-accent/5 px-4 py-1.5 text-sm font-medium text-accent">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Trusted by 2,500+ SEO professionals
                </div>

                <h1 class="text-4xl font-extrabold tracking-tight text-ink sm:text-5xl lg:text-6xl">
                    <span class="text-gradient">Semrush Group Buy</span>
                    &amp; Ahrefs Group Buy
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-ink-secondary">
                    <strong class="text-ink">Semrushtoolz</strong> — India's trusted platform to
                    <strong class="text-ink">buy Semrush</strong> and
                    <strong class="text-ink">buy Ahrefs</strong> at cheap price.
                    Best <strong class="text-ink">group buy SEO tools</strong> with one-click cloud access.
                    Pay via PayPal, UPI, or offline — activate in minutes.
                </p>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="#plans" class="ui-btn-primary px-8 py-3 text-base">
                        View Plans
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                    <a href="{{ route('register') }}" class="ui-btn-outline px-8 py-3 text-base">Create Free Account</a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="mx-auto mt-16 grid max-w-3xl grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="ui-card px-4 py-5 text-center">
                        <div class="text-2xl font-bold text-ink">{{ $stat['value'] }}</div>
                        <div class="mt-1 text-xs font-medium text-ink-muted">{{ $stat['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Main Plans --}}
    <section id="plans" class="py-20">
        <div class="plans-section">
            <div class="text-center">
                <h2 class="section-heading">Semrush &amp; Ahrefs Group Buy Plans</h2>
                <p class="section-sub mx-auto">Cheap Semrush account &amp; cheap Ahrefs account — group buy Semrush and group buy Ahrefs plans for every budget.</p>

                <div class="mx-auto mt-8 max-w-5xl">
                    <img
                        src="https://ik.imagekit.io/webfiles/semrushtoolz.jpeg"
                        alt="Semrush Group Buy"
                        class="w-full rounded-2xl border border-line shadow-sm"
                        loading="lazy"
                        width="1280"
                        height="427"
                    >
                </div>
            </div>

            <div class="mt-10">
                <x-pricing-controls />
            </div>

            <p class="mt-3 text-center text-xs text-ink-muted">Billing period and currency apply to all plans below.</p>

            <div class="plans-grid plans-grid-semrush mt-8">
                @foreach ($semrushPlans as $index => $plan)
                    <x-plan-card :plan="$plan" :index="$index" variant="main" />
                @endforeach
            </div>

            {{-- Plan footnotes --}}
            <div class="mx-auto mt-10 max-w-3xl space-y-3 rounded-2xl border border-line bg-surface/60 p-5">
                @foreach ($planNotes as $note)
                    <p class="text-xs leading-relaxed text-ink-secondary">
                        <sup class="font-bold text-accent">{{ $note['marker'] }}</sup>
                        {{ $note['text'] }}
                    </p>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Individual Ahrefs Plans --}}
    <section id="ahrefs-plans" class="border-t border-line bg-white py-20">
        <div class="plans-section">
            <div class="text-center">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-blue/10 px-3 py-1 text-xs font-semibold text-blue">
                    <img src="https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094" alt="" class="h-4 w-4 object-contain" loading="lazy">
                    Ahrefs Plans
                </div>
                <h2 class="section-heading">Individual Ahrefs Plans</h2>
                <p class="section-sub mx-auto">Need more credits and exports? Choose the Ahrefs plan that matches your usage. Save up to 20% on annual plans.</p>
            </div>

            <div class="shop-tools-grid shop-tools-grid-ahrefs mt-12">
                @foreach ($ahrefsPlans as $index => $tool)
                    <x-shop-tool-card :tool="$tool" :index="$index" />
                @endforeach
            </div>

            <p class="mx-auto mt-8 max-w-2xl text-center text-xs text-ink-muted">
                All Ahrefs plans include Keyword Explorer and Site Explorer with one-click cloud access.
            </p>
        </div>
    </section>

    {{-- Semrush Group Buy SEO Section --}}
    <section id="semrush-group-buy" class="border-t border-line bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <img src="https://ik.imagekit.io/webfiles/semrush.svg?updatedAt=1771433645768" alt="Semrush group buy - buy Semrush cheap at Semrushtoolz" class="mb-6 h-12 w-auto" loading="lazy">
                    <h2 class="section-heading">Semrush Group Buy — Buy Semrush at Low Price</h2>
                    <p class="section-sub mt-3">
                        Looking for <strong>semrush group buy</strong> or <strong>group buy semrush</strong>?
                        Semrushtoolz offers the cheapest Semrush account in India starting at just ₹149/month.
                        Get unlimited keyword analysis, domain analysis, and export features with
                        <strong>one-click cloud access</strong> — no Semrush login to official site needed.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-ink-secondary">
                        <li class="flex gap-2"><span class="text-success">✓</span> Semrush cheap price — from ₹149/month ($3)</li>
                        <li class="flex gap-2"><span class="text-success">✓</span> Semrush tool with site audit plan at ₹499/month</li>
                        <li class="flex gap-2"><span class="text-success">✓</span> Buy Semrush account with PayPal &amp; UPI</li>
                        <li class="flex gap-2"><span class="text-success">✓</span> Semrush support via 24/7 ticket system</li>
                    </ul>
                    <a href="{{ route('tools.semrush') }}" class="ui-btn-primary mt-8 inline-flex">View Semrush Plans &amp; Pricing</a>
                </div>
                <div class="ui-card p-8">
                    <h3 class="text-lg font-bold text-ink">Why Buy Semrush on Semrushtoolz?</h3>
                    <p class="mt-3 text-sm leading-relaxed text-ink-secondary">
                        Official Semrush pricing is expensive for freelancers and small agencies.
                        Our <strong>semrush groupbuy</strong> service gives you the same Semrush tools —
                        keyword research, domain analysis, competitor research, and site audit —
                        at a <strong>semrush low price</strong> you can actually afford.
                        Whether you need a basic <strong>semrush tool</strong> or full
                        <strong>semrush guru group buy</strong> level access, we have a plan for you.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Ahrefs Group Buy SEO Section --}}
    <section id="ahrefs-group-buy" class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="ui-card order-2 p-8 lg:order-1">
                    <h3 class="text-lg font-bold text-ink">Ahrefs Group Buy India — Cheap Ahrefs Account</h3>
                    <p class="mt-3 text-sm leading-relaxed text-ink-secondary">
                        Need <strong>ahrefs group buy</strong>, <strong>ahrefs groupbuy</strong>, or
                        <strong>buy ahrefs cheap</strong>? Semrushtoolz is the best
                        <strong>ahrefs group buy India</strong> platform with 4 flexible plans.
                        Get <strong>ahrefs premium</strong> access with Keyword Explorer, Site Explorer,
                        and daily credits — all via secure cloud access.
                        <strong>Ahrefs in cheap price</strong> starting at ₹699/month.
                    </p>
                </div>
                <div class="order-1 lg:order-2">
                    <img src="https://ik.imagekit.io/webfiles/ahrefs.svg?updatedAt=1771433639094" alt="Ahrefs group buy India - buy ahrefs account cheap" class="mb-6 h-10 w-auto" loading="lazy">
                    <h2 class="section-heading">Ahrefs Group Buy — Buy Ahrefs at Cheap Price</h2>
                    <p class="section-sub mt-3">
                        <strong>SEO group buy Ahrefs</strong> plans with 30 to 200 credits per day.
                        Perfect for <strong>ahrefs seo group buy</strong> users who need
                        <strong>ahrefs tool buy</strong> access without paying full
                        <strong>ahrefs pricing</strong>. Also available in our
                        <strong>ahrefs semrush group buy</strong> combo.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-ink-secondary">
                        <li class="flex gap-2"><span class="text-success">✓</span> Ahrefs Plan 1 to 4 — ₹699 to ₹2,499/month</li>
                        <li class="flex gap-2"><span class="text-success">✓</span> Ahrefs cheap price for India, Pakistan &amp; worldwide</li>
                        <li class="flex gap-2"><span class="text-success">✓</span> Buy ahrefs account with instant activation</li>
                        <li class="flex gap-2"><span class="text-success">✓</span> Ahrefs Bar extension in combo plan</li>
                    </ul>
                    <a href="{{ route('tools.ahrefs') }}" class="ui-btn-primary mt-8 inline-flex">View Ahrefs Plans &amp; Pricing</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Group Buy SEO Tools --}}
    <section id="group-buy-seo-tools" class="border-y border-line bg-white py-20">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="section-heading">Best Group Buy SEO Tools — Semrush &amp; Ahrefs Together</h2>
            <p class="section-sub mx-auto mt-4 leading-relaxed">
                Semrushtoolz is the <strong>best group buy SEO tools</strong> website for
                <strong>group buy SEO tools Semrush</strong> and
                <strong>group buy SEO tools Ahrefs</strong>.
                Our <strong>Semrush Ahrefs group buy</strong> combo at ₹799/month is the
                <strong>best group buy SEO tools service</strong> — includes Semrush, Ahrefs Plan 1,
                Ahrefs Bar, and bonus tools. Whether you search for
                <strong>groupbuy semrush</strong>, <strong>groupbuy ahrefs</strong>,
                <strong>ahrefs buy group</strong>, or <strong>buy SEO tool</strong> —
                Semrushtoolz has you covered with the lowest prices and fastest access.
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-2">
                @foreach (['semrush group buy', 'ahrefs group buy', 'buy semrush', 'buy ahrefs', 'cheap ahrefs account', 'semrush cheap', 'ahrefs groupbuy', 'seo group buy', 'group buy india', 'semrush toolz'] as $tag)
                    <span class="ui-badge bg-surface text-ink-secondary">{{ $tag }}</span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section id="how-it-works" class="border-y border-line bg-white py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="section-heading">How It Works</h2>
                <p class="section-sub mx-auto">Three simple steps to start using premium SEO tools today.</p>
            </div>

            <div class="mt-14 grid gap-8 md:grid-cols-3">
                @foreach ([
                    ['step' => '01', 'title' => 'Choose a Plan', 'desc' => 'Pick Semrush, Ahrefs, Combo, or an individual Ahrefs plan based on your credits needs.', 'icon' => 'shopping-cart'],
                    ['step' => '02', 'title' => 'Pay Your Way', 'desc' => 'India: UPI, PayPal, or offline. International: PayPal or offline. Choose 1, 3, 6, or 12-month billing.', 'icon' => 'credit-card'],
                    ['step' => '03', 'title' => 'One-Click Access', 'desc' => 'Open your dashboard and hit Access Now. Cloud tools need no extension — instant access.', 'icon' => 'zap'],
                ] as $item)
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

    {{-- Features --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="ui-card overflow-hidden">
                <div class="grid lg:grid-cols-2">
                    <div class="p-8 lg:p-12">
                        <h2 class="section-heading">Why Semrushtoolz?</h2>
                        <p class="section-sub">Built specifically for SEO professionals who need reliable, affordable tool access.</p>

                        <div class="mt-8 space-y-5">
                            @foreach ([
                                ['One-Click Cloud Access', 'Semrush & Ahrefs open instantly — no extension, no cookies, no hassle.'],
                                ['Multiple Payment Options', 'PayPal, auto-verified UPI, and offline payment for maximum flexibility.'],
                                ['Secure & Reliable', 'Enterprise-grade cloud system with seat management and session tracking.'],
                                ['Affiliate Program', 'Earn '.$affiliateCommissionRate.'% commission on every referral. Built-in dashboard tracking.'],
                            ] as $feat)
                                <div class="flex gap-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-ink">{{ $feat[0] }}</h4>
                                        <p class="mt-0.5 text-sm text-ink-secondary">{{ $feat[1] }}</p>
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
                                <button class="ui-btn-primary mt-3 w-full py-2 text-xs">Access Now</button>
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
                                <button class="ui-btn-outline mt-3 w-full py-2 text-xs">Access Now</button>
                            </div>
                            <p class="text-center text-xs text-white/50">Dashboard preview — your tools, one click away</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="border-t border-line bg-white py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="section-heading">Frequently Asked Questions — Group Buy SEO Tools</h2>
                <p class="section-sub mx-auto">Answers about Semrush group buy, Ahrefs group buy, pricing, login &amp; access.</p>
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

    {{-- CTA --}}
    <section class="py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl bg-ink px-8 py-14 text-center sm:px-16">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(240,90,40,0.25),transparent_60%)]"></div>
                <div class="relative">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">Ready to boost your SEO?</h2>
                    <p class="mx-auto mt-4 max-w-lg text-white/70">Join 2,500+ SEO professionals. Semrush group buy from ₹149, Ahrefs group buy from ₹699. <a href="{{ route('login') }}" class="underline hover:text-white">Login</a> or <a href="{{ route('register') }}" class="underline hover:text-white">sign up</a> now.</p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="ui-btn-primary px-8 py-3 text-base">Get Started Now</a>
                        <a href="#plans" class="ui-btn border border-white/20 bg-transparent text-white hover:bg-white/10">Compare Plans</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
