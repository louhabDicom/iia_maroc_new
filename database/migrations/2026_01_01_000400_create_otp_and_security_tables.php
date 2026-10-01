<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OTP verification, contact routing, and auditable security events.
 *
 * OTP design decisions (this is the part the brief was strict about):
 *
 *  - Codes are stored as SHA-256 hashes, never plaintext, so a database leak
 *    does not hand over live codes. A per-code random salt is mixed in.
 *  - `purpose` scopes a code (registration, password_reset, phone_change) so a
 *    code minted for one flow cannot be replayed into another.
 *  - `consumed_at` plus `attempts` give single-use + lockout semantics.
 *  - `expires_at` is an absolute timestamp, so there is no ambiguity about
 *    sliding expiry.
 *  - A partial index on (purpose, identifier, consumed_at) keeps the
 *    "latest active code for this phone" lookup cheap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            // registration | login | password_reset | phone_change | email_change
            $table->string('purpose', 32)->index();
            // phone (E.164) or email, depending on the channel.
            $table->string('identifier', 191)->index();
            $table->char('driver', 16)->default('sms');
            $table->string('code_hash', 64);
            $table->string('code_salt', 64);
            $table->unsignedTinyInteger('length')->default(6);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->string('request_ip', 45)->nullable();
            $table->string('provider_message_id', 191)->nullable();
            $table->timestamps();

            $table->index(['purpose', 'identifier', 'consumed_at'], 'otp_lookup_idx');
        });

        // Contact form: the 2026 brief requires routing by subject, so this is
        // a real queue rather than one mailbox.
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->nullOnDelete();
            // registration | sponsoring | speaker | press | other
            $table->string('subject_type', 32)->index();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 32)->nullable();
            $table->string('organisation')->nullable();
            $table->text('message');
            $table->string('locale', 5)->default('fr');
            $table->string('ip_address', 45)->nullable();
            $table->string('status', 16)->default('new')->index();
            $table->text('internal_notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('contact_routes', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 32)->unique();
            $table->string('label_fr');
            $table->string('label_en');
            $table->string('label_ar');
            $table->string('to_email');
            $table->string('cc_email')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /**
         * Append-only security audit log. Written by the OtpService, the login
         * throttle and the payment reconciler, so there is one place to answer
         * "who tried to do what, from where, and did it succeed".
         */
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 64)->index();
            $table->string('level', 16)->default('info')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('identifier')->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_agent', 512)->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
        });

        /**
         * OTP / login / contact throttling that must survive a session reset
         * and cannot be bypassed by rotating the session cookie. The framework
         * RateLimiter already does this via the cache, but a dedicated table
         * gives the admin a queryable view and an audit trail.
         */
        Schema::create('rate_limit_hits', function (Blueprint $table) {
            $table->id();
            $table->string('bucket', 64)->index();
            $table->string('fingerprint', 191)->index();
            $table->timestamp('hit_at')->index();
            $table->index(['bucket', 'fingerprint', 'hit_at'], 'rate_lookup_idx');
        });

        // Login + OTP attempt counters, used to lock an identity temporarily
        // after repeated failures regardless of source IP.
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->index();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('successful')->default(false)->index();
            $table->string('failure_reason', 64)->nullable();
            $table->timestamp('attempted_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('rate_limit_hits');
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('contact_routes');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('otp_codes');
    }
};
