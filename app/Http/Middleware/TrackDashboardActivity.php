<?php

namespace App\Http\Middleware;

use App\Services\SecurityMonitorService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackDashboardActivity
{
    public function __construct(
        protected SecurityMonitorService $security,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $request->routeIs('dashboard.orders.status')) {
            $this->security->logActivityThrottled($user, $request, 'page_view');
        }

        return $next($request);
    }
}
