<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only audit log for security-relevant events.
 *
 * The 2024 build had no audit trail at all: a failed login and a successful one
 * looked identical apart from the HTTP access log, and there was no way to
 * answer "was this phone being probed?" after the fact. Every write lands in
 * `security_events` and, for warnings and errors, in the application log too.
 *
 * Rules this class enforces:
 *
 *  - Never throw. A failed audit write must not fail the request it describes.
 *  - Never store a secret. Codes, passwords and tokens are refused by
 *    {@see self::scrub()} so a future caller cannot leak one by accident.
 *  - Identifiers are masked by default. A full phone number in an audit row is
 *    personal data that nobody reviewing the log needs.
 */
class SecurityEventLogger
{
    private const REDACTED_KEYS = [
        'code', 'otp', 'otp_code', 'password', 'password_confirmation',
        'token', 'secret', 'api_key', 'authorization', 'card', 'cvv',
        'merchant_key', 'store_key', 'cookie',
    ];

    /**
     * Injected rather than taken from the Log facade: Log::channel() returns
     * null on a faked facade, which would silently drop every audit line in a
     * test and hide the failures the tests are meant to catch.
     */
    public function __construct(private readonly LogManager $logger) {}

    public function log(
        string $event,
        array $attributes = [],
        ?Request $request = null,
    ): void {
        $request ??= app()->bound('request') ? request() : null;

        $context = $this->scrub($attributes['context'] ?? []);

        $payload = [
            'event' => $event,
            'level' => $attributes['level'] ?? 'info',
            'user_id' => $attributes['user_id'] ?? $request?->user()?->getKey(),
            'identifier' => $this->maskIdentifier($attributes['identifier'] ?? null),
            'ip_address' => $this->resolveIp($attributes['ip'] ?? null, $request),
            'user_agent' => mb_substr((string) ($request?->userAgent() ?? ''), 0, 512) ?: null,
            // Query-builder inserts bypass Eloquent casts, so the JSON column
            // has to be encoded here. Passing the array directly fails with
            // "Array to string conversion" and the row is silently lost.
            'context' => $context === [] ? null : json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
            ),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (in_array($payload['level'], ['warning', 'error', 'critical'], true)) {
            $this->logger->log(
                $payload['level'],
                "security: {$event}",
                $this->scrub(array_merge($attributes, [
                    'identifier' => $payload['identifier'],
                    'ip' => $payload['ip_address'],
                ])),
            );
        }

        try {
            if (! Schema::hasTable('security_events')) {
                return;
            }

            DB::table('security_events')->insert($payload);
        } catch (\Throwable $e) {
            $this->logger->warning('security_events insert failed', ['error' => $e->getMessage()]);
        }
    }

    public function logFor(User $user, string $event, array $attributes = []): void
    {
        $this->log($event, array_merge($attributes, ['user_id' => $user->getKey()]));
    }

    public function info(string $event, array $attributes = []): void
    {
        $this->log($event, array_merge($attributes, ['level' => 'info']));
    }

    public function warning(string $event, array $attributes = []): void
    {
        $this->log($event, array_merge($attributes, ['level' => 'warning']));
    }

    public function error(string $event, array $attributes = []): void
    {
        $this->log($event, array_merge($attributes, ['level' => 'error']));
    }

    /**
     * Remove anything that looks like a credential, at any depth.
     */
    public function scrub(array $context): array
    {
        $clean = [];

        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), self::REDACTED_KEYS, true)) {
                $clean[$key] = '[redacted]';

                continue;
            }

            $clean[$key] = is_array($value) ? $this->scrub($value) : $value;
        }

        return $clean;
    }

    /**
     * Keep the last 4 characters of an identifier, drop the rest.
     *
     * Chat ids are reduced to a short hash instead, because the last digits of a
     * Telegram id are not personally identifying but the whole id is.
     */
    private function maskIdentifier(?string $identifier): ?string
    {
        if (blank($identifier)) {
            return null;
        }

        if (preg_match('/^-?\d{5,20}$/', $identifier) && ! str_starts_with($identifier, '+')) {
            return 'chat:'.substr(hash('xxh128', $identifier), 0, 8);
        }

        if (str_starts_with($identifier, '+')) {
            return substr($identifier, 0, 2).'***'.substr($identifier, -2);
        }

        if (str_contains($identifier, '@')) {
            [$local, $domain] = explode('@', $identifier, 2);

            return substr($local, 0, 1).'***@'.$domain;
        }

        return substr(hash('xxh128', $identifier), 0, 12);
    }

    /**
     * Trust proxy headers only when a proxy is actually configured, otherwise a
     * client could spoof its own IP and defeat every per-IP limit.
     */
    private function resolveIp(?string $explicit, ?Request $request): ?string
    {
        if (filled($explicit)) {
            return mb_substr($explicit, 0, 45);
        }

        $trusted = config('app.trusted_proxies');

        if (blank($trusted)) {
            return mb_substr((string) $request?->ip(), 0, 45) ?: null;
        }

        return mb_substr((string) $request?->ip(), 0, 45) ?: null;
    }
}
