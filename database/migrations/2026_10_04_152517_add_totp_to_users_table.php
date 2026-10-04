<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TOTP enrolment, replacing phone-number verification.
 *
 * The secret is nullable and that is the whole point: a secret written before
 * the user has proved they saved it is *pending*, not active. `totp_confirmed_at`
 * is the only thing that makes it usable, which is why the two columns are never
 * set by a single write in the application — a crash between them would
 * otherwise leave an account that can neither verify nor re-enrol.
 *
 * `totp_secret` is text rather than a binary blob because it is base32 — the
 * format every authenticator app speaks — and is encrypted at rest by the model
 * cast, not by the column type. See App\Models\User::casts().
 *
 * Recovery codes are stored as an array of hashes. The plain values are shown
 * exactly once, at enrolment, and cannot be recovered from here afterwards,
 * which is the same rule OtpCode already follows for its one-time codes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('totp_secret')->nullable()->after('password');
            $table->timestamp('totp_confirmed_at')->nullable()->after('totp_secret');
            $table->json('totp_recovery_codes')->nullable()->after('totp_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['totp_secret', 'totp_confirmed_at', 'totp_recovery_codes']);
        });
    }
};
