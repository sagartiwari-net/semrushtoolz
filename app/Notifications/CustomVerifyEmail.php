<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmail extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirm your Semrushtoolz account')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Thanks for signing up. Please confirm your email address to activate your account and access the shop.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('This link expires in 60 minutes.')
            ->line('If you did not create an account, no action is required.');
    }
}
