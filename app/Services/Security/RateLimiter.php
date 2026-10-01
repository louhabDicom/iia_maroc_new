<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter as FacadeRateLimiter;
use Illuminate\Support\Facades\Schema;

/**
 * Rate limiting with an audit trail.
 *
 * Laravel's RateLimiter already handles the counting; this wrapper adds two
 * things the 2024 site badly needed:
 *
 *  1. A named bucket API, so limits are declared once in config and cannot
 *     drift between the controller and the form request.
 *  2. A `rate_limit_hits` row per hit, so "was this phone hammered?" is a query
 *     rather than an inference from a log file.
 */
class RateLimiter
{
    /**
     * Record one hit against a bucket key.
     *
     * The IP bucket is incremented in the same call. `assertWithin()` checks
     * both the identifier bucket and the derived `{$bucket}:ip` bucket, so a hit
     * that only advanced the identifier bucket would leave the per-IP ceiling
     * permanently at zero and silently disable half the protection.
     */
    public function hit(string $bucket, string $fingerprint, ?string $ip = null): void
    {
        $key = $this->key($bucket, $fingerprint);
        $decay = $this->decayFor($bucket);

        FacadeRateLimiter::hit($key, $decay);

        if ($ip !== null && $ip !== '') {
            FacadeRateLimiter::hit($this->key($this->ipBucket($bucket), $ip), $decay);
        }

        $this->record($bucket, $fingerprint, $ip);
    }

    /**
     * How many times this fingerprint has hit the bucket.
     */
    public function attempts(string $bucket, string $fingerprint): int
    {
        return FacadeRateLimiter::attempts($this->key($bucket, $fingerprint));
    }

    public function tooManyAttempts(string $bucket, string $fingerprint, int $maxAttempts): bool
    {
        return $this->attempts($bucket, $fingerprint) >= $maxAttempts;
    }

    public function availableIn(string $bucket, string $fingerprint): int
    {
        return FacadeRateLimiter::availableIn($this->key($bucket, $fingerprint));
    }

    public function clear(string $bucket, string $fingerprint): void
    {
        FacadeRateLimiter::clear($this->key($bucket, $fingerprint));
    }

    /**
     * Assert a fingerprint is inside its budget, per identifier and per IP.
     *
     * Both limits are checked because an attacker rotating IPs to defeat a
     * per-IP limit is caught by the per-identifier limit, and vice versa.
     *
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    public function assertWithin(
        string $bucket,
        string $fingerprint,
        ?string $ip,
        int $maxAttempts,
        int $decaySeconds,
        ?string $message = null,
    ): void {
        $ipBucket = $this->ipBucket($bucket);
        $ipCeiling = $this->ipCeiling($maxAttempts, $decaySeconds);

        if ($this->tooManyAttempts($bucket, $fingerprint, $maxAttempts)) {
            $this->registerViolation($bucket, $fingerprint, $ip, 'identifier');

            throw new \Illuminate\Http\Exceptions\ThrottleRequestsException(
                $message ?? __('otp.throttled', ['seconds' => $this->availableIn($bucket, $fingerprint)]),
                null,
                ['Retry-After' => (string) $this->availableIn($bucket, $fingerprint)],
            );
        }

        if ($ip !== null && $ip !== '' && $this->tooManyAttempts($ipBucket, $ip, $ipCeiling)) {
            $this->registerViolation($bucket, $fingerprint, $ip, 'ip');

            throw new \Illuminate\Http\Exceptions\ThrottleRequestsException(
                $message ?? __('otp.throttled', ['seconds' => $this->availableIn($ipBucket, $ip)]),
                null,
                ['Retry-After' => (string) $this->availableIn($ipBucket, $ip)],
            );
        }
    }

    /**
     * The canonical bucket names.
     *
     * These live here rather than being interpolated at the call site: the
     * hourly send cap and the counter that is actually incremented have to be
     * the same string, and when both sides built their own name a change to one
     * silently disabled the other.
     */
    public function otpHourBucket(string $purpose): string
    {
        return "otp:hour:{$purpose}";
    }

    public function otpVerifyBucket(string $purpose): string
    {
        return "otp:verify:{$purpose}";
    }

    public function otpGuessBucket(string $purpose): string
    {
        return "otp:guess:{$purpose}";
    }

    private function ipBucket(string $bucket): string
    {
        return "{$bucket}:ip";
    }

    /**
     * A shared NAT, a university or a corporate proxy puts many real users behind
     * one address, so the per-IP ceiling has to be looser than the
     * per-identifier one or a whole office gets locked out at once.
     */
    private function ipCeiling(int $maxAttempts, int $decaySeconds): int
    {
        if ($decaySeconds >= 3600) {
            return $maxAttempts * 10;
        }

        return $maxAttempts * 3;
    }

    /**
     * Bucket decay in seconds.
     */
    public function decayFor(string $bucket): int
    {
        return match (true) {
            str_contains($bucket, 'ip') => 300,
            str_contains($bucket, 'hour') => 3600,
            default => 300,
        };
    }

    private function key(string $bucket, string $fingerprint): string
    {
        return $bucket.':'.hash('xxh128', $fingerprint);
    }

    private function record(string $bucket, string $fingerprint, ?string $ip): void
    {
        // Never let audit bookkeeping break the request it is recording.
        try {
            if (! Schema::hasTable('rate_limit_hits')) {
                return;
            }

            // The fingerprint is stored hashed: `rate_limit_hits` accumulates one
            // row per request, so keeping raw phone numbers there would build the
            // largest plaintext contact list in the database. The matching
            // identifier is already in security_events, masked.
            DB::table('rate_limit_hits')->insert([
                'bucket' => $bucket,
                'fingerprint' => hash('xxh128', $fingerprint),
                'hit_at' => now(),
            ]);
        } catch (\Throwable) {
            // Intentionally swallowed.
        }
    }

    private function registerViolation(string $bucket, string $fingerprint, ?string $ip, string $scope): void
    {
        app(SecurityEventLogger::class)->log('rate_limit.exceeded', [
            'level' => 'warning',
            'identifier' => $fingerprint,
            'ip' => $ip,
            'context' => ['bucket' => $bucket, 'scope' => $scope],
        ]);
    }
}
