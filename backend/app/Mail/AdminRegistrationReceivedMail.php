<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminRegistrationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Registration Received — Pending Approval');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_registration_received',
            with: [
                'name' => $this->user->name,
                'role' => $this->user->role->value,
            ],
        );
    }
}
