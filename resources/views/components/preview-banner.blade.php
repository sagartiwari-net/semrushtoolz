@if (!empty($isPreview))
    <div class="sticky top-0 z-50 border-b border-warning/40 bg-warning/15 px-4 py-2.5 text-center text-sm text-ink">
        <strong>Preview mode</strong> — This page is not visible to the public.
        @if (!empty($backUrl))
            <a href="{{ $backUrl }}" class="ml-2 font-medium text-accent hover:underline">Back to edit</a>
        @endif
    </div>
@endif
