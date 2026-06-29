<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HomepageService;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public function edit(HomepageService $homepage)
    {
        return view('admin.homepage.edit', [
            'homepage' => $homepage->config(),
        ]);
    }

    public function update(Request $request, HomepageService $homepage)
    {
        $request->validate([
            'seo_title' => ['required', 'string', 'max:200'],
            'seo_description' => ['required', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'seo_og_image' => ['nullable', 'url', 'max:500'],
            'hero_heading_line1' => ['required', 'string', 'max:120'],
        ]);

        $homepage->save($homepage->buildFromRequest($request->all()));

        return redirect()
            ->route('admin.homepage.edit', ['tab' => $request->input('tab', 'seo')])
            ->with('success', 'Homepage content saved.');
    }

    public function reset(HomepageService $homepage)
    {
        $homepage->reset();

        return redirect()->route('admin.homepage.edit')
            ->with('success', 'Homepage reset to default content.');
    }
}
