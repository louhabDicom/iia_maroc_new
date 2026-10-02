<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Operator notes on sponsorship enquiries.
 *
 * `sponsorship_enquiries` shipped with the prospect's own `message` and no
 * separate field for what the organiser wrote back. The obvious workaround —
 * appending to `message` — is destructive: it interleaves two different voices
 * in one column, so the record of what was actually asked for is no longer
 * readable after the first follow-up. `contact_messages` already separates the
 * two as `message` / `internal_notes`, and this brings the same shape to
 * enquiries so a query can rely on it.
 *
 * Nullable rather than defaulting to an empty string: "no notes yet" and "notes
 * that say nothing" should not be indistinguishable when reading the queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_enquiries', function (Blueprint $table): void {
            $table->text('internal_notes')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_enquiries', function (Blueprint $table): void {
            $table->dropColumn('internal_notes');
        });
    }
};