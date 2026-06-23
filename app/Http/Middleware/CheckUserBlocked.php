<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status === 'blocked') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account has been blocked.'], 403);
            }

            return response()->view('dashboard.blocked', [
                'reason' => $user->block_reason ?? 'Account sharing or policy violation detected.',
                'blocked_at' => $user->blocked_at,
            ], 403);
        }

        return $next($request);
    }
}
