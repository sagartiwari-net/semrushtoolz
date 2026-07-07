@extends('layouts.dashboard')

@section('title', 'Access Error')

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="dash-card text-center">
            <div class="text-5xl">⚠️</div>
            <h1 class="mt-4 text-xl font-bold text-danger">Authentication Handshake Failed</h1>
            <p class="mt-3 text-sm text-ink-secondary">
                Tool: <strong>{{ $tool }}</strong>
            </p>
            <div class="mt-4 rounded-xl border border-danger/20 bg-danger/5 px-4 py-3 text-left text-xs text-danger">
                {{ $message }}
            </div>
            @if (str_contains($message, '502') || str_contains($message, 'offline'))
                <p class="mt-4 text-sm text-ink-muted">This server may not be deployed yet. Try another access button on the same tool page.</p>
            @elseif (str_contains($message, 'Invalid signature'))
                <p class="mt-4 text-sm text-ink-muted">Try <strong>Access 1</strong> on the same tool, or another numbered server if available.</p>
            @endif
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('dashboard.tools') }}" class="ui-btn-outline">Back to My Tools</a>
                @if (!empty($hubGroup))
                    <a href="{{ route('dashboard.tools.hub', $hubGroup) }}" class="ui-btn-primary">Try Another Server</a>
                @else
                    @php $endpoint = config("tool_endpoints.endpoints.{$tool}"); @endphp
                    @if ($endpoint)
                        <a href="{{ route('dashboard.tools.hub', $endpoint['group'] ?? 'semrush') }}" class="ui-btn-primary">Try Another Server</a>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
