<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive user columns needed by the account flow.
 *
 * Kept as a separate migration rather than folded into the users table creation
 * so the intent is readable: the base table shipped with the OTP-gated design,
 * these two arrived with the sign-in screens.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Last successful sign-in. Needed for the security review trail and
            // for "is this account still in use?" when auditing, which was not
            // answerable on the 2024 tables.
            $table->timestamp('last_login_at')->nullable()->after('terms_accepted_at');

            // When the last code was requested, so an account can be recognised
            // as abandoned mid-registration and cleaned up.
            $table->timestamp('last_verification_sent_at')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['last_login_at', 'last_verification_sent_at']);
        });
    }
};
