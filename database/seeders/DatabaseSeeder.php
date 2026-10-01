<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Speaker;
use App\Models\User;
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
        $this->call([
            EditionSeeder::class,
        ]);

        $this->call([
            DemoUserSeeder::class,
        ]);
    }
}
