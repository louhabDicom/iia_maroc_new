<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Imports the legacy WordPress `iia_users` table.
 *
 * The dump lives at `public/iia_users.sql` (a phpMyAdmin export of the 2024
 * site's `iia_users` table) and is read at run time, so the file stays the
 * single source of truth and this seeder holds no copy of the data.
 *
 * What it preserves:
 *  - the legacy row id, so other 2024 imports can still join on it;
 *  - the display name (split into first/last), the email and the password hash;
 *  - `user_registered` as both `created_at` and `email_verified_at`, because
 *    these are pre-existing accounts and should not be forced through the 2026
 *    email-verification flow.
 *
 * What it changes:
 *  - emails are lower-cased (the new `email` column is unique and the sign-in
 *    lookup is case-insensitive);
 *  - `$wp$2y$...` hashes lose the `$wp$` prefix, leaving a plain bcrypt hash
 *    that `Hash::check()` verifies as-is;
 *  - `$P$...` phpass hashes are stored verbatim. They only verify once a legacy
 *    phpass verifier is installed alongside bcrypt; without one those accounts
 *    can sign in only after a password reset.
 *
 * Rows with no email are skipped: the sign-in form matches accounts on `email`
 * or `phone` only, so an account with neither could never sign in.
 *
 * Idempotent: re-running updates the same ids rather than duplicating rows.
 */
class LegacyUsersSeeder extends Seeder
{
    public function run(): void
    {
        $path = public_path('iia_users.sql');

        if (! is_file($path)) {
            throw new RuntimeException("Legacy dump not found: {$path}");
        }

        $rows = $this->parseInsertRows((string) file_get_contents($path));

        if ($rows === []) {
            throw new RuntimeException('No rows parsed from public/iia_users.sql — is the dump format unchanged?');
        }

        $records = [];
        $skipped = 0;
        $phpass = 0;
        $bcrypt = 0;

        foreach ($rows as $row) {
            if (count($row) < 10) {
                continue;
            }

            [$id, $login, $pass, , $email, , $registered, $activation, $status, $display] = $row;

            $email = mb_strtolower(trim($email));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;

                continue;
            }

            $display = trim($display) !== '' ? trim($display) : $login;
            [$firstName, $lastName] = $this->splitName($display);

            $registeredAt = $this->toDate($registered) ?? now();

            $password = $this->normalizeHash($pass);

            if (str_starts_with($password, '$P$')) {
                $phpass++;
            } elseif (str_starts_with($password, '$2')) {
                $bcrypt++;
            }

            $records[] = [
                'id' => (int) $id,
                'name' => $display,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => $password,
                'email_verified_at' => $registeredAt->toDateTimeString(),
                'locale' => 'fr',
                'is_admin' => false,
                'terms_accepted_at' => null,
                'created_at' => $registeredAt->toDateTimeString(),
                'updated_at' => $registeredAt->toDateTimeString(),
            ];
        }

        foreach (array_chunk($records, 250) as $chunk) {
            DB::table('users')->upsert($chunk, ['id'], [
                'name', 'first_name', 'last_name', 'email', 'password',
                'email_verified_at', 'locale', 'created_at', 'updated_at',
            ]);
        }

        $this->command?->info(sprintf(
            'Legacy users imported: %d (skipped %d without a usable email).',
            count($records),
            $skipped,
        ));

        if ($phpass > 0) {
            $this->command?->warn(sprintf(
                '%d account(s) keep a legacy phpass hash and cannot sign in until a phpass verifier is added alongside bcrypt.',
                $phpass,
            ));
        }
    }

    /**
     * Parse the `( ... ), ( ... )` tuples out of the `INSERT INTO iia_users`
     * statements. A character scanner rather than a regex on each row: SQL
     * string literals contain commas and escaped quotes, which a naive split on
     * `),(` would shred.
     *
     * @return list<list<string>>
     */
    private function parseInsertRows(string $sql): array
    {
        if (preg_match_all('/INSERT\s+INTO\s+`?iia_users`?.*?VALUES(.*?);/is', $sql, $matches) === false) {
            return [];
        }

        $rows = [];

        foreach ($matches[1] as $block) {
            $length = strlen($block);
            $inString = false;
            $escaped = false;
            $row = null;
            $field = '';

            for ($i = 0; $i < $length; $i++) {
                $char = $block[$i];

                if ($inString) {
                    if ($escaped) {
                        $field .= $char;
                        $escaped = false;
                    } elseif ($char === '\\') {
                        $escaped = true;
                    } elseif ($char === "'") {
                        $inString = false;
                    } else {
                        $field .= $char;
                    }

                    continue;
                }

                if ($char === "'") {
                    $inString = true;
                } elseif ($char === '(') {
                    $row = [];
                    $field = '';
                } elseif ($char === ',' && $row !== null) {
                    $row[] = trim($field);
                    $field = '';
                } elseif ($char === ')' && $row !== null) {
                    $row[] = trim($field);
                    $rows[] = $row;
                    $row = null;
                    $field = '';
                } elseif ($row !== null) {
                    $field .= $char;
                }
            }
        }

        return $rows;
    }

    /** @return array{0: string|null, 1: string|null} */
    private function splitName(string $display): array
    {
        $parts = preg_split('/\s+/', trim($display), 2) ?: [];

        return [$parts[0] ?? null, $parts[1] ?? null];
    }

    /**
     * Recover a hash Laravel can verify. WordPress writes bcrypt hashes as
     * `$wp` + the bcrypt string, i.e. `$wp$2y$10$...`; dropping the `$wp`
     * wrapper (three characters) leaves the original `$2y$...`. phpass `$P$`
     * hashes are returned untouched.
     */
    private function normalizeHash(string $hash): string
    {
        $hash = trim($hash);

        return str_starts_with($hash, '$wp$') ? substr($hash, 3) : $hash;
    }

    private function toDate(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
