<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            if ($request->user()) {
                return redirect()->route('dashboard.index')
                    ->with('error', 'You do not have admin access.');
            }

            return redirect()->route('admin.login')
                ->with('error', 'Please login as admin.');
        }

        return $next($request);
    }
}
