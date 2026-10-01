<?php

namespace App\Models;

use App\Enums\OtpDriver;
use App\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Hash;

/**
 * A one-time verification code.
 *
 * The code is never stored in readable form. `code_salt` is a per-row random
 * value and `code_hash` is SHA-256(salt + code); verification re-hashes the
 * submitted value and compares. A database dump therefore does not yield
 * usable codes.
 *
 * Every lookup is scoped by (purpose, identifier, consumed_at) so a code minted
 * for registration cannot be replayed to reset a password, and a consumed code
 * is never re-validated.
 */
class OtpCode extends Model
{
    /** @use HasFactory<\Database\Factories\OtpCodeFactory> */
    use HasFactory;

    protected $fillable = [
        'purpose', 'identifier', 'driver', 'code_hash', 'code_salt', 'length',
        'attempts', 'max_attempts', 'expires_at', 'consumed_at',
        'last_sent_at', 'request_ip', 'provider_message_id',
    ];

    protected $hidden = ['code_hash', 'code_salt'];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'driver' => OtpDriver::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'length' => 'integer',
        ];
    }

    /**
     * Store a code safely.
     *
     * @return array{code_hash: string, code_salt: string} the values to persist
     */
    public static function hashCode(string $code, ?string $salt = null): array
    {
        $salt ??= bin2hex(random_bytes(32));

        return [
            'code_salt' => $salt,
            'code_hash' => hash('sha256', $salt.$code),
        ];
    }

    public function verify(string $candidate): bool
    {
        return hash_equals($this->code_hash, hash('sha256', $this->code_salt.$candidate));
    }

    // --- State ------------------------------------------------------------

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isLocked(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    public function isUsable(): bool
    {
        return ! $this->isConsumed() && ! $this->isExpired() && ! $this->isLocked();
    }

    public function secondsRemaining(): int
    {
        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    public function markConsumed(): void
    {
        $this->forceFill(['consumed_at' => now()])->save();
    }

    public function recordFailedAttempt(): void
    {
        $this->increment('attempts');
    }

    public function markSent(string $providerMessageId = null): void
    {
        $this->forceFill([
            'last_sent_at' => now(),
            'provider_message_id' => $providerMessageId,
        ])->save();
    }

    // --- Scopes -----------------------------------------------------------

    /** @param  Builder<OtpCode>  $query */
    public function scopeFor(Builder $query, OtpPurpose $purpose, string $identifier): void
    {
        $query->where('purpose', $purpose->value)
            ->where('identifier', $identifier);
    }

    /** @param  Builder<OtpCode>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('consumed_at')->where('expires_at', '>', now());
    }

    /**
     * The most recent still-valid code for a purpose/identifier, or null.
     */
    public static function activeFor(OtpPurpose $purpose, string $identifier): ?self
    {
        return static::query()
            ->for($purpose, $identifier)
            ->active()
            ->latest('id')
            ->first();
    }

    /**
     * Invalidate every outstanding code for a purpose/identifier.
     *
     * Called on successful verification and before issuing a replacement, so
     * there is exactly one live code at a time.
     */
    public static function invalidateAll(OtpPurpose $purpose, string $identifier): void
    {
        static::query()
            ->for($purpose, $identifier)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }
}
