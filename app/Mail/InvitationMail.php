<?php

namespace App\Mail;

use App\Models\Invitation;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invitation $invitation,
        public Organization $organization,
        public ?string $inviterName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->organization->name} on Crewly360",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invitation',
            with: [
                'acceptUrl' => $this->invitation->acceptUrl(),
                'organizationName' => $this->organization->name,
                'inviterName' => $this->inviterName ?? 'An administrator',
                'role' => ucfirst($this->invitation->role),
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }
}
