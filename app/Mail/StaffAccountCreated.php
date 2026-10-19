<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent from both OrganizationController@store (a client's first admin) and
 * UserController@store (a colleague added to an existing organization) —
 * one Mailable, since both are "here is your new TrustBridge account and
 * password" with only the context line differing. Synchronous, no
 * ShouldQueue, same reasoning as ParticipantInvited: no queue worker runs
 * on this shared host.
 */
class StaffAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $account,
        public readonly string $password,
        public readonly string $context,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contul tău TrustBridge',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.staff-account-created');
    }
}
