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
                'gallery.uploaded' => 'Fotografii/video-uri încărcate.',
                'gallery.photo_deleted' => 'Fotografie ștearsă.',
                'gallery.removal_denied' => 'Cerere de eliminare respinsă.',
                'organization.created' => 'Organizație creată: {name}',
                'participant.invited' => 'Invitație creată pentru {name}',
                'participant.deleted' => 'Participant șters: {name}',
                'participant.deletion_denied' => 'Cerere de ștergere respinsă pentru {name}',
                'user.added' => 'Coleg adăugat: {name}',
                'user.deleted' => 'Coleg șters: {name}',
            ],
            'en' => [
                'event.created' => 'Event created: {name}',
                'event.deleted' => 'Event deleted: {name}',
                'gallery.uploaded' => 'Photos/videos uploaded.',
                'gallery.photo_deleted' => 'Photo deleted.',
                'gallery.removal_denied' => 'Removal request denied.',
                'organization.created' => 'Organization created: {name}',
                'participant.invited' => 'Invitation created for {name}',
                'participant.deleted' => 'Participant deleted: {name}',
                'participant.deletion_denied' => 'Deletion request denied for {name}',
                'user.added' => 'Colleague added: {name}',
                'user.deleted' => 'Colleague deleted: {name}',
            ],
            'de' => [
                'event.created' => 'Event erstellt: {name}',
                'event.deleted' => 'Event gelöscht: {name}',
                'gallery.uploaded' => 'Fotos/Videos hochgeladen.',
                'gallery.photo_deleted' => 'Foto gelöscht.',
                'gallery.removal_denied' => 'Entfernungsanfrage abgelehnt.',
                'organization.created' => 'Organisation erstellt: {name}',
                'participant.invited' => 'Einladung erstellt für {name}',
                'participant.deleted' => 'Teilnehmer gelöscht: {name}',
                'participant.deletion_denied' => 'Löschanfrage abgelehnt für {name}',
                'user.added' => 'Kollege hinzugefügt: {name}',
                'user.deleted' => 'Kollege gelöscht: {name}',
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
