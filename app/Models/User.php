<?php

namespace App\Models;

use App\Exceptions\MailPanelException;
use App\Notifications\CustomResetPassword;
use App\Notifications\CustomVerifyEmail;
use App\Services\MailPanel\MailPanelService;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Storage;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'avatar_path', 'referral_code', 'status', 'referred_by_user_id', 'referral_bonus_expires_at', 'last_login_otp_at', 'preferences', 'wallet_balance'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_otp_at' => 'datetime',
            'referral_bonus_expires_at' => 'datetime',
            'preferences' => 'array',
            'wallet_balance' => 'decimal:2',
            'password' => 'hashed',
            'blocked_at' => 'datetime',
            'last_ip_check_at' => 'datetime',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        $mailPanel = app(MailPanelService::class);

        if ($mailPanel->isEnabled()) {
            try {
                $mailPanel->sendEmailVerification($this);

                return;
            } catch (MailPanelException $exception) {
                Log::error('Mail Panel verification email failed', [
                    'user_id' => $this->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $this->notify(new CustomVerifyEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $mailPanel = app(MailPanelService::class);

        if ($mailPanel->isEnabled()) {
            try {
                $mailPanel->sendPasswordReset($this, $token);

                return;
            } catch (MailPanelException $exception) {
                Log::error('Mail Panel password reset email failed', [
                    'user_id' => $this->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $this->notify(new CustomResetPassword($token));
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function avatarUrl(): ?string
    {
        if (! filled($this->avatar_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function toolSessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ToolSession::class);
    }

    public function referrals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    public function affiliateCommissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'referrer_user_id');
    }

    public function referrer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function loginLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserLoginLog::class);
    }

    public function securityAlerts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SecurityAlert::class);
    }

    public function supportTickets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function affiliatePayouts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    public function walletTransactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function resolvedPreferences(): array
    {
        $defaults = [
            'notify_email' => true,
            'notify_expiry' => true,
            'notify_login' => true,
            'timezone' => 'Asia/Kolkata',
            'theme' => 'light',
        ];

        return array_merge($defaults, is_array($this->preferences) ? $this->preferences : []);
    }
}
