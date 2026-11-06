<?php

namespace App\Support;

/**
 * Every controller flashes short, templated confirmation strings
 * ("Eveniment creat: {name}") straight into the session — this app has no
 * lang/ files and never uses Laravel's own trans()/__() (see the Mail
 * classes' own inline $strings[$lang][$key] pattern for why), so those
 * were hardcoded in Romanian regardless of the acting user's own locale.
 * One place for all of them, keyed the same way across RO/EN/DE, used
 * wherever a controller needs `back()->with('status', ...)`.
 */
class FlashMessage
{
    public static function t(string $locale, string $key, array $replace = []): string
    {
        $strings = [
            'ro' => [
                'event.created' => 'Eveniment creat: {name}',
                'event.deleted' => 'Eveniment șters: {name}',
                'event.rescheduled' => 'Data evenimentului a fost actualizată: {name}',
                'gallery.uploaded' => 'Fotografii/video-uri încărcate.',
                'gallery.photo_deleted' => 'Fotografie ștearsă.',
                'gallery.removal_denied' => 'Cerere de eliminare respinsă.',
                'organization.created' => 'Organizație creată: {name}',
                'partner.deleted' => 'Partener șters: {name}',
                'partner.updated' => 'Partener actualizat: {name}',
                'partner.no_account' => 'Partenerul „{name}” nu are niciun cont — adaugă un email din „Editează” mai întâi.',
                'participant.invited' => 'Invitație creată pentru {name}',
                'participant.deleted' => 'Participant șters: {name}',
                'participant.deletion_denied' => 'Cerere de ștergere respinsă pentru {name}',
                'user.added' => 'Coleg adăugat: {name}',
                'user.deleted' => 'Coleg șters: {name}',
                'auth.already_authenticated' => 'Ești deja autentificat ca {name} ({email}). Ca să intri cu alt cont, apasă „Ieșire” mai întâi.',
            ],
            'en' => [
                'event.created' => 'Event created: {name}',
                'event.deleted' => 'Event deleted: {name}',
                'event.rescheduled' => 'Event date updated: {name}',
                'gallery.uploaded' => 'Photos/videos uploaded.',
                'gallery.photo_deleted' => 'Photo deleted.',
                'gallery.removal_denied' => 'Removal request denied.',
                'organization.created' => 'Organization created: {name}',
                'partner.deleted' => 'Partner deleted: {name}',
                'partner.updated' => 'Partner updated: {name}',
                'partner.no_account' => 'Partner "{name}" has no account yet — add an email from "Edit" first.',
                'participant.invited' => 'Invitation created for {name}',
                'participant.deleted' => 'Participant deleted: {name}',
                'participant.deletion_denied' => 'Deletion request denied for {name}',
                'user.added' => 'Colleague added: {name}',
                'user.deleted' => 'Colleague deleted: {name}',
                'auth.already_authenticated' => 'You are already signed in as {name} ({email}). To sign in with a different account, click "Sign out" first.',
            ],
            'de' => [
                'event.created' => 'Event erstellt: {name}',
                'event.deleted' => 'Event gelöscht: {name}',
                'event.rescheduled' => 'Event-Datum aktualisiert: {name}',
                'gallery.uploaded' => 'Fotos/Videos hochgeladen.',
                'gallery.photo_deleted' => 'Foto gelöscht.',
                'gallery.removal_denied' => 'Entfernungsanfrage abgelehnt.',
                'organization.created' => 'Organisation erstellt: {name}',
                'partner.deleted' => 'Partner gelöscht: {name}',
                'partner.updated' => 'Partner aktualisiert: {name}',
                'partner.no_account' => 'Partner „{name}" hat noch kein Konto — fügen Sie zuerst eine E-Mail unter „Bearbeiten" hinzu.',
                'participant.invited' => 'Einladung erstellt für {name}',
                'participant.deleted' => 'Teilnehmer gelöscht: {name}',
                'participant.deletion_denied' => 'Löschanfrage abgelehnt für {name}',
                'user.added' => 'Kollege hinzugefügt: {name}',
                'user.deleted' => 'Kollege gelöscht: {name}',
                'auth.already_authenticated' => 'Sie sind bereits als {name} ({email}) angemeldet. Um sich mit einem anderen Konto anzumelden, klicken Sie zuerst auf „Abmelden".',
            ],
        ];

        $template = $strings[$locale][$key] ?? $strings['ro'][$key] ?? $key;

        $replacements = [];
        foreach ($replace as $search => $value) {
            $replacements['{'.$search.'}'] = $value;
        }

        return strtr($template, $replacements);
    }
}
