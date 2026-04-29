<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application,
        public string $statusLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Status lamaran Anda diperbarui',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.application-status-updated',
        );
    }
}
