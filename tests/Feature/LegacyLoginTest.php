<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Signing in with a password hash carried over from the 2024 WordPress site.
 *
 * The importer carries three shapes: phpass `$P$` hashes, WordPress bcrypt
 * written as `$wp$2y$...`, and (from an earlier importer bug) bcrypt with its
 * leading `$` stripped to `2y$...`. bcrypt cannot read any of them, and with
 * `HASH_VERIFY=true` the first two made `Hash::check()` throw, so every
 * imported account returned a 500 instead of the sign-in form. These tests pin
 * the fix at the HTTP boundary: the account signs in, and the stored hash is
 * upgraded to bcrypt so the legacy shape only has to be understood once.
 */
class LegacyLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A published vector from the phpass documentation: the hash of "password".
     */
    private const PHPASS_PASSWORD = 'password';

    private const PHPASS_HASH = '$P$8ohUJ.1sdFw09/bMaAQPTGDNi2BIUt1';

    private function userWithStoredPassword(string $password): User
    {
        $user = User::factory()->create(['email' => 'legacy@example.test']);

        // Bypass the model's `hashed` cast, which would turn the legacy value
        // into a bcrypt hash of itself and defeat the point of the test.
        DB::table('users')->where('id', $user->id)->update(['password' => $password]);

        return $user->refresh();
    }

    private function storedPassword(User $user): string
    {
        return (string) DB::table('users')->where('id', $user->id)->value('password');
    }

    public function test_a_phpass_password_signs_in_and_is_upgraded_to_bcrypt(): void
    {
        $user = $this->userWithStoredPassword(self::PHPASS_HASH);

        $response = $this->post(route('login.store.ar'), [
            'identifier' => 'legacy@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $rehashed = $this->storedPassword($user);
        $this->assertSame('bcrypt', password_get_info($rehashed)['algoName']);
        $this->assertTrue(password_verify('password', $rehashed));
    }

    public function test_a_bcrypt_hash_missing_its_leading_dollar_signs_in(): void
    {
        $plain = 'Legacy#Pass1';
        // Reproduce the importer that stripped `$wp$` instead of `$wp`, leaving
        // the stored hash without bcrypt's leading `$`.
        $broken = substr((string) password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]), 1);
        $user = $this->userWithStoredPassword($broken);

        $response = $this->post(route('login.store.ar'), [
            'identifier' => 'legacy@example.test',
            'password' => $plain,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $rehashed = $this->storedPassword($user);
        $this->assertSame('bcrypt', password_get_info($rehashed)['algoName']);
        $this->assertStringStartsWith('$2y$', $rehashed);
    }

    public function test_a_wordpress_wrapped_bcrypt_hash_signs_in(): void
    {
        $plain = 'Legacy#Pass2';
        $wrapped = '$wp'.(string) password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
        $user = $this->userWithStoredPassword($wrapped);

        $response = $this->post(route('login.store.ar'), [
            'identifier' => 'legacy@example.test',
            'password' => $plain,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('bcrypt', password_get_info($this->storedPassword($user))['algoName']);
    }

    public function test_a_wrong_phpass_password_is_rejected_without_a_server_error(): void
    {
        $user = $this->userWithStoredPassword(self::PHPASS_HASH);

        $response = $this->post(route('login.store.ar'), [
            'identifier' => 'legacy@example.test',
            'password' => 'not-the-password',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
        // The legacy hash is left untouched when the password does not match.
        $this->assertSame(self::PHPASS_HASH, $this->storedPassword($user));
    }
}
