@props(['section'])

@php
    $bg = match ($section['background'] ?? 'white') {
        'gray' => 'bg-surface border-t border-line',
        'dark' => 'bg-ink text-white',
        default => 'bg-white border-t border-line',
    };
    $layout = $section['layout'] ?? 'centered';
@endphp

<section id="{{ $section['id'] }}" class="py-20 {{ $bg }}">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if ($layout === 'two_column')
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    @if ($section['heading'])
                        <h2 @class(['section-heading', 'text-white' => ($section['background'] ?? '') === 'dark'])>{{ $section['heading'] }}</h2>
                    @endif
                    @if ($section['subheading'])
                        <p @class(['section-sub mt-3', 'text-white/70' => ($section['background'] ?? '') === 'dark'])>{{ $section['subheading'] }}</p>
                    @endif
                    @if ($section['body_html'])
                        <div @class(['mt-4 text-sm leading-relaxed', ($section['background'] ?? '') === 'dark' ? 'text-white/80' : 'text-ink-secondary'])>{!! $section['body_html'] !!}</div>
                    @endif
                    @if (! empty($section['bullets']))
                        <ul class="mt-6 space-y-2 text-sm text-ink-secondary">
                            @foreach ($section['bullets'] as $bullet)
                                <li class="flex gap-2"><span class="text-success">✓</span> {{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($section['cta_label'])
                        <a href="{{ $section['cta_url'] ?: '#' }}" class="ui-btn-primary mt-8 inline-flex">{{ $section['cta_label'] }}</a>
                    @endif
                </div>
                @if ($section['image_url'])
                    <div>
                        <img src="{{ $section['image_url'] }}" alt="{{ $section['image_alt'] ?: $section['heading'] }}" class="w-full rounded-2xl border border-line shadow-sm" loading="lazy">
                    </div>
                @endif
            </div>
        @elseif ($layout === 'cta_banner')
            <div class="relative overflow-hidden rounded-3xl bg-ink px-8 py-12 text-center sm:px-16">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(240,90,40,0.2),transparent_60%)]"></div>
                <div class="relative">
                    @if ($section['heading'])
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">{{ $section['heading'] }}</h2>
                    @endif
                    @if ($section['body_html'])
                        <div class="mx-auto mt-4 max-w-2xl text-sm text-white/70">{!! $section['body_html'] !!}</div>
                    @endif
                    @if ($section['cta_label'])
                        <a href="{{ $section['cta_url'] ?: route('register') }}" class="ui-btn-primary mt-6 inline-flex">{{ $section['cta_label'] }}</a>
                    @endif
                </div>
            </div>
        @else
            <div class="mx-auto max-w-4xl text-center">
                @if ($section['heading'])
                    <h2 @class(['section-heading', 'text-white' => ($section['background'] ?? '') === 'dark'])>{{ $section['heading'] }}</h2>
                @endif
                @if ($section['subheading'])
                    <p @class(['section-sub mx-auto mt-3', 'text-white/70' => ($section['background'] ?? '') === 'dark'])>{{ $section['subheading'] }}</p>
                @endif
                @if ($section['image_url'])
                    <img src="{{ $section['image_url'] }}" alt="{{ $section['image_alt'] ?: $section['heading'] }}" class="mx-auto mt-8 max-w-3xl rounded-2xl border border-line shadow-sm" loading="lazy">
                @endif
                @if ($section['body_html'])
                    <div @class(['mx-auto mt-6 max-w-3xl text-left text-sm leading-relaxed sm:text-center', ($section['background'] ?? '') === 'dark' ? 'text-white/80' : 'text-ink-secondary'])>{!! $section['body_html'] !!}</div>
                @endif
                @if (! empty($section['bullets']))
                    <ul class="mx-auto mt-6 max-w-xl space-y-2 text-left text-sm text-ink-secondary">
                        @foreach ($section['bullets'] as $bullet)
                            <li class="flex gap-2"><span class="text-success">✓</span> {{ $bullet }}</li>
                        @endforeach
                    </ul>
                @endif
                @if ($section['cta_label'])
                    <a href="{{ $section['cta_url'] ?: '#' }}" class="ui-btn-primary mt-8 inline-flex">{{ $section['cta_label'] }}</a>
                @endif
            </div>
        @endif
    </div>
</section>
