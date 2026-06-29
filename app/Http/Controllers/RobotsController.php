<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __construct(
        protected SitemapService $sitemap,
    ) {}

    public function index(): Response
    {
        return response($this->sitemap->robotsTxt(), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
