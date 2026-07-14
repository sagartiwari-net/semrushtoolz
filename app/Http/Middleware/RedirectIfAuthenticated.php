<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($request->routeIs('admin.login') && $user->isAdmin()) {
                return redirect()->route('admin.index');
            }

            if ($user->isAdmin() && $request->routeIs('login')) {
                return redirect()->route('admin.index');
            }

            if ($user->isReseller()) {
                if ($user->isActiveReseller()) {
                    return redirect()->route('reseller.index');
                }

                Auth::logout();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Your reseller account is inactive. Contact admin.']);
            }

            return redirect()->route('dashboard.index');
        }

        return $next($request);
    }
}
