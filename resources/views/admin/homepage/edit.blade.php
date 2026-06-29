@extends('layouts.admin')

@section('title', 'Homepage')

@section('content')
    @php
        $hp = $homepage;
        $tab = request('tab', 'seo');
        $tabs = [
            'seo' => 'SEO & Meta',
            'hero' => 'Hero & Stats',
            'plans' => 'Plans Sections',
            'content' => 'SEO Content',
            'faq' => 'FAQ & CTA',
        ];
    @endphp

    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="dash-page-title">Homepage Editor</h1>
            <p class="text-sm text-ink-muted">Customize the public homepage at <a href="{{ route('home') }}" target="_blank" class="text-accent hover:underline">{{ url('/') }}</a></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('home') }}" target="_blank" class="ui-btn-outline text-sm">Preview site</a>
            <form method="POST" action="{{ route('admin.homepage.reset') }}" onsubmit="return confirm('Reset homepage to default content? Your custom text will be lost.')">
                @csrf
                <button type="submit" class="ui-btn-ghost text-sm text-danger">Reset to defaults</button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
            <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.homepage.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="tab" value="{{ $tab }}">

        <x-dash-tabs :tabs="$tabs" :active="$tab" :preserve="[]">
            <div @class(['space-y-6', 'hidden' => $tab !== 'seo'])>
                <div class="dash-card max-w-4xl space-y-4">
                    <h2 class="font-semibold text-ink">SEO &amp; Meta Tags</h2>
                    <p class="text-xs text-ink-muted">Controls page title, description, keywords, Open Graph image and schema text.</p>
                    <div><label class="ui-label">SEO Title</label><input class="ui-input" name="seo_title" value="{{ old('seo_title', $hp['seo']['title']) }}" required></div>
                    <div><label class="ui-label">Meta Description</label><textarea class="ui-input" name="seo_description" rows="3" required>{{ old('seo_description', $hp['seo']['description']) }}</textarea></div>
                    <div><label class="ui-label">Meta Keywords</label><input class="ui-input" name="seo_keywords" value="{{ old('seo_keywords', $hp['seo']['keywords']) }}"></div>
                    <div><label class="ui-label">OG / Social Image URL</label><input class="ui-input" name="seo_og_image" value="{{ old('seo_og_image', $hp['seo']['og_image']) }}" placeholder="https://..."></div>
                    <div><label class="ui-label">Organization description (schema)</label><input class="ui-input" name="seo_organization_description" value="{{ old('seo_organization_description', $hp['seo']['organization_description']) }}"></div>
                </div>
            </div>

            <div @class(['space-y-6 max-w-4xl', 'hidden' => $tab !== 'hero'])>
                    <div class="dash-card space-y-4">
                        <h2 class="font-semibold text-ink">Hero section</h2>
                        <div><label class="ui-label">Top badge text</label><input class="ui-input" name="hero_badge" value="{{ old('hero_badge', $hp['hero']['badge']) }}"></div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label class="ui-label">Heading line 1</label><input class="ui-input" name="hero_heading_line1" value="{{ old('hero_heading_line1', $hp['hero']['heading_line1']) }}" required></div>
                            <div><label class="ui-label">Heading line 2</label><input class="ui-input" name="hero_heading_line2" value="{{ old('hero_heading_line2', $hp['hero']['heading_line2']) }}"></div>
                        </div>
                        <div><label class="ui-label">Subtext (HTML allowed)</label><textarea class="ui-input min-h-[100px]" name="hero_subtext_html" rows="4">{{ old('hero_subtext_html', $hp['hero']['subtext_html']) }}</textarea></div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label class="ui-label">Primary button label</label><input class="ui-input" name="hero_cta_primary_label" value="{{ old('hero_cta_primary_label', $hp['hero']['cta_primary_label']) }}"></div>
                            <div><label class="ui-label">Primary button URL</label><input class="ui-input" name="hero_cta_primary_url" value="{{ old('hero_cta_primary_url', $hp['hero']['cta_primary_url']) }}" placeholder="#plans"></div>
                            <div><label class="ui-label">Secondary button label</label><input class="ui-input" name="hero_cta_secondary_label" value="{{ old('hero_cta_secondary_label', $hp['hero']['cta_secondary_label']) }}"></div>
                            <div><label class="ui-label">Secondary button URL</label><input class="ui-input" name="hero_cta_secondary_url" value="{{ old('hero_cta_secondary_url', $hp['hero']['cta_secondary_url']) }}" placeholder="/register (leave blank for register page)"></div>
                        </div>
                    </div>
                    <div class="dash-card space-y-4">
                        <h2 class="font-semibold text-ink">Hero stats (4 boxes)</h2>
                        @for ($i = 0; $i < 4; $i++)
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input class="ui-input" name="stat_value[]" value="{{ old('stat_value.'.$i, $hp['stats'][$i]['value'] ?? '') }}" placeholder="Value e.g. 2,500+">
                                <input class="ui-input" name="stat_label[]" value="{{ old('stat_label.'.$i, $hp['stats'][$i]['label'] ?? '') }}" placeholder="Label e.g. Active Users">
                            </div>
                        @endfor
                    </div>
            </div>

            <div @class(['space-y-6 max-w-4xl', 'hidden' => $tab !== 'plans'])>
                    <div class="dash-card space-y-4">
                        <h2 class="font-semibold text-ink">Main plans section</h2>
                        <p class="text-xs text-ink-muted">Plan cards still come from Admin → Plans. Edit headings and banner here.</p>
                        <div><label class="ui-label">Section heading</label><input class="ui-input" name="plans_heading" value="{{ old('plans_heading', $hp['plans']['heading']) }}"></div>
                        <div><label class="ui-label">Section subheading</label><textarea class="ui-input" name="plans_subheading" rows="2">{{ old('plans_subheading', $hp['plans']['subheading']) }}</textarea></div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label class="ui-label">Banner image URL</label><input class="ui-input" name="plans_banner_image" value="{{ old('plans_banner_image', $hp['plans']['banner_image']) }}"></div>
                            <div><label class="ui-label">Banner image alt text</label><input class="ui-input" name="plans_banner_alt" value="{{ old('plans_banner_alt', $hp['plans']['banner_alt']) }}"></div>
                        </div>
                        <div><label class="ui-label">Billing note</label><input class="ui-input" name="plans_billing_note" value="{{ old('plans_billing_note', $hp['plans']['billing_note']) }}"></div>
                        <hr class="border-line">
                        <h3 class="text-sm font-semibold text-ink">Plan footnotes</h3>
                        @php $notes = $hp['plan_notes'] ?? []; @endphp
                        @for ($i = 0; $i < max(2, count($notes)); $i++)
                            <div class="grid gap-3 sm:grid-cols-[80px_1fr]">
                                <input class="ui-input" name="plan_note_marker[]" value="{{ old('plan_note_marker.'.$i, $notes[$i]['marker'] ?? '') }}" placeholder="*">
                                <input class="ui-input" name="plan_note_text[]" value="{{ old('plan_note_text.'.$i, $notes[$i]['text'] ?? '') }}" placeholder="Footnote text">
                            </div>
                        @endfor
                    </div>
                    <div class="dash-card space-y-4">
                        <h2 class="font-semibold text-ink">Ahrefs plans section</h2>
                        <div><label class="ui-label">Badge label</label><input class="ui-input" name="ahrefs_plans_badge" value="{{ old('ahrefs_plans_badge', $hp['ahrefs_plans']['badge']) }}"></div>
                        <div><label class="ui-label">Heading</label><input class="ui-input" name="ahrefs_plans_heading" value="{{ old('ahrefs_plans_heading', $hp['ahrefs_plans']['heading']) }}"></div>
                        <div><label class="ui-label">Subheading</label><textarea class="ui-input" name="ahrefs_plans_subheading" rows="2">{{ old('ahrefs_plans_subheading', $hp['ahrefs_plans']['subheading']) }}</textarea></div>
                        <div><label class="ui-label">Footnote</label><input class="ui-input" name="ahrefs_plans_footnote" value="{{ old('ahrefs_plans_footnote', $hp['ahrefs_plans']['footnote']) }}"></div>
                    </div>
            </div>

            <div @class(['space-y-6 max-w-4xl', 'hidden' => $tab !== 'content'])>
                    @foreach (['semrush' => 'Semrush SEO block', 'ahrefs' => 'Ahrefs SEO block'] as $prefix => $label)
                        @php $block = $hp["{$prefix}_block"]; @endphp
                        <div class="dash-card space-y-4">
                            <h2 class="font-semibold text-ink">{{ $label }}</h2>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div><label class="ui-label">Logo URL</label><input class="ui-input" name="{{ $prefix }}_logo_url" value="{{ old($prefix.'_logo_url', $block['logo_url']) }}"></div>
                                <div><label class="ui-label">Logo alt text</label><input class="ui-input" name="{{ $prefix }}_logo_alt" value="{{ old($prefix.'_logo_alt', $block['logo_alt']) }}"></div>
                            </div>
                            <div><label class="ui-label">Main heading</label><input class="ui-input" name="{{ $prefix }}_heading" value="{{ old($prefix.'_heading', $block['heading']) }}"></div>
                            <div><label class="ui-label">Intro (HTML)</label><textarea class="ui-input min-h-[80px]" name="{{ $prefix }}_intro_html" rows="4">{{ old($prefix.'_intro_html', $block['intro_html']) }}</textarea></div>
                            <div><label class="ui-label">Bullet points (one per line)</label><textarea class="ui-input" name="{{ $prefix }}_bullets" rows="4">{{ old($prefix.'_bullets', implode("\n", $block['bullets'] ?? [])) }}</textarea></div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div><label class="ui-label">CTA label</label><input class="ui-input" name="{{ $prefix }}_cta_label" value="{{ old($prefix.'_cta_label', $block['cta_label']) }}"></div>
                                <div><label class="ui-label">CTA URL</label><input class="ui-input" name="{{ $prefix }}_cta_url" value="{{ old($prefix.'_cta_url', $block['cta_url']) }}" placeholder="Leave blank for tool page"></div>
                            </div>
                            <div><label class="ui-label">Sidebar heading</label><input class="ui-input" name="{{ $prefix }}_sidebar_heading" value="{{ old($prefix.'_sidebar_heading', $block['sidebar_heading']) }}"></div>
                            <div><label class="ui-label">Sidebar text (HTML)</label><textarea class="ui-input min-h-[80px]" name="{{ $prefix }}_sidebar_html" rows="4">{{ old($prefix.'_sidebar_html', $block['sidebar_html']) }}</textarea></div>
                        </div>
                    @endforeach

                    <div class="dash-card space-y-4">
                        <h2 class="font-semibold text-ink">Combo / keywords section</h2>
                        <div><label class="ui-label">Heading</label><input class="ui-input" name="combo_heading" value="{{ old('combo_heading', $hp['combo_block']['heading']) }}"></div>
                        <div><label class="ui-label">Body (HTML)</label><textarea class="ui-input min-h-[100px]" name="combo_body_html" rows="5">{{ old('combo_body_html', $hp['combo_block']['body_html']) }}</textarea></div>
                        <div><label class="ui-label">Keyword tags (one per line)</label><textarea class="ui-input" name="combo_tags" rows="4">{{ old('combo_tags', is_array($hp['combo_block']['tags'] ?? null) ? implode("\n", $hp['combo_block']['tags']) : $hp['combo_block']['tags']) }}</textarea></div>
                    </div>

                    <div class="dash-card space-y-4">
                        <h2 class="font-semibold text-ink">How it works</h2>
                        <div><label class="ui-label">Heading</label><input class="ui-input" name="how_heading" value="{{ old('how_heading', $hp['how_it_works']['heading']) }}"></div>
                        <div><label class="ui-label">Subheading</label><input class="ui-input" name="how_subheading" value="{{ old('how_subheading', $hp['how_it_works']['subheading']) }}"></div>
                        @php $steps = $hp['how_it_works']['steps'] ?? []; @endphp
                        @for ($i = 0; $i < max(3, count($steps)); $i++)
                            <div class="rounded-lg border border-line p-3 space-y-2">
                                <div class="grid gap-2 sm:grid-cols-3">
                                    <input class="ui-input" name="step_num[]" value="{{ old('step_num.'.$i, $steps[$i]['step'] ?? sprintf('%02d', $i + 1)) }}" placeholder="01">
                                    <input class="ui-input sm:col-span-2" name="step_title[]" value="{{ old('step_title.'.$i, $steps[$i]['title'] ?? '') }}" placeholder="Step title">
                                </div>
                                <input class="ui-input" name="step_desc[]" value="{{ old('step_desc.'.$i, $steps[$i]['desc'] ?? '') }}" placeholder="Description">
                                <input type="hidden" name="step_icon[]" value="{{ old('step_icon.'.$i, $steps[$i]['icon'] ?? 'zap') }}">
                            </div>
                        @endfor
                    </div>

                    <div class="dash-card space-y-4">
                        <h2 class="font-semibold text-ink">Why Semrushtoolz features</h2>
                        <div><label class="ui-label">Heading</label><input class="ui-input" name="features_heading" value="{{ old('features_heading', $hp['features']['heading']) }}"></div>
                        <div><label class="ui-label">Subheading</label><input class="ui-input" name="features_subheading" value="{{ old('features_subheading', $hp['features']['subheading']) }}"></div>
                        <p class="text-xs text-ink-muted">Use <code>{affiliate_rate}</code> in feature text for live commission %.</p>
                        @php $features = $hp['features']['items'] ?? []; @endphp
                        @for ($i = 0; $i < max(4, count($features)); $i++)
                            <div class="grid gap-2 sm:grid-cols-2">
                                <input class="ui-input" name="feature_title[]" value="{{ old('feature_title.'.$i, $features[$i]['title'] ?? '') }}" placeholder="Feature title">
                                <input class="ui-input" name="feature_desc[]" value="{{ old('feature_desc.'.$i, $features[$i]['desc'] ?? '') }}" placeholder="Description">
                            </div>
                        @endfor
                    </div>
            </div>

            <div @class(['space-y-6 max-w-4xl', 'hidden' => $tab !== 'faq'])>
                    <div class="dash-card space-y-4">
                        <h2 class="font-semibold text-ink">FAQ section</h2>
                        <div><label class="ui-label">Heading</label><input class="ui-input" name="faq_heading" value="{{ old('faq_heading', $hp['faq']['heading']) }}"></div>
                        <div><label class="ui-label">Subheading</label><input class="ui-input" name="faq_subheading" value="{{ old('faq_subheading', $hp['faq']['subheading']) }}"></div>
                        @php $faqs = $hp['faq']['items'] ?? []; @endphp
                        @for ($i = 0; $i < max(5, count($faqs)); $i++)
                            <div class="rounded-lg border border-line p-3 space-y-2">
                                <input class="ui-input" name="faq_q[]" value="{{ old('faq_q.'.$i, $faqs[$i]['q'] ?? '') }}" placeholder="Question">
                                <textarea class="ui-input" name="faq_a[]" rows="2" placeholder="Answer">{{ old('faq_a.'.$i, $faqs[$i]['a'] ?? '') }}</textarea>
                            </div>
                        @endfor
                    </div>
                    <div class="dash-card space-y-4">
                        <h2 class="font-semibold text-ink">Bottom CTA banner</h2>
                        <div><label class="ui-label">Heading</label><input class="ui-input" name="cta_heading" value="{{ old('cta_heading', $hp['cta']['heading']) }}"></div>
                        <div><label class="ui-label">Subtext (HTML)</label><textarea class="ui-input" name="cta_subtext_html" rows="3">{{ old('cta_subtext_html', $hp['cta']['subtext_html']) }}</textarea>
                            <p class="mt-1 text-xs text-ink-muted">Use <code>{login_url}</code> and <code>{register_url}</code> for links.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label class="ui-label">Primary button label</label><input class="ui-input" name="cta_primary_label" value="{{ old('cta_primary_label', $hp['cta']['primary_label']) }}"></div>
                            <div><label class="ui-label">Primary button URL</label><input class="ui-input" name="cta_primary_url" value="{{ old('cta_primary_url', $hp['cta']['primary_url']) }}" placeholder="/register"></div>
                            <div><label class="ui-label">Secondary button label</label><input class="ui-input" name="cta_secondary_label" value="{{ old('cta_secondary_label', $hp['cta']['secondary_label']) }}"></div>
                            <div><label class="ui-label">Secondary button URL</label><input class="ui-input" name="cta_secondary_url" value="{{ old('cta_secondary_url', $hp['cta']['secondary_url']) }}" placeholder="#plans"></div>
                        </div>
                    </div>
            </div>
        </x-dash-tabs>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="ui-btn-primary">Save homepage</button>
            <a href="{{ route('home') }}" target="_blank" class="ui-btn-outline">Preview</a>
        </div>
    </form>
@endsection
