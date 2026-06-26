<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use App\Services\ReferralSignupBonusService;
use App\Services\TurnstileService;

class AuthPageController extends Controller
{
    public function __construct(
        protected ReferralService $referrals,
        protected ReferralSignupBonusService $referralBonus,
        protected TurnstileService $turnstile,
    ) {}

    public function login()
    {
        return view('auth.login', [
            'seo' => [
                'title' => 'Semrush Login & Ahrefs Login — Group Buy Dashboard',
                'description' => 'Login to Semrushtoolz to access your Semrush group buy and Ahrefs group buy account. Sign in for one-click cloud access to premium SEO tools. Cheap Semrush & Ahrefs plans in India.',
                'keywords' => 'semrush login, ahrefs login, login semrush, sem rush login, semrush sign in, ahrefs sign in, semrushtoolz login, semrush group buy login, ahrefs group buy login, semrush log in, login to semrush',
            ],
            'loginMode' => session('login_mode', old('login_mode', 'password')),
            'otpEmail' => session('otp_login_email', old('email')),
        ]);
    }

    public function register()
    {
        $referralCode = $this->referrals->codeFromRequest(request());
        $referrer = $referralCode ? $this->referrals->resolveReferrer($referralCode) : null;

        return view('auth.register', [
            'seo' => [
                'title' => 'Sign Up — Buy Semrush & Ahrefs Group Buy',
                'description' => 'Create your Semrushtoolz account to buy Semrush and Ahrefs at cheap price. Best group buy SEO tools in India. Plans from ₹149/month.',
                'keywords' => 'buy semrush, buy ahrefs, semrush group buy, ahrefs group buy, group buy semrush, cheap ahrefs account, semrush cheap, register semrushtoolz',
            ],
            'signupBonus' => $this->referralBonus->signupBanner($referralCode, $referrer?->name),
            'turnstileSiteKey' => $this->turnstile->siteKey(),
            'turnstileEnabled' => $this->turnstile->isEnabled(),
        ]);
    }

    public function adminLogin()
    {
        return view('auth.admin-login', [
            'seo' => [
                'title' => 'Admin Login — Semrushtoolz',
                'description' => 'Admin panel login for Semrushtoolz.',
            ],
        ]);
    }
}
