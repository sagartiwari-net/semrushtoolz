<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Block resellers from customer dashboard. */
class BlockResellerFromDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isReseller()) {
            if ($user->isActiveReseller()) {
                return redirect()->route('reseller.index');
            }

            auth()->logout();

            return redirect()->route('login')
                ->with('error', 'Your reseller account is inactive. Contact admin.');
        }

        return $next($request);
    }
}
