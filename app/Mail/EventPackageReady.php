<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once, from ProcessEventRetentionCommand, when an event's retention
 * deadline is reached and its end_of_cycle_action is 'export' — the signed
 * link is the ONLY thing in this email; the page behind it (not this
 * email) carries the download button and the archive's password, see
 * EventExportController::show(). Synchronous (no ShouldQueue), same
 * reasoning as ParticipantInvited: no queue worker runs on this host.
 *
 * $lang is the event's own `language` field — the recipient here is always
 * the client's side, which has no account/locale of its own, same as a
 * participant.
 */
class EventPackageReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Event $event,
        public readonly string $packageUrl,
        public readonly string $lang = 'ro',
    ) {}

    public function envelope(): Envelope
    {
        $subjects = [
            'ro' => "Pachet foto pregătit: {$this->event->name}",
            'en' => "Photo package ready: {$this->event->name}",
            'de' => "Fotopaket bereit: {$this->event->name}",
        ];

        return new Envelope(
            subject: $subjects[$this->lang] ?? $subjects['ro'],
        );
    }

    public function content(): Content
    {
        $recipientLine = filled($this->event->final_package_recipient)
            ? strtr($this->t('recipientLine'), ['{recipient}' => $this->event->final_package_recipient])
            : '';

        return new Content(
            view: 'mail.event-package-ready',
            with: [
                'eventName' => $this->event->name,
                'greeting' => $this->t('greeting'),
                'recipientLine' => $recipientLine,
                'explain' => $this->t('explain'),
                'button' => $this->t('button'),
                'packageUrl' => $this->packageUrl,
                'regards' => $this->t('regards'),
            ],
        );
    }

    private function t(string $key): string
    {
        $strings = [
            'ro' => [
                'greeting' => 'Bună,',
                'recipientLine' => 'Pachetul este pregătit pentru: {recipient}.',
                'explain' => 'Fotografiile și video-urile evenimentului au ajuns la finalul perioadei de păstrare. Pachetul este gata — descarcă-l și confirmă primirea apăsând butonul de mai jos. Fișierele originale rămân pe platformă până confirmi.',
                'button' => 'Vezi și descarcă pachetul',
                'regards' => 'Cu stimă,',
            ],
            'en' => [
                'greeting' => 'Hi,',
                'recipientLine' => 'This package is prepared for: {recipient}.',
                'explain' => "This event's photos and videos have reached the end of their retention period. The package is ready — download it and confirm receipt using the button below. The original files stay on the platform until you confirm.",
                'button' => 'View and download the package',
                'regards' => 'Best regards,',
            ],
            'de' => [
                'greeting' => 'Hallo,',
                'recipientLine' => 'Das Paket ist vorbereitet für: {recipient}.',
                'explain' => 'Die Fotos und Videos dieses Events haben das Ende der Aufbewahrungsfrist erreicht. Das Paket ist bereit — laden Sie es über den Button unten herunter und bestätigen Sie den Erhalt. Die Originaldateien bleiben auf der Plattform, bis Sie bestätigen.',
                'button' => 'Paket ansehen und herunterladen',
                'regards' => 'Mit freundlichen Grüßen,',
            ],
        ];

        return $strings[$this->lang][$key] ?? $strings['ro'][$key];
    }
}
