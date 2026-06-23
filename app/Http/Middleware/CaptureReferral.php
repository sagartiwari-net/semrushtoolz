<?php

namespace App\Http\Middleware;

use App\Services\ReferralService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferral
{
    public function __construct(
        protected ReferralService $referrals,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->referrals->captureFromRequest($request);

        return $next($request);
    }
}
