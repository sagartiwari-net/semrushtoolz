<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreToolRequest;
use App\Http\Requests\Admin\UpdateToolRequest;
use App\Models\Tool;
use App\Models\ToolCredential;
use App\Services\AdminUserQueryService;
use App\Services\ToolSeoService;
use App\Services\TransactionalEmailService;
use Illuminate\Http\Request;

class ToolCatalogController extends Controller
{
    public function __construct(
        protected TransactionalEmailService $transactionalMail,
        protected AdminUserQueryService $userQueries,
    ) {}
    public function index()
    {
        $tools = Tool::with('accessGroup')->orderBy('sort_order')->get();
        $subscriberCounts = $this->userQueries->toolSubscriberCounts();

        return view('admin.tools.index', compact('tools', 'subscriberCounts'));
    }

    public function create()
    {
        return view('admin.tools.form', [
            'tool' => new Tool(['is_active' => true, 'access_type' => Tool::ACCESS_CLOUD]),
        ]);
    }

    public function store(StoreToolRequest $request)
    {
        $data = $this->toolData($request);

        if (($data['sort_order'] ?? 0) === 0) {
            $data['sort_order'] = (int) Tool::min('sort_order') - 1;
        }

        $tool = Tool::create($data);
        $this->syncCredentials($tool, $request);

        if ($tool->is_active) {
            $this->transactionalMail->announceNewTool($tool);
        }

        return redirect()->route('admin.tools.index')->with('success', 'Tool created.');
    }

    public function edit(Tool $tool)
    {
        $tool->load('credentials');

        return view('admin.tools.form', compact('tool'));
    }

    public function update(UpdateToolRequest $request, Tool $tool)
    {
        $wasActive = (bool) $tool->is_active;
        $tool->update($this->toolData($request));
        $this->syncCredentials($tool, $request);

        if (! $wasActive && $tool->fresh()->is_active) {
            $this->transactionalMail->announceNewTool($tool->fresh());
        }

        return redirect()->route('admin.tools.index')->with('success', 'Tool updated.');
    }

    public function toggle(Tool $tool)
    {
        $wasActive = (bool) $tool->is_active;
        $tool->update(['is_active' => ! $tool->is_active]);

        if (! $wasActive && $tool->fresh()->is_active) {
            $this->transactionalMail->announceNewTool($tool->fresh());
        }

        return back()->with('success', 'Tool status updated.');
    }

    public function destroy(Tool $tool)
    {
        $slug = $tool->slug;
        $name = $tool->name;

        $tool->load('accessGroup.servers');
        $tool->accessGroup?->servers()->delete();
        $tool->accessGroup?->delete();
        $tool->credentials()->delete();
        $tool->plans()->detach();
        $tool->articles()->update(['tool_id' => null]);
        $tool->delete();

        Tool::query()
            ->whereNotNull('grants_tool_slugs')
            ->get()
            ->each(function (Tool $package) use ($slug) {
                $slugs = collect($package->grants_tool_slugs ?? [])
                    ->reject(fn ($s) => $s === $slug)
                    ->values()
                    ->all();

                if ($slugs !== ($package->grants_tool_slugs ?? [])) {
                    $package->update(['grants_tool_slugs' => $slugs]);
                }
            });

        Tool::query()
            ->where('grants_tool_slug', $slug)
            ->update(['grants_tool_slug' => null]);

        return redirect()->route('admin.tools.index')
            ->with('success', "Tool \"{$name}\" deleted.");
    }

    public function seoSuggestions(Request $request)
    {
        $tool = $request->filled('tool_id')
            ? Tool::findOrFail($request->integer('tool_id'))
            : new Tool($request->only(['name', 'description', 'slug', 'price_inr', 'price_usd', 'thumbnail_url', 'logo_url']));

        return response()->json(ToolSeoService::suggestionsForTool($tool));
    }

    public function preview(Tool $tool)
    {
        $article = $tool->articles()->latest()->first();

        if ($article) {
            return redirect()->route('admin.articles.preview', $article);
        }

        $tool->loadMissing('credentials');

        return view('admin.tools.preview', [
            'tool' => $tool,
            'shop' => $tool->toShopArray(),
            'seo' => $tool->resolvedSeo(),
        ]);
    }

    protected function toolData(StoreToolRequest|UpdateToolRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['show_in_shop'] = $request->boolean('show_in_shop');
        $data['is_extension'] = $data['access_type'] === Tool::ACCESS_EXTENSION;

        if (isset($data['shop_features_text'])) {
            $data['shop_features'] = collect(explode("\n", $data['shop_features_text']))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->values()
                ->all();
            unset($data['shop_features_text']);
        }

        if (array_key_exists('grants_tool_slugs_text', $data)) {
            $data['grants_tool_slugs'] = collect(preg_split('/[\r\n,]+/', (string) $data['grants_tool_slugs_text']))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->values()
                ->all();
            unset($data['grants_tool_slugs_text']);
        }

        if (! empty($data['prices_json'])) {
            $data['prices'] = json_decode($data['prices_json'], true) ?: null;
        }
        unset($data['prices_json'], $data['credentials']);

        if (empty($data['thumbnail_url']) && ! empty($data['logo_url'])) {
            $data['thumbnail_url'] = $data['logo_url'];
        }

        $preview = new Tool($data);
        if (empty($data['seo_title']) || empty($data['seo_description'])) {
            $suggestions = ToolSeoService::suggestionsForTool($preview);
            $data['seo_title'] = $data['seo_title'] ?: $suggestions['seo_title'];
            $data['seo_description'] = $data['seo_description'] ?: $suggestions['seo_description'];
            $data['seo_keywords'] = $data['seo_keywords'] ?: $suggestions['seo_keywords'];
        }

        return $data;
    }

    protected function syncCredentials(Tool $tool, StoreToolRequest|UpdateToolRequest $request): void
    {
        if ($tool->access_type !== Tool::ACCESS_CREDENTIALS) {
            $tool->credentials()->delete();

            return;
        }

        $tool->credentials()->delete();
        $order = 0;

        foreach ($request->input('credentials', []) as $cred) {
            $username = trim($cred['username'] ?? '');
            $password = trim($cred['password'] ?? '');
            if ($username === '' && $password === '') {
                continue;
            }
            ToolCredential::create([
                'tool_id' => $tool->id,
                'label' => trim($cred['label'] ?? '') ?: null,
                'username' => $username,
                'password' => $password,
                'official_url' => trim($cred['official_url'] ?? '') ?: null,
                'sort_order' => $order++,
                'is_active' => true,
            ]);
        }
    }
}
