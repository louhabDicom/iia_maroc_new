<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable, translatable site copy and downloadable documents.
 *
 * Why a `content_blocks` table instead of putting every string in the language
 * files: the brief requires the communications team to be able to revise the
 * introduction, the target-audience list, the sponsoring argument and the
 * contact routing without a deploy. Language files still hold structural UI
 * strings; this table holds editorial copy that changes between editions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->cascadeOnDelete();
            $table->string('key', 128)->index();
            $table->string('group', 48)->default('general')->index();
            $table->json('value');                    // {fr,en,ar} or a typed value
            $table->string('value_type', 24)->default('text');
            $table->string('description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->unique(['edition_id', 'key']);
        });

        /**
         * Versioned documents (programme PDF, technical sheet, sponsorship
         * dossier). The 2026 brief requires the version number and the
         * last-updated date to be visible on the download, and a "provisional"
         * flag while the scientific programme is still being confirmed.
         */
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->cascadeOnDelete();
            $table->string('type', 48)->index();     // programme | technical_sheet | sponsorship_dossier | registration_form
            $table->json('title');
            $table->string('version', 24)->default('1.0');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->char('mime_type', 128)->nullable();
            $table->boolean('is_provisional')->default(false);
            $table->json('available_locales')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['edition_id', 'type']);
        });

        /**
         * Speaker presentation slides. Gated behind a per-user grant rather than
         * the single hardcoded access code in the 2024 build
         * (validate_access_code.php compared against a literal string that was
         * committed to the repository).
         */
        Schema::create('presentation_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->foreignId('speaker_id')->nullable()->constrained('speakers')->nullOnDelete();
            $table->foreignId('conference_session_id')->nullable()->constrained('conference_sessions')->nullOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->char('mime_type', 128)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            // Main talk first, then supplementary material.
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['conference_session_id', 'sort_order']);
        });

        Schema::create('download_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('scope', 64);             // presentations | all
            $table->string('code_hash', 64);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedSmallInteger('download_limit')->nullable();
            $table->unsignedSmallInteger('download_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_grants');
        Schema::dropIfExists('presentation_files');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('content_blocks');
    }
};
