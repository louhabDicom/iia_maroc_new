<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Users for the ARABCIA 2026 platform.
 *
 * This is intentionally NOT the legacy WordPress `iia_users` shape. Notes on
 * the deliberate differences:
 *
 *  - `phone` is unique and required: the 2026 registration flow is OTP-gated,
 *    so a phone number is the primary verified identity alongside email.
 *  - Passwords use bcrypt (Laravel default). The legacy site used phpass
 *    portable hashes, which are re-hashed on first successful login by
 *    LegacyUserImporter + the `RehashOnLogin` listener.
 *  - Membership (formerly `iia_adhesion.etat = 'active'`) is a first-class
 *    relation, not a column, so pricing rules can reference it cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // countries is created in a later migration, so the FK is added
            // there once the target table exists.
            $table->unsignedBigInteger('country_id')->nullable()->index();

            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            // E.164-ish, digits only, + prefix optional. Unique + indexed.
            //
            // Nullable on purpose: the 2024 WordPress `iia_users` table has no
            // phone column at all, so most imported accounts arrive without one.
            // MySQL permits many NULLs under a unique index, so a placeholder
            // number is not needed. Every account that participates in the
            // 2026 flow must still verify a phone before ordering, which
            // User::canRegister() enforces.
            $table->string('phone', 32)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->string('city')->nullable();
            $table->string('organisation')->nullable();
            $table->string('job_title')->nullable();

            $table->string('locale', 5)->default('fr');
            $table->boolean('is_admin')->default(false);

            // A user is only allowed to hold an order or an active session
            // once they have proved phone ownership.
            //
            // A timestamp rather than a boolean: "when did they accept" is
            // needed for the RGPD record of consent, and a boolean cannot
            // answer that retrospectively.
            $table->timestamp('terms_accepted_at')->nullable();

            $table->string('avatar_path')->nullable();
            $table->json('preferences')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_admin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
