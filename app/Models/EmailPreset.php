<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailPreset extends Model
{
    public const CATEGORY_OTP = 'otp';

    public const CATEGORY_AUTH = 'auth';

    public const CATEGORY_SUBSCRIPTION = 'subscription';

    public const CATEGORY_PLAN_EXPIRY = 'plan_expiry';

    public const CATEGORY_PROMOTION = 'promotion';

    public const CATEGORY_AFFILIATE = 'affiliate';

    public const CATEGORY_OTHER = 'other';

    public const TYPE_TRANSACTIONAL = 'transactional';

    public const TYPE_PROMO = 'promo';

    public const KEY_LOGIN_OTP = 'login_otp';

    public const KEY_LOGIN_CONFIRMATION = 'login_confirmation';

    public const KEY_SIGNUP_CREDENTIALS = 'signup_credentials';

    public const KEY_FORGOT_PASSWORD = 'forgot_password';

    public const KEY_VERIFY_EMAIL = 'verify_email';

    public const KEY_PERIODIC_OTP = 'periodic_otp';

    public const KEY_SUBSCRIPTION_PAID = 'subscription_paid';

    public const KEY_SUBSCRIPTION_PENDING = 'subscription_pending';

    public const KEY_NEW_TOOL = 'new_tool';

    public const KEY_PLAN_EXPIRE_7D = 'plan_expire_7d';

    public const KEY_PLAN_EXPIRE_3D = 'plan_expire_3d';

    public const KEY_PLAN_EXPIRE_2D = 'plan_expire_2d';

    public const KEY_PLAN_EXPIRE_TODAY = 'plan_expire_today';

    public const KEY_PLAN_EXPIRED_1D = 'plan_expired_1d';

    public const KEY_PLAN_EXPIRED_2D = 'plan_expired_2d';

    public const KEY_PLAN_EXPIRED_3D = 'plan_expired_3d';

    public const KEY_PLAN_EXPIRED_1W = 'plan_expired_1w';

    public const KEY_PLAN_EXPIRED_2W = 'plan_expired_2w';

    public const KEY_PROMO_OFFER_1 = 'promo_offer_1';

    public const KEY_PROMO_OFFER_2 = 'promo_offer_2';

    public const KEY_PROMO_OFFER_3 = 'promo_offer_3';

    public const KEY_REFERRAL_BONUS_DAY1 = 'referral_bonus_day1';

    public const KEY_REFERRAL_BONUS_DAY2 = 'referral_bonus_day2';

    public const KEY_REFERRAL_BONUS_DAY3 = 'referral_bonus_day3';

    public const KEY_AFFILIATE_MONTHLY_REPORT = 'affiliate_monthly_report';

    public const KEY_AFFILIATE_PROGRAM_INVITE = 'affiliate_program_invite';

    public const KEY_AFFILIATE_PROGRAM_BOOST = 'affiliate_program_boost';

    public const KEY_WALLET_TOPUP_COMPLETED = 'wallet_topup_completed';

    public const KEY_WALLET_CASHBACK_RECEIVED = 'wallet_cashback_received';

    protected $fillable = [
        'key',
        'name',
        'category',
        'slug',
        'subject',
        'html_body',
        'text_body',
        'type',
        'is_enabled',
        'is_system',
        'sort_order',
        'description',
        'variables_help',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_system' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public static function categories(): array
    {
        return [
            self::CATEGORY_OTP => 'OTP & Login',
            self::CATEGORY_AUTH => 'Authentication',
            self::CATEGORY_SUBSCRIPTION => 'Subscription & Payments',
            self::CATEGORY_PLAN_EXPIRY => 'Plan Expiry Reminders',
            self::CATEGORY_PROMOTION => 'Promotions & Offers',
            self::CATEGORY_AFFILIATE => 'Affiliate Program',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    public static function findByKey(string $key): ?self
    {
        return static::query()->where('key', $key)->where('is_enabled', true)->first();
    }

    public static function slugFromKey(string $key): string
    {
        return 'semrushtoolz-'.str_replace('_', '-', $key);
    }

    public function categoryLabel(): string
    {
        return static::categories()[$this->category] ?? ucfirst($this->category);
    }
}
