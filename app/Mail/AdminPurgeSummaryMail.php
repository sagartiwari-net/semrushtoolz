<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminPurgeSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $deletedCount,
        public array $emails,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->deletedCount} unverified account(s) purged — ".config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.admin-purge-summary',
        );
    }
}
