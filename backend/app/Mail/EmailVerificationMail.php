<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $token,
        private readonly string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify Your Email — FixMyWindow');
    }

    public function content(): Content
    {
        $verifyUrl = rtrim(config('app.url'), '/') . '/api/v1/auth/email/verify/' . $this->token;

        return new Content(
            view: 'emails.verify_email',
            with: [
                'name'      => $this->name,
                'verifyUrl' => $verifyUrl,
            ],
        );
    }
}
