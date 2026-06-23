<?php

namespace App\Notifications;

use App\Models\LoginOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public string $purpose,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->purpose) {
            LoginOtp::PURPOSE_PERIODIC => 'Security verification — Semrushtoolz login',
            default => 'Your Semrushtoolz login code',
        };

        $intro = match ($this->purpose) {
            LoginOtp::PURPOSE_PERIODIC => 'For your security, please verify your login. It has been more than '.config('auth_otp.recheck_days', 15).' days since your last verification.',
            default => 'Use this one-time code to sign in to your Semrushtoolz account.',
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line($intro)
            ->line('Your verification code is:')
            ->line('**'.$this->code.'**')
            ->line('This code expires in '.config('auth_otp.expires_minutes', 10).' minutes.')
            ->line('If you did not request this, you can safely ignore this email.');
    }
}
