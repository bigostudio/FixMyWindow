<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your FixMyWindow Account Has Been Approved');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_approved',
            with: ['name' => $this->name],
        );
    }
}
