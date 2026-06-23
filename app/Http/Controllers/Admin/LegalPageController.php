<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LegalPageController extends Controller
{
    public function index()
    {
        return view('admin.legal-pages.index', [
            'pages' => LegalPage::orderBy('sort_order')->orderBy('title')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.legal-pages.form', [
            'page' => new LegalPage(['is_published' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $page = $this->save(new LegalPage, $request);

        return redirect()->route('admin.legal-pages.index')
            ->with('success', "Page \"{$page->title}\" created.");
    }

    public function edit(LegalPage $legalPage)
    {
        return view('admin.legal-pages.form', ['page' => $legalPage]);
    }

    public function update(Request $request, LegalPage $legalPage)
    {
        $this->save($legalPage, $request);

        return redirect()->route('admin.legal-pages.index')
            ->with('success', 'Page updated.');
    }

    public function destroy(LegalPage $legalPage)
    {
        if (in_array($legalPage->slug, ['terms', 'privacy', 'refund'], true)) {
            return back()->with('error', 'Core legal pages cannot be deleted. You can unpublish or edit them instead.');
        }

        $legalPage->delete();

        return back()->with('success', 'Page deleted.');
    }

    protected function save(LegalPage $page, Request $request): LegalPage
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('legal_pages', 'slug')->ignore($page->id),
            ],
            'html_body' => ['required', 'string'],
            'seo_title' => ['required', 'string', 'max:200'],
            'seo_description' => ['required', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $page->fill($data)->save();

        return $page->fresh();
    }

    public function preview(LegalPage $legalPage)
    {
        return view('pages.legal.show', [
            'page' => $legalPage,
            'seo' => $legalPage->seo(),
            'siteName' => \App\Models\SiteSetting::generalConfig()['site_name'],
            'isPreview' => true,
            'backUrl' => route('admin.legal-pages.edit', $legalPage),
        ]);
    }
}
