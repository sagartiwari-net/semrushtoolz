@extends('layouts.admin')

@section('title', $preset->exists ? 'Edit Email Preset' : 'New Email Preset')

@section('content')
    <div class="mb-5">
        <a href="{{ route('admin.email-presets.index') }}" class="text-sm text-ink-muted hover:text-accent">&larr; Back to Presets</a>
        <h1 class="dash-page-title mt-2">{{ $preset->exists ? 'Edit Preset' : 'New Preset' }}</h1>
        <p class="text-sm text-ink-secondary">Use <code>@{{variable}}</code> in subject and body. Save syncs the template to Mail Panel.</p>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
            <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <form method="POST" action="{{ $preset->exists ? route('admin.email-presets.update', $preset) : route('admin.email-presets.store') }}" class="dash-card space-y-6" id="preset-form">
            @csrf
            @if ($preset->exists)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="ui-label">Preset name *</label>
                    <input class="ui-input" name="name" value="{{ old('name', $preset->name) }}" required>
                </div>
                <div>
                    <label class="ui-label">Category *</label>
                    <select class="ui-input" name="category" required>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $preset->category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="ui-label">Internal key *</label>
                    <input class="ui-input font-mono text-sm" name="key" id="preset-key" value="{{ old('key', $preset->key) }}" required @readonly($preset->exists && $preset->is_system) placeholder="my_custom_email">
                    <p class="mt-1 text-xs text-ink-muted">Used in code — lowercase, underscores only.</p>
                </div>
                <div>
                    <label class="ui-label">Mail Panel slug *</label>
                    <input class="ui-input font-mono text-sm" name="slug" id="preset-slug" value="{{ old('slug', $preset->slug) }}" required @readonly($preset->exists && $preset->is_system) placeholder="semrushtoolz-my-custom-email">
                    <p class="mt-1 text-xs text-ink-muted">Unique template slug on Mail Panel.</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="ui-label">Type *</label>
                    <select class="ui-input" name="type">
                        <option value="transactional" @selected(old('type', $preset->type) === 'transactional')>Transactional</option>
                        <option value="promo" @selected(old('type', $preset->type) === 'promo')>Promo</option>
                    </select>
                </div>
                <div>
                    <label class="ui-label">Sort order</label>
                    <input class="ui-input" type="number" name="sort_order" value="{{ old('sort_order', $preset->sort_order ?? 0) }}" min="0">
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_enabled" value="1" class="rounded" @checked(old('is_enabled', $preset->is_enabled ?? true))>
                        <span>Enabled</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="ui-label">Email subject *</label>
                <input class="ui-input preset-preview-field" name="subject" id="preset-subject" value="{{ old('subject', $preset->subject) }}" required placeholder="Your code: @{{otp}}">
            </div>

            <div>
                <label class="ui-label">HTML body *</label>
                <textarea class="ui-input font-mono text-xs preset-preview-field" name="html_body" id="preset-html" rows="12" required>{{ old('html_body', $preset->html_body) }}</textarea>
            </div>

            <div>
                <label class="ui-label">Plain text body (optional)</label>
                <textarea class="ui-input font-mono text-xs preset-preview-field" name="text_body" id="preset-text" rows="6">{{ old('text_body', $preset->text_body) }}</textarea>
            </div>

            <div>
                <label class="ui-label">Description (admin note)</label>
                <input class="ui-input" name="description" value="{{ old('description', $preset->description) }}" placeholder="When this email is sent">
            </div>

            <div>
                <label class="ui-label">Available variables</label>
                <input class="ui-input font-mono text-sm" name="variables_help" value="{{ old('variables_help', $preset->variables_help) }}" placeholder="@{{name}}, @{{otp}}, @{{plan_name}}">
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="ui-btn ui-btn-primary">Save Preset</button>
                <button type="button" class="ui-btn ui-btn-outline" id="preview-btn">Refresh preview</button>
                <a href="{{ route('admin.email-presets.index') }}" class="ui-btn ui-btn-outline">Cancel</a>
            </div>
        </form>

        <div class="dash-card space-y-4 xl:sticky xl:top-4 xl:self-start">
            <div class="flex items-center justify-between gap-3">
                <h3 class="dash-card-title !mb-0">Live preview</h3>
                <span class="text-xs text-ink-muted">Sample data</span>
            </div>
            <p class="text-xs text-ink-muted">Shows how the email will look with example values. Click <strong>Refresh preview</strong> after editing.</p>
            <div class="rounded-lg border border-line bg-white p-3">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">Subject</p>
                <p id="preview-subject" class="text-sm font-medium text-ink">—</p>
            </div>
            <div class="rounded-lg border border-line bg-white overflow-hidden">
                <p class="border-b border-line px-3 py-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">HTML body</p>
                <iframe id="preview-html-frame" title="Email HTML preview" class="w-full border-0 bg-white" style="height:520px;display:block;"></iframe>
            </div>
            <div class="rounded-lg border border-line bg-surface-2 p-3">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">Plain text</p>
                <pre id="preview-text" class="whitespace-pre-wrap text-xs text-ink-secondary">—</pre>
            </div>
        </div>
    </div>

    <script>
        const previewUrl = @json(route('admin.email-presets.preview'));
        const presetKey = @json(old('key', $preset->key));
        const csrf = @json(csrf_token());

        async function refreshPreview() {
            const subject = document.getElementById('preset-subject')?.value || '';
            const htmlBody = document.getElementById('preset-html')?.value || '';
            const textBody = document.getElementById('preset-text')?.value || '';
            const key = document.getElementById('preset-key')?.value || presetKey;

            const btn = document.getElementById('preview-btn');
            if (btn) btn.disabled = true;

            try {
                const response = await fetch(previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        subject,
                        html_body: htmlBody,
                        text_body: textBody,
                        preset_key: key,
                    }),
                });

                if (!response.ok) throw new Error('Preview failed');

                const data = await response.json();
                document.getElementById('preview-subject').textContent = data.subject || '—';
                const frame = document.getElementById('preview-html-frame');
                if (frame) {
                    frame.srcdoc = data.html_body || '<p style="font-family:sans-serif;padding:16px;color:#666;">Empty</p>';
                }
                document.getElementById('preview-text').textContent = data.text_body || '—';
            } catch (e) {
                document.getElementById('preview-subject').textContent = 'Preview error';
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        document.getElementById('preview-btn')?.addEventListener('click', refreshPreview);
        refreshPreview();

        const keyInput = document.getElementById('preset-key');
        const slugInput = document.getElementById('preset-slug');
        keyInput?.addEventListener('input', () => {
            if (slugInput && !slugInput.dataset.touched) {
                slugInput.value = 'semrushtoolz-' + keyInput.value.replace(/_/g, '-');
            }
        });
        slugInput?.addEventListener('input', () => { slugInput.dataset.touched = '1'; });
    </script>
@endsection
