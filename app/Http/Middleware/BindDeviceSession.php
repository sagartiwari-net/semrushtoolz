<?php

namespace App\Http\Middleware;

use App\Services\SecurityMonitorService;
use App\Services\UserSessionService;
use App\Support\DeviceFingerprint;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BindDeviceSession
{
    public function __construct(
        protected SecurityMonitorService $security,
        protected UserSessionService $sessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $clientFp = DeviceFingerprint::clientFromRequest($request);

        if (! $request->session()->has(DeviceFingerprint::SESSION_KEY)) {
            if ($clientFp) {
                $this->security->bindDeviceToSession($request);
            }

            $this->sessions->syncSessionMeta($request, $user);

            return $next($request);
        }

        if ($clientFp && ! $this->security->sessionDeviceMatches($request)) {
            $this->security->recordDeviceMismatch($user, $request);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'This session was opened on another device or browser. Please sign in again.',
                ]);
        }

        if ($clientFp) {
            $this->sessions->syncSessionMeta($request, $user);
        }

        return $next($request);
    }
}
