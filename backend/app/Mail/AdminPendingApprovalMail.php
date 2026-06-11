<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminPendingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly User $newUser,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New Team Member Registration — Approval Required');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_pending_approval',
            with: [
                'name'  => $this->newUser->name,
                'email' => $this->newUser->email,
                'phone' => $this->newUser->phone,
                'role'  => $this->newUser->role->value,
            ],
        );
    }
}
