<?php

namespace App\Mail;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent synchronously (no ShouldQueue) from ParticipantController@store — no
 * queue worker runs on this shared host (same reasoning as Event skipping
 * media conversions), so a queued mail would just sit unsent forever.
 * Failure here must never block creating the participant: the invitation
 * link banner is the fallback either way, see the controller's try/catch.
 */
class ParticipantInvited extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Participant $participant,
        public readonly string $invitationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invitație: {$this->participant->event->name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.participant-invited');
    }
}
