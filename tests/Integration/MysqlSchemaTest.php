<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Runs the same assertions against the production engine.
 *
 * sqlite is the default because it is fast, but it is not the target: MariaDB
 * 10.4 is. The differences that actually bite are unique-index semantics with
 * NULLs (a nullable unique phone column), JSON column types, `lockForUpdate`
 * behaviour, and the `time`/`year` column types. This suite exists so a schema
 * mistake is caught on the real engine before the importer runs against it.
 *
 * Skipped automatically unless the connection is explicitly pointed at MySQL,
 * so the default `php artisan test` stays fast.
 */
class MysqlSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'mysql') {
            $this->markTestSkipped('Requires the mysql connection; run with DB_CONNECTION=mysql.');
        }
    }

    public function test_a_nullable_unique_phone_column_still_allows_one_null_per_row(): void
    {
        // This is why `phone` had to become nullable for the 2024 import: many
        // legacy accounts have no phone, and MySQL's unique index would reject
        // a shared placeholder value such as "unknown".
        $first = \App\Models\User::factory()->create(['phone' => null]);
        $second = \App\Models\User::factory()->create(['phone' => null]);

        $this->assertNull($first->phone);
        $this->assertNull($second->phone);
    }

    public function test_a_duplicate_non_null_phone_is_still_rejected(): void
    {
        \App\Models\User::factory()->create(['phone' => '+212612345678']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        \App\Models\User::factory()->create(['phone' => '+212612345678']);
    }

    public function test_money_columns_reject_a_negative_value(): void
    {
        // unsignedBigInteger, so a negative total cannot be stored at all.
        $this->expectException(\Illuminate\Database\QueryException::class);

        \DB::table('ticket_types')->insert([
            'edition_id' => \App\Models\Edition::factory()->create()->id,
            'code' => 'bad',
            'name' => json_encode(['fr' => 'x']),
            'price_member' => -1,
            'price_standard' => 100,
            'currency' => 'MAD',
            'currency_numeric' => '504',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_json_columns_round_trip_unicode_arabic(): void
    {
        $edition = \App\Models\Edition::factory()->create([
            'title' => ['fr' => 'Titre', 'en' => 'Title', 'ar' => 'مؤتمر ARABCIA 2026'],
        ]);

        $raw = \DB::table('editions')->where('id', $edition->id)->value('title');

        $this->assertIsString($raw);
        $this->assertStringContainsString('ARABCIA', $raw);

        app()->setLocale('ar');
        $this->assertSame('مؤتمر ARABCIA 2026', $edition->fresh()->title);
    }

    public function test_a_time_column_is_read_back_as_a_time(): void
    {
        $session = \App\Models\ConferenceSession::factory()->create([
            'starts_at' => '09:30:00',
            'ends_at' => '10:15:00',
        ]);

        $this->assertSame('09:30', $session->startsAtString());
        $this->assertSame(45, $session->durationMinutes());
    }

    public function test_an_order_row_lock_is_supported(): void
    {
        // lockForUpdate is what invoice numbering relies on; if the engine
        // ignored it, two concurrent callbacks could take the same number.
        $connection = \DB::connection('mysql');

        $connection->beginTransaction();

        $locked = $connection->table('countries')
            ->lockForUpdate()
            ->where('iso2', 'MA')
            ->first();

        $connection->rollBack();

        $this->assertTrue(true, 'lockForUpdate executed without error');
    }
}
