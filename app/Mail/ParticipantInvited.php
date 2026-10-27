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
 *
 * $locale is the SENDER's (the organizer creating the invitation), not the
 * participant's — a participant has no account/locale of their own, and
 * the organizer is the one who knows which language the recipient reads.
 */
class ParticipantInvited extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Participant $participant,
        public readonly string $invitationUrl,
        public readonly string $lang = 'ro',
    ) {}

    public function envelope(): Envelope
    {
        $subjects = [
            'ro' => "Invitație: {$this->participant->event->name}",
            'en' => "Invitation: {$this->participant->event->name}",
            'de' => "Einladung: {$this->participant->event->name}",
        ];

        return new Envelope(
            subject: $subjects[$this->lang] ?? $subjects['ro'],
        );
    }

    public function content(): Content
    {
        $event = $this->participant->event;
        $organizerName = $event->organizer ?? $event->organization?->name ?? match ($this->lang) {
            'en' => 'The organizer',
            'de' => 'Der Veranstalter',
            default => 'Organizatorul',
        };

        $dateFormat = $this->lang === 'de' ? 'd. F Y' : 'd F Y';
        $startsAt = $event->starts_at->locale($this->lang)->translatedFormat($dateFormat);
        $expiresAt = $this->participant->link_expires_at->locale($this->lang)->translatedFormat($dateFormat);

        $locationLine = $event->location
            ? strtr($this->t('locationSuffix'), ['{location}' => $event->location])
            : '';

        $invites = strtr($this->t('invites'), [
            '{organizer}' => $organizerName,
            '{event}' => $event->name,
            '{date}' => $startsAt,
            '{locationLine}' => $locationLine,
        ]);

        return new Content(
            view: 'mail.participant-invited',
            with: [
                'eventName' => $event->name,
                'greeting' => strtr($this->t('greeting'), ['{name}' => $this->participant->name]),
                'invites' => $invites,
                'confirm' => $this->t('confirm'),
                'button' => $this->t('button'),
                'codeLabel' => $this->t('codeLabel'),
                'checkinCode' => $this->participant->checkin_code,
                'linkNotice' => strtr($this->t('linkNotice'), ['{date}' => $expiresAt]),
                'regards' => $this->t('regards'),
            ],
        );
    }

    private function t(string $key): string
    {
        $strings = [
            'ro' => [
                'greeting' => 'Bună, {name},',
                'invites' => '{organizer} te invită la {event}, pe {date}{locationLine}.',
                'locationSuffix' => ', la {location}',
                'confirm' => 'Confirmă dacă participi și preferința ta privind fotografiile oficiale apăsând butonul de mai jos.',
                'button' => 'Răspunde la invitație',
                'codeLabel' => 'Codul tău de acces la eveniment (prezintă-l la intrare):',
                'linkNotice' => 'Acest link e personal — nu îl distribui altcuiva. Expiră pe {date}.',
                'regards' => 'Cu stimă,',
            ],
            'en' => [
                'greeting' => 'Hi {name},',
                'invites' => '{organizer} invites you to {event} on {date}{locationLine}.',
                'locationSuffix' => ', at {location}',
                'confirm' => 'Please confirm whether you are attending and your preference regarding official photos using the button below.',
                'button' => 'Respond to invitation',
                'codeLabel' => 'Your event access code (show it at the entrance):',
                'linkNotice' => 'This link is personal — do not share it with anyone else. It expires on {date}.',
                'regards' => 'Best regards,',
            ],
            'de' => [
                'greeting' => 'Hallo {name},',
                'invites' => '{organizer} lädt dich ein zu {event} am {date}{locationLine}.',
                'locationSuffix' => ', in {location}',
                'confirm' => 'Bitte bestätige über den Button unten, ob du teilnimmst, und deine Präferenz bezüglich offizieller Fotos.',
                'button' => 'Auf Einladung antworten',
                'codeLabel' => 'Dein Zugangscode für das Event (am Eingang vorzeigen):',
                'linkNotice' => 'Dieser Link ist persönlich — teile ihn mit niemandem. Er läuft am {date} ab.',
                'regards' => 'Mit freundlichen Grüßen,',
            ],
        ];

        return $strings[$this->lang][$key] ?? $strings['ro'][$key];
    }
}
