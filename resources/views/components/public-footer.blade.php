<footer class="border-t border-line bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-8 md:grid-cols-4">
            <div class="md:col-span-2">
                <x-logo />
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-secondary">
                    Premium SEO tools at a fraction of the cost. One-click cloud access to Semrush & Ahrefs.
                </p>
            </div>
            <div>
                <h4 class="mb-3 text-sm font-semibold text-ink">Product</h4>
                <ul class="space-y-2 text-sm text-ink-secondary">
                    @foreach ($footerArticles ?? [] as $article)
                        <li><a href="{{ url('/'.$article->url_path) }}" class="hover:text-accent">{{ $article->breadcrumb_label ?? $article->title }}</a></li>
                    @endforeach
                    <li><a href="{{ route('login') }}" class="hover:text-accent">Semrush Login</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-accent">Buy Semrush &amp; Ahrefs</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-3 text-sm font-semibold text-ink">Legal</h4>
                <ul class="space-y-2 text-sm text-ink-secondary">
                    @forelse ($footerLegalPages ?? [] as $page)
                        <li><a href="{{ $page->publicUrl() }}" class="hover:text-accent">{{ $page->title }}</a></li>
                    @empty
                        <li><a href="{{ route('legal.terms') }}" class="hover:text-accent">Terms of Service</a></li>
                        <li><a href="{{ route('legal.privacy') }}" class="hover:text-accent">Privacy Policy</a></li>
                        <li><a href="{{ route('legal.refund') }}" class="hover:text-accent">Refund Policy</a></li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-line pt-6 sm:flex-row">
            <p class="text-xs text-ink-muted">&copy; {{ date('Y') }} Semrushtoolz.com. All rights reserved.</p>
            <p class="text-xs text-ink-muted">Built for SEO professionals</p>
        </div>
    </div>
</footer>
