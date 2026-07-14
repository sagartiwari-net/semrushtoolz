<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReseller
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActiveReseller()) {
            if ($user?->isAdmin()) {
                return redirect()->route('admin.index')
                    ->with('error', 'Reseller panel is for reseller accounts only.');
            }

            if ($user) {
                return redirect()->route('dashboard.index')
                    ->with('error', 'You do not have reseller access.');
            }

            return redirect()->guest(route('login'))
                ->with('error', 'Please sign in to continue.');
        }

        return $next($request);
    }
}
