<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once, the first time the designated photographer opens their
 * event's own gallery page (GalleryController::index()) — reported: "email
 * opened" can't be trusted as proof anyone actually saw their access
 * instructions, so this is the real signal instead, and whoever is
 * responsible for the event (the assigned Partner's own account, or the
 * event's creator as a fallback — same recipient-resolution reasoning as
 * PhotoRemovalRequested/notifyModerators()) gets told the moment it
 * happens, not left to wonder until the event day.
 */
class PhotographerConfirmedAccess extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Event $event,
        public readonly string $photographerName,
        public readonly string $photographerEmail,
        public readonly string $eventUrl,
        public readonly string $lang = 'ro',
    ) {}

    public function envelope(): Envelope
    {
        $subjects = [
            'ro' => "Fotograful a confirmat accesul: {$this->event->name}",
            'en' => "The photographer confirmed access: {$this->event->name}",
            'de' => "Der Fotograf hat den Zugriff bestätigt: {$this->event->name}",
        ];

        return new Envelope(
            subject: $subjects[$this->lang] ?? $subjects['ro'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.photographer-confirmed-access',
            with: [
                'eventName' => $this->event->name,
                'greeting' => $this->t('greeting'),
                'explain' => strtr($this->t('explain'), [
                    '{event}' => $this->event->name,
                    '{name}' => $this->photographerName,
                    '{email}' => $this->photographerEmail,
                ]),
                'button' => $this->t('button'),
                'eventUrl' => $this->eventUrl,
                'regards' => $this->t('regards'),
                'lang' => $this->lang,
            ],
        );
    }

    private function t(string $key): string
    {
        $strings = [
            'ro' => [
                'greeting' => 'Bună,',
                'explain' => 'Fotograful desemnat, {name} ({email}), s-a autentificat și a accesat galeria evenimentului „{event}" — știe cum și unde să încarce pozele.',
                'button' => 'Vezi evenimentul',
                'regards' => 'Cu stimă,',
            ],
            'en' => [
                'greeting' => 'Hi,',
                'explain' => 'The designated photographer, {name} ({email}), has signed in and accessed the gallery for "{event}" — they know how and where to upload the photos.',
                'button' => 'View the event',
                'regards' => 'Best regards,',
            ],
            'de' => [
                'greeting' => 'Hallo,',
                'explain' => 'Der zuständige Fotograf, {name} ({email}), hat sich angemeldet und die Galerie für „{event}" aufgerufen — er weiß, wie und wo die Fotos hochzuladen sind.',
                'button' => 'Event ansehen',
                'regards' => 'Mit freundlichen Grüßen,',
            ],
        ];

        return $strings[$this->lang][$key] ?? $strings['ro'][$key];
    }
}
