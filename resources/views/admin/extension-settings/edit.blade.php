@extends('layouts.admin')

@section('title', 'Extension Settings')

@section('content')
    <div class="mb-5">
        <h1 class="dash-page-title">Chrome Extension Settings</h1>
        <p class="text-sm text-ink-secondary">Manage extension download link, API connection, and who can download it.</p>
    </div>

    <form method="POST" action="{{ route('admin.extension-settings.update') }}" class="dash-card max-w-2xl space-y-4">
        @csrf @method('PUT')

        <div><label class="ui-label">Download URL</label><input class="ui-input" name="download_url" value="{{ old('download_url', $config['download_url']) }}" placeholder="https://..."></div>
        <div><label class="ui-label">Version</label><input class="ui-input" name="version" value="{{ old('version', $config['version']) }}" placeholder="2.4.1"></div>
        <div><label class="ui-label">Install guide (HTML)</label><textarea class="ui-input min-h-[100px]" name="install_guide">{{ old('install_guide', $config['install_guide']) }}</textarea></div>

        <hr class="border-line">

        <p class="text-sm font-semibold text-ink">Extension API (extaccess.php equivalent)</p>
        <div><label class="ui-label">API URL</label><input class="ui-input" name="api_url" value="{{ old('api_url', $config['api_url']) }}" placeholder="https://api.example.com/api"></div>
        <div><label class="ui-label">Secret key</label><input class="ui-input font-mono" name="secret_key" value="{{ old('secret_key', $config['secret_key']) }}"></div>
        <div><label class="ui-label">Client slug</label><input class="ui-input" name="client_slug" value="{{ old('client_slug', $config['client_slug']) }}"></div>
        <div><label class="ui-label">Extension indicator element ID</label><input class="ui-input font-mono" name="indicator_id" value="{{ old('indicator_id', $config['indicator_id']) }}"><p class="mt-1 text-xs text-ink-muted">Chrome extension injects this hidden element when installed.</p></div>

        <div>
            <label class="ui-label">Who can download extension?</label>
            <select class="ui-input" name="visibility">
                <option value="any_subscription" @selected(old('visibility', $config['visibility']) === 'any_subscription')>Any active subscription</option>
                <option value="purchased_extension_tools" @selected(old('visibility', $config['visibility']) === 'purchased_extension_tools')>Only if plan includes extension tools</option>
            </select>
        </div>

        <button class="ui-btn-primary">Save Settings</button>
    </form>
@endsection
