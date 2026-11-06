<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Reported live: "palatCSinta_"-style generated passwords, emailed once and
 * shown on screen once, kept not working — the account always needed a
 * reset. Str::password()'s own default symbol set includes '&', '<', '>'
 * (HTML-special — Blade's {{ }} escaping turns them into '&amp;'/'&lt;'/
 * '&gt;' in the email's raw HTML, which most mail clients render back
 * correctly, but a plain-text view, a forwarded copy, or a notification
 * preview can show the escaped entity text literally instead of the real
 * character) alongside '\', '{', '}', '|' — also fragile to retype
 * correctly from a screen or a quoted-printable-encoded email body. Letters
 * and numbers alone, at 14+ characters, are still far beyond brute-forcible
 * for a one-time password the recipient is told to change on first login —
 * every call site that generates a credential a human must read and retype
 * uses this instead of Str::password() directly, so that policy lives in
 * one place.
 */
class GeneratedPassword
{
    public static function make(int $length = 14): string
    {
        return Str::password($length, symbols: false);
    }
}
