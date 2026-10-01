<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core reference data + the conference edition itself.
 *
 * The 2026 brief requires:
 *  - one canonical identity ("Conférence internationale ARABCIA 2026")
 *  - the 2024 edition preserved but moved to a dedicated archive space
 *
 * So `editions` holds every year of the event, not just 2026. Anything that
 * is edition-scoped (programme, tickets, sponsors) points at an edition, which
 * is what makes the 2024 archive a data question rather than a code branch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('iso2', 2)->unique();
            $table->char('iso3', 3)->nullable()->index();
            $table->string('name_fr');
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('phone_code', 6)->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('editions', function (Blueprint $table) {
            $table->id();
            $table->year('year')->unique();
            $table->string('code', 32)->unique();      // e.g. ARABCIA2026
            $table->string('organiser', 64);          // ARABCIA
            $table->string('host_institute', 64);     // IIA Maroc

            $table->json('title');                     // {fr,en,ar}
            $table->json('theme');                     // {fr,en,ar}
            $table->json('introduction');              // {fr,en,ar}

            $table->string('city', 128);
            $table->string('country_iso2', 2)->default('MA');
            $table->string('venue_name');
            $table->text('venue_address')->nullable();
            $table->decimal('venue_lat', 10, 7)->nullable();
            $table->decimal('venue_lng', 10, 7)->nullable();
            $table->string('venue_map_url', 512)->nullable();

            $table->date('starts_on');
            $table->date('ends_on');
            $table->json('languages')->nullable();     // ["fr","en","ar"]
            $table->json('target_audience')->nullable();

            // 'draft' | 'published' | 'archived'
            $table->string('status', 16)->default('draft')->index();
            $table->boolean('is_current')->default(false)->index();
            $table->boolean('registration_open')->default(false);

            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('organiser_contact_name')->nullable();
            $table->string('organiser_contact_email')->nullable();
            $table->string('host_contact_name')->nullable();
            $table->string('host_contact_email')->nullable();

            $table->string('hero_image_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('archive_note')->nullable();

            $table->timestamps();
        });

        // users.country_id can only be constrained now that countries exists.
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
        });

        Schema::create('organisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->nullOnDelete();
            $table->string('code', 32)->unique();      // ARABCIA | IIA_MAROC
            $table->json('name');
            $table->json('description');
            $table->json('role');                      // {fr,en,ar}
            $table->string('logo_path')->nullable();
            $table->string('logo_mono_path')->nullable();
            $table->string('website_url', 512)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisations');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
        });
        Schema::dropIfExists('editions');
        Schema::dropIfExists('countries');
    }
};
