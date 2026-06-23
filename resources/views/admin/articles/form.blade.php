@extends('layouts.admin')

@section('title', $article->exists ? 'Edit Tool Page' : 'Add Tool Page')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.articles.index') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Tool Pages</a>
        <h1 class="dash-page-title mt-2">{{ $article->exists ? 'Edit Tool Page' : 'Add Tool Page' }}</h1>
        <p class="mt-1 text-sm text-ink-secondary">Product landing page with pricing, FAQs and purchase CTAs. Use URL path <code class="text-xs">tools/your-slug</code> for tool purchase pages.</p>
        @if ($article->exists)
            <a href="{{ route('admin.articles.preview', $article) }}" target="_blank" class="mt-3 inline-flex ui-btn-ghost text-sm">Preview page</a>
        @endif
    </div>

    <form method="POST" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}" class="space-y-6">
        @csrf
        @if ($article->exists) @method('PUT') @endif

        <div class="dash-card max-w-4xl space-y-4">
            <h2 class="font-semibold text-ink">Basic</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="ui-label">Title</label><input class="ui-input" name="title" value="{{ old('title', $article->title) }}" required></div>
                <div><label class="ui-label">URL path</label><input class="ui-input font-mono" name="url_path" value="{{ old('url_path', $article->url_path) }}" placeholder="tools/semrush-group-buy" required>
                    <p class="mt-1 text-xs text-ink-muted">Tool pages: prefix with <code>tools/</code> (e.g. tools/envato-group-buy)</p>
                </div>
            </div>
            <div><label class="ui-label">Breadcrumb label</label><input class="ui-input" name="breadcrumb_label" value="{{ old('breadcrumb_label', $article->breadcrumb_label) }}"></div>
            <div>
                <label class="ui-label">Linked Tool</label>
                <select class="ui-input" name="tool_id" id="article-tool-id">
                    <option value="">— None —</option>
                    @foreach ($tools as $tool)
                        <option value="{{ $tool->id }}" @selected(old('tool_id', $article->tool_id) == $tool->id)>{{ $tool->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex gap-2 text-sm"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $article->is_published))> Published</label>
        </div>

        <div class="dash-card max-w-4xl space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-semibold text-ink">SEO &amp; Meta Tags</h2>
                <button type="button" id="auto-article-seo" class="ui-btn-ghost text-sm">Auto-generate from linked tool</button>
            </div>
            <p class="text-xs text-ink-muted">Public page uses these for title, description, keywords, Open Graph and JSON-LD schema. Empty fields auto-fill from linked tool on the live site.</p>
            <div><label class="ui-label">SEO Title</label><input class="ui-input" name="seo_title" id="article-seo-title" value="{{ old('seo_title', $article->seo_title) }}" required></div>
            <div><label class="ui-label">SEO Description</label><textarea class="ui-input" name="seo_description" id="article-seo-description" rows="2" required>{{ old('seo_description', $article->seo_description) }}</textarea></div>
            <div><label class="ui-label">SEO Keywords</label><input class="ui-input" name="seo_keywords" id="article-seo-keywords" value="{{ old('seo_keywords', $article->seo_keywords) }}"></div>
            <div class="rounded-lg border border-line bg-canvas p-3 text-xs text-ink-secondary">
                <p class="font-medium text-ink">Google / social preview</p>
                <p class="mt-2 text-accent" id="preview-article-title">{{ $article->seo_title ?: 'Page title' }}</p>
                <p class="text-success" id="preview-article-url">{{ $article->url_path ? url('/'.$article->url_path) : url('/tools/your-slug') }}</p>
                <p class="mt-1" id="preview-article-desc">{{ $article->seo_description ?: 'Meta description preview.' }}</p>
            </div>
        </div>

        <div class="dash-card max-w-4xl space-y-4">
            <h2 class="font-semibold text-ink">Hero</h2>
            <div><label class="ui-label">Heading</label><input class="ui-input" name="hero_heading" id="article-hero-heading" value="{{ old('hero_heading', $article->hero_heading) }}" required></div>
            <div><label class="ui-label">Subtext (HTML allowed)</label><textarea class="ui-input" name="hero_subtext" rows="3">{{ old('hero_subtext', $article->hero_subtext) }}</textarea></div>
            <div>
                <label class="ui-label">Thumbnail / OG image URL</label>
                <input class="ui-input" name="hero_image" id="article-hero-image" value="{{ old('hero_image', $article->hero_image) }}">
                <p class="mt-1 text-xs text-ink-muted">Used in hero, Open Graph, Twitter card and Product schema</p>
                @if ($article->resolvedHeroImage())
                    <img src="{{ $article->resolvedHeroImage() }}" alt="" class="mt-2 h-12 w-auto rounded border border-line bg-white p-1">
                @endif
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="ui-label">Primary CTA label</label><input class="ui-input" name="hero_cta_label" value="{{ old('hero_cta_label', $article->hero_cta_label) }}"></div>
                <div><label class="ui-label">Primary CTA URL</label><input class="ui-input" name="hero_cta_url" value="{{ old('hero_cta_url', $article->hero_cta_url) }}" placeholder="/register"></div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="ui-label">Secondary CTA label</label><input class="ui-input" name="hero_secondary_label" value="{{ old('hero_secondary_label', $article->hero_secondary_label) }}"></div>
                <div><label class="ui-label">Secondary CTA URL</label><input class="ui-input" name="hero_secondary_url" value="{{ old('hero_secondary_url', $article->hero_secondary_url) }}" placeholder="/#plans"></div>
            </div>
        </div>

        <div class="dash-card max-w-4xl space-y-4">
            <h2 class="font-semibold text-ink">Pricing Section</h2>
            <label class="flex gap-2 text-sm"><input type="checkbox" name="show_pricing" value="1" @checked(old('show_pricing', $article->show_pricing ?? true))> Show pricing cards</label>
            <div><label class="ui-label">Pricing heading</label><input class="ui-input" name="pricing_heading" value="{{ old('pricing_heading', $article->pricing_heading) }}"></div>
            <div><label class="ui-label">Pricing subtext</label><textarea class="ui-input" name="pricing_subtext" rows="2">{{ old('pricing_subtext', $article->pricing_subtext) }}</textarea></div>
            <div>
                <label class="ui-label mb-2 block">Plans to show</label>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($plans as $plan)
                        <label class="flex items-center gap-2 rounded-lg border border-line p-2 text-sm">
                            <input type="checkbox" name="plan_slugs[]" value="{{ $plan->slug }}" @checked(in_array($plan->slug, old('plan_slugs', $article->plan_slugs ?? [])))>
                            {{ $plan->name }} <span class="text-xs text-ink-muted">({{ $plan->slug }})</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="dash-card max-w-4xl space-y-4">
            <h2 class="font-semibold text-ink">Content Blocks</h2>
            <p class="text-xs text-ink-muted">SEO sections with HTML content.</p>
            @php $blocks = old('content_blocks', $article->content_blocks ?? [['id' => '', 'heading' => '', 'html' => '']]); @endphp
            @foreach ($blocks as $i => $block)
                <div class="rounded-lg border border-line p-4 space-y-2">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <input class="ui-input" name="content_blocks[{{ $i }}][id]" value="{{ $block['id'] ?? '' }}" placeholder="section-id">
                        <input class="ui-input" name="content_blocks[{{ $i }}][heading]" value="{{ $block['heading'] ?? '' }}" placeholder="Section heading">
                    </div>
                    <textarea class="ui-input min-h-[80px]" name="content_blocks[{{ $i }}][html]" placeholder="HTML content">{{ $block['html'] ?? '' }}</textarea>
                </div>
            @endforeach
            <div class="rounded-lg border border-dashed border-line p-4 space-y-2">
                <div class="grid gap-2 sm:grid-cols-2">
                    <input class="ui-input" name="content_blocks[{{ count($blocks) }}][id]" placeholder="new-section-id">
                    <input class="ui-input" name="content_blocks[{{ count($blocks) }}][heading]" placeholder="New section heading">
                </div>
                <textarea class="ui-input min-h-[80px]" name="content_blocks[{{ count($blocks) }}][html]" placeholder="New section HTML"></textarea>
            </div>
        </div>

        <div class="dash-card max-w-4xl space-y-4">
            <h2 class="font-semibold text-ink">Features</h2>
            @php $features = $article->features ?? []; @endphp
            @for ($i = 0; $i < max(3, count($features)); $i++)
                <div class="grid gap-2 sm:grid-cols-2">
                    <input class="ui-input" name="features[title][]" value="{{ old('features.title.'.$i, $features[$i][0] ?? '') }}" placeholder="Feature title">
                    <input class="ui-input" name="features[desc][]" value="{{ old('features.desc.'.$i, $features[$i][1] ?? '') }}" placeholder="Description">
                </div>
            @endfor
        </div>

        <div class="dash-card max-w-4xl space-y-4">
            <h2 class="font-semibold text-ink">Steps</h2>
            @php $steps = $article->steps ?? []; @endphp
            @for ($i = 0; $i < max(4, count($steps)); $i++)
                <div class="grid gap-2 sm:grid-cols-3">
                    <input class="ui-input" name="steps[num][]" value="{{ old('steps.num.'.$i, $steps[$i][0] ?? sprintf('%02d', $i + 1)) }}" placeholder="01">
                    <input class="ui-input" name="steps[title][]" value="{{ old('steps.title.'.$i, $steps[$i][1] ?? '') }}" placeholder="Step title">
                    <input class="ui-input" name="steps[desc][]" value="{{ old('steps.desc.'.$i, $steps[$i][2] ?? '') }}" placeholder="Description">
                </div>
            @endfor
        </div>

        <div class="dash-card max-w-4xl space-y-4">
            <h2 class="font-semibold text-ink">Highlights & FAQ</h2>
            @php $highlights = $article->highlights ?? []; @endphp
            @for ($i = 0; $i < max(4, count($highlights)); $i++)
                <input class="ui-input" name="highlights[]" value="{{ old('highlights.'.$i, $highlights[$i] ?? '') }}" placeholder="Highlight bullet point">
            @endfor
            <hr class="border-line">
            @php $faqs = $article->faqs ?? []; @endphp
            @for ($i = 0; $i < max(3, count($faqs)); $i++)
                <div class="space-y-2">
                    <input class="ui-input" name="faqs[q][]" value="{{ old('faqs.q.'.$i, $faqs[$i]['q'] ?? '') }}" placeholder="Question">
                    <textarea class="ui-input" name="faqs[a][]" rows="2" placeholder="Answer">{{ old('faqs.a.'.$i, $faqs[$i]['a'] ?? '') }}</textarea>
                </div>
            @endfor
        </div>

        <div class="dash-card max-w-4xl space-y-4">
            <h2 class="font-semibold text-ink">Footer CTA</h2>
            <div><label class="ui-label">Heading</label><input class="ui-input" name="footer_cta_heading" value="{{ old('footer_cta_heading', $article->footer_cta_heading) }}"></div>
            <div><label class="ui-label">Subtext</label><textarea class="ui-input" name="footer_cta_subtext" rows="2">{{ old('footer_cta_subtext', $article->footer_cta_subtext) }}</textarea></div>
        </div>

        <button type="submit" class="ui-btn-primary">Save Tool Page</button>
    </form>

    <script>
        const articleSeoIds = ['article-seo-title', 'article-seo-description', 'article-seo-keywords'];
        function updateArticleSeoPreview() {
            const path = document.querySelector('[name=url_path]')?.value || 'tools/your-slug';
            document.getElementById('preview-article-title').textContent = document.getElementById('article-seo-title').value || 'Page title';
            document.getElementById('preview-article-url').textContent = `${window.location.origin}/${path.replace(/^\//, '')}`;
            document.getElementById('preview-article-desc').textContent = document.getElementById('article-seo-description').value || 'Meta description preview.';
        }
        articleSeoIds.forEach(id => document.getElementById(id)?.addEventListener('input', updateArticleSeoPreview));
        document.querySelector('[name=url_path]')?.addEventListener('input', updateArticleSeoPreview);

        document.getElementById('auto-article-seo')?.addEventListener('click', async () => {
            const toolId = document.getElementById('article-tool-id')?.value;
            if (!toolId) {
                alert('Please select a linked tool first.');
                return;
            }
            const params = new URLSearchParams({
                tool_id: toolId,
                title: document.querySelector('[name=title]')?.value || '',
            });
            const res = await fetch(`{{ route('admin.articles.seo-suggestions') }}?${params}`);
            const data = await res.json();
            const set = (id, val) => { const el = document.getElementById(id); if (el && val) el.value = val; };
            set('article-seo-title', data.seo_title);
            set('article-seo-description', data.seo_description);
            set('article-seo-keywords', data.seo_keywords);
            set('article-hero-heading', data.hero_heading);
            set('article-hero-image', data.hero_image);
            if (data.title && !document.querySelector('[name=title]').value) {
                document.querySelector('[name=title]').value = data.title;
            }
            if (data.breadcrumb_label && !document.querySelector('[name=breadcrumb_label]').value) {
                document.querySelector('[name=breadcrumb_label]').value = data.breadcrumb_label;
            }
            if (data.url_path && !document.querySelector('[name=url_path]').value) {
                document.querySelector('[name=url_path]').value = data.url_path;
            }
            updateArticleSeoPreview();
        });
        updateArticleSeoPreview();
    </script>
@endsection
