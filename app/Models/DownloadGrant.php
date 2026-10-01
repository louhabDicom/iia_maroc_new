<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Permission to download presentation slides.
 *
 * A grant is a real row with an expiry, a revocation and a download counter, so
 * access can be audited and withdrawn. `code_hash` is only used to confirm the
 * emailed code at the moment of granting; the plaintext is never stored.
 */
class DownloadGrant extends Model
{
    public const SCOPE_PRESENTATIONS = 'presentations';

    public const SCOPE_ALL = 'all';

    protected $fillable = [
        'user_id', 'scope', 'code_hash', 'granted_by',
        'expires_at', 'revoked_at', 'download_limit', 'download_count',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'download_limit' => 'integer',
            'download_count' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /** @param  Builder<DownloadGrant>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public static function hashCode(string $code): string
    {
        return hash('sha256', $code);
    }

    public function verifyCode(string $code): bool
    {
        return hash_equals((string) $this->code_hash, self::hashCode($code));
    }

    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return $this->hasDownloadsLeft();
    }

    public function hasDownloadsLeft(): bool
    {
        return $this->download_limit === null || $this->download_count < $this->download_limit;
    }

    public function covers(string $scope): bool
    {
        return $this->scope === self::SCOPE_ALL || $this->scope === $scope;
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }

    /**
     * Count a download, refusing past the limit.
     *
     * The guard is in the SQL WHERE clause rather than in PHP: two concurrent
     * requests can both read `download_count = 4` and both increment to 5,
     * exceeding a limit of 5.
     */
    public function recordDownload(): bool
    {
        if ($this->download_limit === null) {
            $this->increment('download_count');

            return true;
        }

        return (bool) static::query()
            ->whereKey($this->getKey())
            ->whereNull('revoked_at')
            ->where('download_count', '<', $this->download_limit)
            ->update(['download_count' => DB::raw('download_count + 1')]);
    }
}
