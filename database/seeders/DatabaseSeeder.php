<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Base seed data.
 *
 * Kept separate from {@see EditionSeeder} because the two have different
 * lifetimes: the edition, the rooms and the rates are content the organisers own
 * and will edit, while this file is the minimum needed for a working install.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Order matters and is load-bearing:
        //
        //   EditionSeeder  creates the 2026 edition, the rooms and the tracks
        //                   that ProgrammeSeeder resolves its room/track codes
        //                   against, so it has to run first.
        //   SpeakersSeeder must precede ProgrammeSeeder: the programme attaches
        //   speakers by name and throws on an unknown name rather than silently
        //                   dropping the session. Seeding it afterwards fails.
        //
        // Every one of these is idempotent (updateOrCreate, or a delete-then-
        // rebuild for the programme), so `db:seed` can be re-run safely.
        $this->call([
            EditionSeeder::class,
            SpeakersSeeder::class,
            ProgrammeSeeder::class,
            DemoUserSeeder::class,
            LegacyUsersSeeder::class,
        ]);
    }
}
