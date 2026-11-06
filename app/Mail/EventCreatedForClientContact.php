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
 * Reported: the client-side contact who signed the contract (Event's
 * client_contact_email) has no way to know an event was actually created
 * for them — this fires every time (unlike StaffAccountCreated, which only
 * fires for a freshly created account), so a second, third, etc. event for
 * the same contact still gets its own notice. $password is set only when
 * findOrCreateClientContact() just created the account (null when reusing
 * an existing one — they already know how to log in, same reasoning as
 * every other account-reuse case in this app).
 */
class EventCreatedForClientContact extends Mailable
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
            'ro' => 'Evenimentul tău a fost creat: '.$this->event->name,
            'en' => 'Your event has been created: '.$this->event->name,
            'de' => 'Ihr Event wurde erstellt: '.$this->event->name,
        ];

        return new Envelope(
            subject: $subjects[$this->lang] ?? $subjects['ro'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.event-created-for-client-contact',
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
                'title' => 'Evenimentul tău a fost creat',
                'greeting' => 'Stimate {name},',
                'body' => 'Am creat evenimentul dumneavoastră „{event}" conform contractului, programat pe {date}. Îl puteți vedea oricând, autentificându-vă în contul dumneavoastră.',
                'credentialsLabel' => 'Date de autentificare:',
                'emailLabel' => 'Email',
                'passwordLabel' => 'Parolă',
                'recommendChange' => 'Recomandăm să vă schimbați parola după prima autentificare, din pagina de profil.',
                'button' => 'Vezi evenimentul',
                'regards' => 'Cu stimă,',
            ],
            'en' => [
                'title' => 'Your event has been created',
                'greeting' => 'Dear {name},',
                'body' => 'We have created your event "{event}" as per your contract, scheduled for {date}. You can view it anytime by signing in to your account.',
                'credentialsLabel' => 'Login credentials:',
                'emailLabel' => 'Email',
                'passwordLabel' => 'Password',
                'recommendChange' => 'We recommend changing your password after your first login, from the profile page.',
                'button' => 'View the event',
                'regards' => 'Best regards,',
            ],
            'de' => [
                'title' => 'Ihr Event wurde erstellt',
                'greeting' => 'Sehr geehrte(r) {name},',
                'body' => 'Wir haben Ihr Event „{event}" gemäß Ihrem Vertrag erstellt, geplant für {date}. Sie können es jederzeit einsehen, indem Sie sich in Ihr Konto einloggen.',
                'credentialsLabel' => 'Zugangsdaten:',
                'emailLabel' => 'E-Mail',
                'passwordLabel' => 'Passwort',
                'recommendChange' => 'Wir empfehlen, Ihr Passwort nach der ersten Anmeldung über die Profilseite zu ändern.',
                'button' => 'Event ansehen',
                'regards' => 'Mit freundlichen Grüßen,',
            ],
        ];

        return $strings[$this->lang][$key] ?? $strings['ro'][$key];
    }
}
