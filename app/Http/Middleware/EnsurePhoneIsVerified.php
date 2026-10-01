<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require a verified phone before an authenticated action is allowed.
 *
 * This is the gate that the 2024 build was missing: registration accepted a
 * form, created a session and dropped the person straight into checkout with no
 * proof of contact, so an order could be placed for somebody else's number.
 *
 * It is separate from the OTP service: that code decides whether a number is
 * real, this decides whether a session is allowed to act on it.
 */
class EnsurePhoneIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->hasVerifiedPhone()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('auth.phone_not_verified'),
            ], 403);
        }

        // Keep the intended destination so the person lands where they were
        // heading once verified, instead of on a generic dashboard.
        return redirect()
            ->route('verification.notice')
            ->with('intended', $request->fullUrl());
    }
}
