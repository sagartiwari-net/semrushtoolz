@extends('layouts.admin')

@section('title', $tool->exists ? 'Edit Tool' : 'Add Tool')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.tools.index') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Tools</a>
        <h1 class="dash-page-title mt-2">{{ $tool->exists ? 'Edit Tool' : 'Add Tool' }}</h1>
        <p class="text-sm text-ink-secondary">Set tool type, reference price, and access method. Link to a Tool Group for access buttons.</p>
        @if ($tool->exists)
            <a href="{{ route('admin.tools.preview', $tool) }}" target="_blank" class="mt-2 inline-flex ui-btn-ghost text-sm">Preview</a>
        @endif
    </div>

    <form method="POST" action="{{ $tool->exists ? route('admin.tools.update', $tool) : route('admin.tools.store') }}" class="space-y-6" id="tool-form">
        @csrf @if($tool->exists) @method('PUT') @endif

        <div class="dash-card max-w-2xl space-y-4">
            <h2 class="font-semibold text-ink">Basic Info</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="ui-label">Name</label><input class="ui-input" name="name" value="{{ old('name', $tool->name) }}" required></div>
                <div><label class="ui-label">Slug</label><input class="ui-input font-mono" name="slug" value="{{ old('slug', $tool->slug) }}" placeholder="ubersuggest" required></div>
            </div>
            <div><label class="ui-label">Description</label><input class="ui-input" name="description" value="{{ old('description', $tool->description) }}"></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="ui-label">Logo URL</label>
                    <input class="ui-input" name="logo_url" id="tool-logo-url" value="{{ old('logo_url', $tool->logo_url) }}">
                    <p class="mt-1 text-xs text-ink-muted">Small icon for shop cards</p>
                </div>
                <div>
                    <label class="ui-label">Thumbnail URL</label>
                    <input class="ui-input" name="thumbnail_url" id="tool-thumbnail-url" value="{{ old('thumbnail_url', $tool->thumbnail_url) }}">
                    <p class="mt-1 text-xs text-ink-muted">OG image / larger preview (falls back to logo)</p>
                    @if ($tool->thumbnailUrl())
                        <img src="{{ $tool->thumbnailUrl() }}" alt="" class="mt-2 h-12 w-auto rounded border border-line bg-white p-1">
                    @endif
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="ui-label">Reference price INR</label><input class="ui-input" type="number" name="price_inr" value="{{ old('price_inr', $tool->price_inr) }}" min="0" placeholder="149"></div>
                <div><label class="ui-label">Reference price USD</label><input class="ui-input" type="number" name="price_usd" value="{{ old('price_usd', $tool->price_usd) }}" min="0" placeholder="3"></div>
            </div>
            <div><label class="ui-label">Other currencies (JSON)</label><input class="ui-input font-mono text-sm" name="prices_json" value="{{ old('prices_json', $tool->prices ? json_encode($tool->prices) : '') }}" placeholder='{"EUR":5,"GBP":4}'><p class="mt-1 text-xs text-ink-muted">Optional — for future multi-currency support</p></div>
            <div><label class="ui-label">Sort order</label><input class="ui-input" type="number" name="sort_order" value="{{ old('sort_order', $tool->sort_order ?? 0) }}"></div>
            <label class="flex gap-2 text-sm"><input type="checkbox" name="show_in_shop" value="1" @checked(old('show_in_shop', $tool->show_in_shop ?? true))> Show in shop (auto-appears when price is set)</label>
            <label class="flex gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tool->is_active ?? true))> Active</label>
        </div>

        <div class="dash-card max-w-2xl space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-semibold text-ink">SEO &amp; Meta Tags</h2>
                <button type="button" id="auto-tool-seo" class="ui-btn-ghost text-sm">Auto-generate SEO</button>
            </div>
            <p class="text-xs text-ink-muted">Used for shop listings and linked tool product pages. Leave blank to auto-fill on save.</p>
            <div><label class="ui-label">SEO Title</label><input class="ui-input" name="seo_title" id="tool-seo-title" value="{{ old('seo_title', $tool->seo_title) }}" placeholder="Semrush Group Buy India — from ₹149/month"></div>
            <div><label class="ui-label">SEO Description</label><textarea class="ui-input" name="seo_description" id="tool-seo-description" rows="2" placeholder="Best Semrush group buy in India...">{{ old('seo_description', $tool->seo_description) }}</textarea></div>
            <div><label class="ui-label">SEO Keywords</label><input class="ui-input" name="seo_keywords" id="tool-seo-keywords" value="{{ old('seo_keywords', $tool->seo_keywords) }}" placeholder="semrush group buy, buy semrush, semrush cheap"></div>
            <div class="rounded-lg border border-line bg-canvas p-3 text-xs text-ink-secondary" id="tool-seo-preview">
                <p class="font-medium text-ink">Search preview</p>
                <p class="mt-2 text-accent" id="preview-tool-title">{{ $tool->seo_title ?: 'SEO title preview' }}</p>
                <p class="text-success" id="preview-tool-url">{{ url('/tools/example-group-buy') }}</p>
                <p class="mt-1" id="preview-tool-desc">{{ $tool->seo_description ?: 'Meta description preview will appear here.' }}</p>
            </div>
        </div>

        <div class="dash-card max-w-2xl space-y-4">
            <h2 class="font-semibold text-ink">Shop Display</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="ui-label">Category</label>
                    <select class="ui-input" name="category">
                        @foreach (\App\Models\Tool::categories() as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $tool->category ?? 'seo') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="ui-label">Shop badge</label><input class="ui-input" name="shop_badge" value="{{ old('shop_badge', $tool->shop_badge) }}" placeholder="Popular"></div>
            </div>
            <div>
                <label class="ui-label">Grants access to tool slug (single)</label>
                <input class="ui-input font-mono" name="grants_tool_slug" value="{{ old('grants_tool_slug', $tool->grants_tool_slug) }}" placeholder="ahrefs">
                <p class="mt-1 text-xs text-ink-muted">For tiers: Ahrefs Plan 1–4 → grants <code>ahrefs</code>. Leave empty for normal tools.</p>
            </div>
            <div>
                <label class="ui-label">Package unlocks these tools (one slug per line)</label>
                <textarea class="ui-input min-h-[100px] font-mono text-sm" name="grants_tool_slugs_text" placeholder="ubersuggest&#10;similarweb&#10;spyfu&#10;kwfinder">{{ old('grants_tool_slugs_text', implode("\n", $tool->grants_tool_slugs ?? [])) }}</textarea>
                <p class="mt-1 text-xs text-ink-muted">
                    For <strong>Bonus Tools</strong> package: list each child tool slug. Plan only needs this package checked;
                    My Tools shows each child as its own card (with its own access page + multiple buttons).
                    This package tool itself is hidden from My Tools.
                </p>
            </div>
            <div><label class="ui-label">Shop features (one per line)</label><textarea class="ui-input min-h-[80px]" name="shop_features_text">{{ old('shop_features_text', implode("\n", $tool->shop_features ?? [])) }}</textarea></div>
        </div>

        <div class="dash-card max-w-2xl space-y-4">
            <h2 class="font-semibold text-ink">Access Type</h2>
            <select class="ui-input" name="access_type" id="access-type">
                <option value="cloud" @selected(old('access_type', $tool->access_type ?? 'cloud') === 'cloud')>Cloud — One-click proxy access (Semrush, Ahrefs)</option>
                <option value="extension" @selected(old('access_type', $tool->access_type) === 'extension')>Extension — Chrome extension required (Canva, UberSuggest)</option>
                <option value="whatsapp" @selected(old('access_type', $tool->access_type) === 'whatsapp')>WhatsApp — Manual activation (Ahrefs Bar)</option>
                <option value="credentials" @selected(old('access_type', $tool->access_type) === 'credentials')>Credentials — Username & password</option>
            </select>

            <div id="fields-whatsapp" class="space-y-3 hidden">
                <div><label class="ui-label">WhatsApp number</label><input class="ui-input" name="whatsapp_number" value="{{ old('whatsapp_number', $tool->whatsapp_number) }}" placeholder="918510848196"></div>
                <div><label class="ui-label">Message</label><input class="ui-input" name="whatsapp_message" value="{{ old('whatsapp_message', $tool->whatsapp_message) }}" placeholder="Contact us on WhatsApp for bar activation"></div>
            </div>

            <div id="fields-extension" class="space-y-3 hidden">
                <div><label class="ui-label">Custom extension download URL (optional)</label><input class="ui-input" name="extension_download_url" value="{{ old('extension_download_url', $tool->extension_download_url) }}"><p class="mt-1 text-xs text-ink-muted">Leave blank to use global extension settings</p></div>
            </div>

            <div id="fields-credentials" class="space-y-3 hidden">
                <div><label class="ui-label">Default official website</label><input class="ui-input" name="official_url" value="{{ old('official_url', $tool->official_url) }}" placeholder="https://tool-site.com"></div>
                <p class="text-xs text-ink-muted">Add one or more login credentials below.</p>
                @php $creds = old('credentials', $tool->exists ? $tool->credentials->toArray() : [['label'=>'','username'=>'','password'=>'','official_url'=>'']]); @endphp
                @foreach ($creds as $i => $cred)
                    <div class="rounded-lg border border-line p-3 space-y-2">
                        <input class="ui-input" name="credentials[{{ $i }}][label]" value="{{ $cred['label'] ?? '' }}" placeholder="Account label (e.g. Server 1)">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <input class="ui-input" name="credentials[{{ $i }}][username]" value="{{ $cred['username'] ?? '' }}" placeholder="Username">
                            <input class="ui-input" name="credentials[{{ $i }}][password]" value="{{ $cred['password'] ?? '' }}" placeholder="Password">
                        </div>
                        <input class="ui-input" name="credentials[{{ $i }}][official_url]" value="{{ $cred['official_url'] ?? '' }}" placeholder="Official URL (optional)">
                    </div>
                @endforeach
                <div class="rounded-lg border border-dashed border-line p-3 space-y-2">
                    <input class="ui-input" name="credentials[{{ count($creds) }}][label]" placeholder="New account label">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <input class="ui-input" name="credentials[{{ count($creds) }}][username]" placeholder="Username">
                        <input class="ui-input" name="credentials[{{ count($creds) }}][password]" placeholder="Password">
                    </div>
                </div>
            </div>

            <p class="text-xs text-ink-muted">After saving, create a <a href="{{ route('admin.tool-groups.index') }}" class="text-accent">Tool Group</a> and <a href="{{ route('admin.tool-servers.index') }}" class="text-accent">Access Servers</a> for cloud/extension buttons.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button class="ui-btn-primary">Save Tool</button>
            @if ($tool->exists)
                <form method="POST" action="{{ route('admin.tools.destroy', $tool) }}" onsubmit="return confirm('Delete tool {{ $tool->name }}? This also removes its access group and servers.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ui-btn-ghost text-danger">Delete tool</button>
                </form>
            @endif
        </div>
    </form>

    <script>
        const typeSelect = document.getElementById('access-type');
        const panels = { whatsapp: 'fields-whatsapp', extension: 'fields-extension', credentials: 'fields-credentials' };
        function toggleAccessFields() {
            Object.values(panels).forEach(id => document.getElementById(id)?.classList.add('hidden'));
            const panel = panels[typeSelect.value];
            if (panel) document.getElementById(panel)?.classList.remove('hidden');
        }
        typeSelect.addEventListener('change', toggleAccessFields);
        toggleAccessFields();

        const seoFields = ['tool-seo-title', 'tool-seo-description', 'tool-seo-keywords'];
        function updateToolSeoPreview() {
            document.getElementById('preview-tool-title').textContent = document.getElementById('tool-seo-title').value || 'SEO title preview';
            document.getElementById('preview-tool-desc').textContent = document.getElementById('tool-seo-description').value || 'Meta description preview will appear here.';
        }
        seoFields.forEach(id => document.getElementById(id)?.addEventListener('input', updateToolSeoPreview));

        document.getElementById('auto-tool-seo')?.addEventListener('click', async () => {
            const params = new URLSearchParams({
                name: document.querySelector('[name=name]')?.value || '',
                slug: document.querySelector('[name=slug]')?.value || '',
                description: document.querySelector('[name=description]')?.value || '',
                price_inr: document.querySelector('[name=price_inr]')?.value || '0',
                price_usd: document.querySelector('[name=price_usd]')?.value || '0',
                logo_url: document.getElementById('tool-logo-url')?.value || '',
                thumbnail_url: document.getElementById('tool-thumbnail-url')?.value || '',
            });
            @if ($tool->exists)
            params.set('tool_id', '{{ $tool->id }}');
            @endif
            const res = await fetch(`{{ route('admin.tools.seo-suggestions') }}?${params}`);
            const data = await res.json();
            document.getElementById('tool-seo-title').value = data.seo_title || '';
            document.getElementById('tool-seo-description').value = data.seo_description || '';
            document.getElementById('tool-seo-keywords').value = data.seo_keywords || '';
            if (data.thumbnail_url && !document.getElementById('tool-thumbnail-url').value) {
                document.getElementById('tool-thumbnail-url').value = data.thumbnail_url;
            }
            updateToolSeoPreview();
        });
        updateToolSeoPreview();
    </script>
@endsection
