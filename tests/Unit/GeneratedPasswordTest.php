<?php

namespace Tests\Unit;

use App\Support\GeneratedPassword;
use PHPUnit\Framework\TestCase;

/**
 * Reported live: a generated password shown in an email didn't work,
 * requiring a reset every time — traced to Str::password()'s default
 * symbol set including '&'/'<'/'>', which Blade's {{ }} escaping turns
 * into HTML entities that some mail clients display literally instead of
 * rendering back to the real character. Letters/numbers only from here on.
 */
class GeneratedPasswordTest extends TestCase
{
    public function test_it_never_contains_a_symbol_character(): void
    {
        // Run many times — the whole bug only shows up probabilistically
        // (Str::password() picks symbols at random), so a single sample
        // proves nothing either way.
        for ($i = 0; $i < 200; $i++) {
            $password = GeneratedPassword::make();

            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $password, "Generated password \"{$password}\" contains a non-alphanumeric character.");
        }
    }

    public function test_it_honors_the_requested_length(): void
    {
        $this->assertSame(16, strlen(GeneratedPassword::make(16)));
        $this->assertSame(14, strlen(GeneratedPassword::make(14)));
    }
}
