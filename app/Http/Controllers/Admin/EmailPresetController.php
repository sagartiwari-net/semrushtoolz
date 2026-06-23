<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailPreset;
use App\Services\EmailPresetRenderer;
use App\Services\MailPanel\MailPanelTemplateSyncService;
use App\Support\MailPanelSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailPresetController extends Controller
{
    public function index()
    {
        $presets = EmailPreset::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return view('admin.email-presets.index', [
            'presetsByCategory' => $presets,
            'categories' => EmailPreset::categories(),
        ]);
    }

    public function create()
    {
        return view('admin.email-presets.form', $this->formData(new EmailPreset([
            'type' => EmailPreset::TYPE_TRANSACTIONAL,
            'category' => EmailPreset::CATEGORY_OTHER,
            'is_enabled' => true,
        ])));
    }

    public function store(Request $request, MailPanelTemplateSyncService $syncService)
    {
        $preset = $this->save(new EmailPreset, $request);

        if (MailPanelSettings::isConfigured() && $preset->is_enabled) {
            try {
                $syncService->syncPreset($preset);
            } catch (\Throwable $exception) {
                return redirect()->route('admin.email-presets.index')
                    ->with('error', "Preset saved but Mail Panel sync failed: {$exception->getMessage()}");
            }
        }

        return redirect()->route('admin.email-presets.index')
            ->with('success', "Preset \"{$preset->name}\" created.");
    }

    public function edit(EmailPreset $emailPreset)
    {
        return view('admin.email-presets.form', $this->formData($emailPreset));
    }

    public function update(Request $request, EmailPreset $emailPreset, MailPanelTemplateSyncService $syncService)
    {
        $preset = $this->save($emailPreset, $request);

        if (MailPanelSettings::isConfigured() && $preset->is_enabled) {
            try {
                $syncService->syncPreset($preset);
            } catch (\Throwable $exception) {
                return back()->with('error', "Saved locally but Mail Panel sync failed: {$exception->getMessage()}");
            }
        }

        return redirect()->route('admin.email-presets.index')
            ->with('success', "Preset \"{$preset->name}\" updated.");
    }

    public function destroy(EmailPreset $emailPreset)
    {
        if ($emailPreset->is_system) {
            return back()->with('error', 'System presets cannot be deleted. You can disable them instead.');
        }

        $emailPreset->delete();

        return back()->with('success', 'Preset deleted.');
    }

    public function sync(EmailPreset $emailPreset, MailPanelTemplateSyncService $syncService)
    {
        if (! MailPanelSettings::isConfigured()) {
            return back()->with('error', 'Mail Panel is not configured. Open Email Setup first.');
        }

        try {
            $syncService->syncPreset($emailPreset);

            return back()->with('success', "Preset \"{$emailPreset->name}\" synced to Mail Panel.");
        } catch (\Throwable $exception) {
            return back()->with('error', 'Sync failed: '.$exception->getMessage());
        }
    }

    protected function formData(EmailPreset $preset): array
    {
        return [
            'preset' => $preset,
            'categories' => EmailPreset::categories(),
            'sampleData' => app(EmailPresetRenderer::class)->sampleData($preset->key ?: null),
        ];
    }

    public function preview(Request $request, EmailPresetRenderer $renderer)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'preset_key' => ['nullable', 'string', 'max:80'],
        ]);

        $sample = $renderer->sampleData($data['preset_key'] ?? null);
        $preset = new EmailPreset([
            'subject' => $data['subject'],
            'html_body' => $data['html_body'],
            'text_body' => $data['text_body'] ?? null,
        ]);

        $rendered = $renderer->renderPreset($preset, $sample);

        return response()->json([
            'subject' => $rendered['subject'],
            'html_body' => $rendered['html_body'],
            'text_body' => $rendered['text_body'],
        ]);
    }

    protected function save(EmailPreset $preset, Request $request): EmailPreset
    {
        $isNew = ! $preset->exists;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'key' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('email_presets', 'key')->ignore($preset->id),
            ],
            'category' => ['required', 'string', Rule::in(array_keys(EmailPreset::categories()))],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9\-_]*$/', Rule::unique('email_presets', 'slug')->ignore($preset->id)],
            'subject' => ['required', 'string', 'max:255'],
            'html_body' => ['required', 'string'],
            'text_body' => ['nullable', 'string'],
            'type' => ['required', 'string', Rule::in([EmailPreset::TYPE_TRANSACTIONAL, EmailPreset::TYPE_PROMO])],
            'description' => ['nullable', 'string', 'max:500'],
            'variables_help' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        if ($isNew) {
            $data['is_system'] = false;
        } elseif ($preset->is_system) {
            unset($data['key'], $data['slug']);
        }

        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $preset->fill($data)->save();

        return $preset->fresh();
    }
}
