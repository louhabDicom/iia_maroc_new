<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require a working authenticator before an authenticated action is allowed.
 *
 * This is the gate that the 2024 build was missing: registration accepted a form,
 * created a session and dropped the person straight into checkout with nothing
 * proven, so an order could be placed against an account nobody controlled.
 *
 * It is separate from the TOTP service: that service decides whether a code is
 * correct, this decides whether a session is allowed to act without one.
 *
 * Reachable while enrolled-but-unconfirmed is the one state this lets through —
 * no, it is not: an unconfirmed account is sent to enrolment. What it does let
 * through is a *missing* secret entirely, because `config('totp.required')` may
 * be off for a deployment that has not finished wiring it up.
 */
class EnsureTotpIsConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->hasConfirmedTotp()) {
            return $next($request);
        }

        // With the gate switched off, an account that never enrolled is allowed
        // through rather than locked out of its own order.
        if (! config('totp.required', true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('totp.not_enrolled'),
            ], 403);
        }

        // Keep the intended destination so the person lands where they were
        // heading once enrolled, instead of on a generic dashboard.
        return redirect()
            ->route('totp.setup')
            ->with('intended', $request->fullUrl());
    }
}
