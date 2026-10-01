<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Programme: rooms, speakers, sessions, and the 2026 workshop axes.
 *
 * The 2026 brief asks for a slot-and-room view with at most four parallel
 * sessions, plus the three workshop axes (IA & transformation / Resilience /
 * Auditor of tomorrow). Modelling `starts_at`/`ends_at` as datetimes rather
 * than a date + time string is what makes the parallel-track grid computable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->string('code', 32);              // ai-transformation | resilience | future-auditor
            $table->json('name');                    // {fr,en,ar}
            $table->json('description')->nullable();
            $table->string('colour', 16)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique(['edition_id', 'code']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->string('code', 32);
            $table->json('name');                    // {fr,en,ar}
            $table->unsignedSmallInteger('capacity')->nullable();
            // 0 = ground floor. The programme grid groups by room in this order,
            // so the column is required by the views, not decorative.
            $table->unsignedSmallInteger('floor')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique(['edition_id', 'code']);
        });

        Schema::create('speakers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->json('job_title')->nullable();
            $table->string('organisation')->nullable();
            $table->char('country_iso2', 2)->nullable();
            // Brief: biography must be exactly three sentences.
            $table->text('biography')->nullable();
            $table->unsignedTinyInteger('biography_sentences')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('email')->nullable();
            $table->string('linkedin_url', 512)->nullable();
            $table->json('talk_title')->nullable();
            // 'pending' until the speaker signs the publication agreement.
            $table->string('status', 16)->default('pending')->index();
            $table->boolean('is_keynote')->default(false);
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['edition_id', 'status']);
        });

        // Named `conference_sessions`: `sessions` is reserved by the
        // framework's HTTP session store.
        Schema::create('conference_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->foreignId('track_id')->nullable()->constrained('tracks')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();

            // opening | keynote | plenary | panel | workshop | innovation_lab | award | break
            $table->string('format', 32)->index();
            $table->json('title');
            $table->json('summary')->nullable();
            $table->json('objectives')->nullable();

            $table->date('session_date')->index();
            $table->time('starts_at');
            $table->time('ends_at');

            // 'fr' | 'en' | 'ar' | 'fr,en,ar' style CSV for bilingual sessions
            $table->string('language', 16)->default('fr');
            $table->boolean('interpretation_available')->default(false);

            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['edition_id', 'session_date', 'starts_at']);
            $table->index(['edition_id', 'format']);
        });

        // Named `session_speakers` to avoid colliding with the framework's
        // HTTP `sessions` table.
        Schema::create('session_speakers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_session_id')->constrained('conference_sessions')->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained('speakers')->cascadeOnDelete();
            // Speaker may co-chair or moderate a panel.
            $table->string('role', 32)->default('speaker');
            // Presentation order: a chair or moderator must be listed first,
            // and the alphabetical fallback is wrong for a panel.
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['conference_session_id', 'speaker_id']);
            $table->index(['conference_session_id', 'sort_order']);
        });

        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->nullOnDelete();
            // platinum | gold | silver | bronze | partner | institutional | previous
            $table->string('tier', 32)->index();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->string('logo_mono_path')->nullable();
            $table->string('website_url', 512)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sponsoring_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->string('code', 32);
            $table->json('name');
            $table->json('benefits');                 // array of {fr,en,ar}
            $table->unsignedInteger('price_from')->nullable();
            $table->string('currency', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['edition_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsoring_packages');
        Schema::dropIfExists('sponsors');
        Schema::dropIfExists('session_speakers');
        Schema::dropIfExists('conference_sessions');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('speakers');
        Schema::dropIfExists('tracks');
    }
};
