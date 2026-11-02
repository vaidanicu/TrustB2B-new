<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once per media.moderate-deletion-requests holder in the event's
 * organization, each in THEIR OWN locale (not a single shared one — unlike
 * most mail in this app, there's no acting user here: the submitter is an
 * anonymous public-gallery visitor, see GalleryController::requestRemoval()).
 * Synchronous, same reasoning as every other mail in this app: no queue
 * worker runs on this shared host, and a removal request must never fail
 * to save just because sending one of several notification emails errors.
 */
class PhotoRemovalRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Event $event,
        public readonly string $galleryUrl,
        public readonly ?string $requesterName,
        public readonly string $reason,
        public readonly string $lang = 'ro',
    ) {}

    public function envelope(): Envelope
    {
        $subjects = [
            'ro' => "Cerere de eliminare poză: {$this->event->name}",
            'en' => "Photo removal request: {$this->event->name}",
            'de' => "Entfernungsanfrage für ein Foto: {$this->event->name}",
        ];

        return new Envelope(
            subject: $subjects[$this->lang] ?? $subjects['ro'],
        );
    }

    public function content(): Content
    {
        $requesterLine = $this->requesterName !== null
            ? strtr($this->t('requesterLine'), ['{name}' => $this->requesterName])
            : $this->t('requesterUnknown');

        return new Content(
            view: 'mail.photo-removal-requested',
            with: [
                'eventName' => $this->event->name,
                'greeting' => $this->t('greeting'),
                'explain' => strtr($this->t('explain'), ['{event}' => $this->event->name]),
                'requesterLine' => $requesterLine,
                'reasonLabel' => $this->t('reasonLabel'),
                'reason' => $this->reason,
                'button' => $this->t('button'),
                'galleryUrl' => $this->galleryUrl,
                'regards' => $this->t('regards'),
            ],
        );
    }

    private function t(string $key): string
    {
        $strings = [
            'ro' => [
                'greeting' => 'Bună,',
                'explain' => 'Cineva a cerut eliminarea unei fotografii din galeria publică a evenimentului „{event}”.',
                'requesterLine' => 'Cerere trimisă de: {name}.',
                'requesterUnknown' => 'Persoana nu și-a lăsat numele.',
                'reasonLabel' => 'Motiv:',
                'button' => 'Vezi și decide în galerie',
                'regards' => 'Cu stimă,',
            ],
            'en' => [
                'greeting' => 'Hi,',
                'explain' => 'Someone has asked for a photo to be removed from the public gallery of "{event}".',
                'requesterLine' => 'Requested by: {name}.',
                'requesterUnknown' => "The person didn't leave their name.",
                'reasonLabel' => 'Reason:',
                'button' => 'Review and decide in the gallery',
                'regards' => 'Best regards,',
            ],
            'de' => [
                'greeting' => 'Hallo,',
                'explain' => 'Jemand hat die Entfernung eines Fotos aus der öffentlichen Galerie von „{event}" beantragt.',
                'requesterLine' => 'Angefragt von: {name}.',
                'requesterUnknown' => 'Die Person hat ihren Namen nicht angegeben.',
                'reasonLabel' => 'Begründung:',
                'button' => 'In der Galerie prüfen und entscheiden',
                'regards' => 'Mit freundlichen Grüßen,',
            ],
        ];

        return $strings[$this->lang][$key] ?? $strings['ro'][$key];
    }
}
