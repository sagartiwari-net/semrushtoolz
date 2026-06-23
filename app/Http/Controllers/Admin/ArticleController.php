<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Plan;
use App\Models\Tool;
use App\Services\ArticlePageService;
use App\Services\ToolSeoService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(
        protected ArticlePageService $pages,
    ) {}

    public function index()
    {
        $articles = Article::with('tool')->latest()->get();

        return view('admin.articles.index', compact('articles'));
    }

    public function create()
    {
        $article = new Article(['is_published' => false, 'show_pricing' => true]);
        if ($toolId = request()->integer('tool_id')) {
            $article->tool_id = $toolId;
        }

        return view('admin.articles.form', [
            'tools' => Tool::orderBy('sort_order')->get(),
            'plans' => Plan::orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreArticleRequest $request)
    {
        $article = $this->saveArticle(new Article, $request);

        return redirect()->route('admin.articles.index')
            ->with('success', 'Article created. URL: /'.$article->url_path);
    }

    public function edit(Article $article)
    {
        $article->load('tool');

        return view('admin.articles.form', [
            'article' => $article,
            'tools' => Tool::orderBy('sort_order')->get(),
            'plans' => Plan::orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateArticleRequest $request, Article $article)
    {
        $article = $this->saveArticle($article, $request);

        return redirect()->route('admin.articles.index')
            ->with('success', 'Article updated.');
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return back()->with('success', 'Article deleted.');
    }

    public function toggle(Article $article)
    {
        $article->update([
            'is_published' => ! $article->is_published,
            'published_at' => ! $article->is_published ? now() : $article->published_at,
        ]);

        return back()->with('success', 'Publish status updated.');
    }

    public function seoSuggestions(Request $request)
    {
        $request->validate([
            'tool_id' => ['required', 'exists:tools,id'],
            'title' => ['nullable', 'string', 'max:200'],
        ]);

        $tool = Tool::findOrFail($request->integer('tool_id'));

        return response()->json(
            ToolSeoService::suggestionsForToolPage($tool, $request->input('title'))
        );
    }

    public function preview(Article $article)
    {
        return $this->pages->render($article, isPreview: true, backUrl: route('admin.articles.edit', $article));
    }

    protected function saveArticle(Article $article, StoreArticleRequest|UpdateArticleRequest $request): Article
    {
        $data = $request->validated();
        $data['show_pricing'] = $request->boolean('show_pricing');
        $data['is_published'] = $request->boolean('is_published');

        if ($data['is_published'] && ! $article->published_at) {
            $data['published_at'] = now();
        }

        $data['faqs'] = $this->normalizeFaqs($request->input('faqs', []));
        $data['features'] = $this->normalizePairs($request->input('features', []));
        $data['steps'] = $this->normalizeSteps($request->input('steps', []));
        $data['highlights'] = array_values(array_filter($request->input('highlights', [])));
        $data['plan_slugs'] = $request->input('plan_slugs', []);
        $data['content_blocks'] = $this->normalizeContentBlocks($request->input('content_blocks', []));

        $article->fill($data)->save();

        return $article;
    }

    protected function normalizeFaqs(array $faqs): array
    {
        $out = [];
        foreach ($faqs['q'] ?? [] as $i => $q) {
            $a = $faqs['a'][$i] ?? '';
            if (trim($q) && trim($a)) {
                $out[] = ['q' => trim($q), 'a' => trim($a)];
            }
        }

        return $out;
    }

    protected function normalizePairs(array $items): array
    {
        $out = [];
        foreach ($items['title'] ?? [] as $i => $title) {
            $desc = $items['desc'][$i] ?? '';
            if (trim($title)) {
                $out[] = [trim($title), trim($desc)];
            }
        }

        return $out;
    }

    protected function normalizeSteps(array $items): array
    {
        $out = [];
        foreach ($items['num'] ?? [] as $i => $num) {
            $title = $items['title'][$i] ?? '';
            $desc = $items['desc'][$i] ?? '';
            if (trim($title)) {
                $out[] = [trim($num), trim($title), trim($desc)];
            }
        }

        return $out;
    }

    protected function normalizeContentBlocks(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            $heading = trim($block['heading'] ?? '');
            if ($heading === '') {
                continue;
            }
            $out[] = [
                'id' => trim($block['id'] ?? '') ?: \Illuminate\Support\Str::slug($heading),
                'heading' => $heading,
                'html' => $block['html'] ?? '',
            ];
        }

        return $out;
    }
}
