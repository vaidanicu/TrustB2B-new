<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Reported: a photographer needs to know how and where to upload for
 * EVERY event they're assigned to, not just the first time an account
 * gets created for them (the old StaffAccountCreated flow only fired
 * once) — mirrors EventCreatedForClientContact exactly. $password is set
 * only when findOrCreatePhotographer() just created the account (null
 * when reusing an existing one).
 */
class PhotographerAssignedToEvent extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $account,
        public readonly Event $event,
        public readonly ?string $password = null,
        public readonly string $lang = 'ro',
    ) {}

    public function envelope(): Envelope
    {
        $subjects = [
            'ro' => 'Eveniment nou: '.$this->event->name,
            'en' => 'New event: '.$this->event->name,
            'de' => 'Neues Event: '.$this->event->name,
        ];

        return new Envelope(
            subject: $subjects[$this->lang] ?? $subjects['ro'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.photographer-assigned-to-event',
            with: [
                'title' => $this->t('title'),
                'greeting' => strtr($this->t('greeting'), ['{name}' => $this->account->name]),
                'body' => strtr($this->t('body'), [
                    '{event}' => $this->event->name,
                    '{date}' => $this->event->starts_at->format('d.m.Y H:i'),
                ]),
                'credentialsLabel' => $this->password !== null ? $this->t('credentialsLabel') : null,
                'emailLabel' => $this->t('emailLabel'),
                'passwordLabel' => $this->t('passwordLabel'),
                'password' => $this->password,
                'recommendChange' => $this->password !== null ? $this->t('recommendChange') : null,
                'button' => $this->t('button'),
                'regards' => $this->t('regards'),
                'account' => $this->account,
                'lang' => $this->lang,
            ],
        );
    }

    private function t(string $key): string
    {
        $strings = [
            'ro' => [
                'title' => 'Ai fost desemnat fotograf',
                'greeting' => 'Bună, {name},',
                'body' => 'Ai fost desemnat să încarci fotografiile/video-urile pentru evenimentul „{event}", programat pe {date}. Autentifică-te pentru a vedea galeria și a încărca pozele.',
                'credentialsLabel' => 'Date de autentificare:',
                'emailLabel' => 'Email',
                'passwordLabel' => 'Parolă',
                'recommendChange' => 'Recomandăm să-ți schimbi parola după prima autentificare, din pagina de profil.',
                'button' => 'Autentificare',
                'regards' => 'Cu stimă,',
            ],
            'en' => [
                'title' => 'You have been assigned as photographer',
                'greeting' => 'Hi {name},',
                'body' => 'You have been assigned to upload photos/videos for the event "{event}", scheduled for {date}. Sign in to see the gallery and upload the photos.',
                'credentialsLabel' => 'Login credentials:',
                'emailLabel' => 'Email',
                'passwordLabel' => 'Password',
                'recommendChange' => 'We recommend changing your password after your first login, from the profile page.',
                'button' => 'Log in',
                'regards' => 'Best regards,',
            ],
            'de' => [
                'title' => 'Du wurdest als Fotograf eingesetzt',
                'greeting' => 'Hallo {name},',
                'body' => 'Du wurdest benannt, um Fotos/Videos für das Event „{event}" hochzuladen, geplant für {date}. Melde dich an, um die Galerie zu sehen und die Fotos hochzuladen.',
                'credentialsLabel' => 'Zugangsdaten:',
                'emailLabel' => 'E-Mail',
                'passwordLabel' => 'Passwort',
                'recommendChange' => 'Wir empfehlen, dein Passwort nach der ersten Anmeldung über die Profilseite zu ändern.',
                'button' => 'Anmelden',
                'regards' => 'Mit freundlichen Grüßen,',
            ],
        ];

        return $strings[$this->lang][$key] ?? $strings['ro'][$key];
    }
}
