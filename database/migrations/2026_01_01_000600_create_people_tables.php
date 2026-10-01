<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Team members and speaker/sponsor submission forms.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->cascadeOnDelete();
            // organising_committee | scientific | logistics | press | host_institute
            $table->string('team', 48)->default('organising_committee')->index();
            $table->string('name');
            $table->json('role');
            $table->string('organisation')->nullable();
            $table->json('bio')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('linkedin_url', 512)->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['edition_id', 'is_published']);
        });

        /**
         * Call-for-speakers submissions. The brief says the presentation-upload
         * form must NOT be public at launch ("à mettre à la fin de la
         * conférence"), so this table exists but the route is admin-disabled
         * until the CFP opens.
         */
        Schema::create('speaker_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->string('organisation')->nullable();
            $table->string('job_title')->nullable();
            $table->char('country_iso2', 2)->nullable();
            // keynote | plenary | panel | workshop | innovation_lab
            $table->string('requested_format', 32);
            $table->foreignId('requested_track_id')->nullable()->constrained('tracks')->nullOnDelete();
            $table->string('session_title');
            $table->text('abstract');
            $table->text('outline')->nullable();
            $table->string('status', 24)->default('submitted')->index();
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        /**
         * Sponsorship enquiries. Per the 2026 brief, sponsoring leads route to
         * a named contact at ARABCIA / IIA Maroc rather than a generic form.
         */
        Schema::create('sponsorship_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('sponsoring_packages')->nullOnDelete();
            $table->string('company');
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone', 32)->nullable();
            $table->string('website_url', 512)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 24)->default('new')->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });

        /**
         * Newsletter opt-in, double opt-in. Consent is recorded with the
         * source and the exact text shown, because the brief covers three
         * languages and consent must be demonstrable per language version.
         */
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->char('locale', 5)->default('fr');
            $table->string('status', 16)->default('pending')->index();
            $table->string('consent_locale', 5)->default('fr');
            $table->string('consent_ip', 45)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('sponsorship_enquiries');
        Schema::dropIfExists('speaker_submissions');
        Schema::dropIfExists('team_members');
    }
};
