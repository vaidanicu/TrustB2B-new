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
 *
 * $locale is the acting user's (whoever created this account), not the new
 * account's own — the new account hasn't logged in yet to set one, and the
 * creator is the one who knows which language the recipient reads.
 * $contextType picks which sentence template to translate; the context
 * line itself is never passed in pre-built so every language can get its
 * own word order instead of a fixed Romanian sentence.
 */
class StaffAccountCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $account,
        public readonly string $password,
        public readonly string $contextType,
        public readonly string $organizationName,
        public readonly string $lang = 'ro',
    ) {}

    public function envelope(): Envelope
    {
        $subjects = [
            'ro' => 'Contul tău TrustBridge',
            'en' => 'Your TrustBridge account',
            'de' => 'Dein TrustBridge-Konto',
        ];

        return new Envelope(
            subject: $subjects[$this->lang] ?? $subjects['ro'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.staff-account-created',
            with: [
                'title' => $this->t('title'),
                'greeting' => strtr($this->t('greeting'), ['{name}' => $this->account->name]),
                'context' => strtr($this->t($this->contextType), ['{organization}' => $this->organizationName]),
                'credentialsLabel' => $this->t('credentialsLabel'),
                'emailLabel' => $this->t('emailLabel'),
                'passwordLabel' => $this->t('passwordLabel'),
                'recommendChange' => $this->t('recommendChange'),
                'button' => $this->t('button'),
                'regards' => $this->t('regards'),
            ],
        );
    }

    private function t(string $key): string
    {
        $strings = [
            'ro' => [
                'title' => 'Contul tău a fost creat',
                'greeting' => 'Bună, {name},',
                'organization-admin' => 'Ai fost desemnat admin pentru organizația {organization}.',
                'organization-staff' => 'Ai fost adăugat în echipa organizației {organization}.',
                'event-photographer' => 'Ai fost desemnat să încarci fotografiile/video-urile pentru evenimentul {organization}.',
                'platform-admin' => 'Ai acces de platform-admin pe TrustBridge.',
                'credentialsLabel' => 'Date de autentificare:',
                'emailLabel' => 'Email',
                'passwordLabel' => 'Parolă',
                'recommendChange' => 'Recomandăm să-ți schimbi parola după prima autentificare, din pagina de profil.',
                'button' => 'Autentificare',
                'regards' => 'Cu stimă,',
            ],
            'en' => [
                'title' => 'Your account has been created',
                'greeting' => 'Hi {name},',
                'organization-admin' => 'You have been assigned as admin for the {organization} organization.',
                'organization-staff' => "You have been added to the {organization} organization's team.",
                'event-photographer' => 'You have been designated to upload photos/videos for the {organization} event.',
                'platform-admin' => 'You have platform-admin access on TrustBridge.',
                'credentialsLabel' => 'Login credentials:',
                'emailLabel' => 'Email',
                'passwordLabel' => 'Password',
                'recommendChange' => 'We recommend changing your password after your first login, from the profile page.',
                'button' => 'Log in',
                'regards' => 'Best regards,',
            ],
            'de' => [
                'title' => 'Dein Konto wurde erstellt',
                'greeting' => 'Hallo {name},',
                'organization-admin' => 'Du wurdest als Administrator für die Organisation {organization} eingesetzt.',
                'organization-staff' => 'Du wurdest dem Team der Organisation {organization} hinzugefügt.',
                'event-photographer' => 'Du wurdest benannt, um Fotos/Videos für das Event {organization} hochzuladen.',
                'platform-admin' => 'Du hast Platform-Admin-Zugriff auf TrustBridge.',
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
