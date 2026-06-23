<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class ExtensionSettingsController extends Controller
{
    public function edit()
    {
        return view('admin.extension-settings.edit', [
            'config' => SiteSetting::extensionConfig(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'download_url' => ['nullable', 'url', 'max:2048'],
            'version' => ['nullable', 'string', 'max:30'],
            'install_guide' => ['nullable', 'string'],
            'api_url' => ['nullable', 'url', 'max:500'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'client_slug' => ['nullable', 'string', 'max:60'],
            'visibility' => ['required', 'in:any_subscription,purchased_extension_tools'],
            'indicator_id' => ['nullable', 'string', 'max:80'],
        ]);

        SiteSetting::set('extension_download_url', $data['download_url'] ?? '');
        SiteSetting::set('extension_version', $data['version'] ?? '');
        SiteSetting::set('extension_install_guide', $data['install_guide'] ?? '');
        SiteSetting::set('extension_api_url', $data['api_url'] ?? '');
        SiteSetting::set('extension_secret_key', $data['secret_key'] ?? '');
        SiteSetting::set('extension_client_slug', $data['client_slug'] ?? 'semrushtoolz');
        SiteSetting::set('extension_visibility', $data['visibility']);
        SiteSetting::set('extension_indicator_id', $data['indicator_id'] ?? 'my-extension-installed-indicator');

        return back()->with('success', 'Extension settings saved.');
    }
}
