<?php

namespace App\Http\Middleware;

use App\Services\SecurityMonitorService;
use App\Support\DeviceFingerprint;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BindDeviceSession
{
    public function __construct(
        protected SecurityMonitorService $security,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $request->session()->has(DeviceFingerprint::SESSION_KEY)) {
            $this->security->bindDeviceToSession($request);

            return $next($request);
        }

        if (! $this->security->sessionDeviceMatches($request)) {
            $this->security->logActivity($user, $request, 'device_mismatch');

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'This session was opened on another device or browser. Please sign in again.',
                ]);
        }

        return $next($request);
    }
}
