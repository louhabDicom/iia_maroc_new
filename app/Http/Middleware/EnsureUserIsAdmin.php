<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require an administrator account.
 *
 * The 2024 site had no staff area at all. Programme edits, speaker decisions
 * and order cancellations were performed by logging into the front end as a
 * member and hoping the operator remembered which buttons were dangerous — the
 * reason `iia_emble` users existed without anything gating them. Anything that
 * changes money, published content or another person's data belongs behind this
 * rather than behind a hidden link in the member area.
 *
 * The refusal is a 403 rather than a redirect to the dashboard. A signed-in
 * member who guesses /admin is not a member whose session expired, and sending
 * them to a sign-in page would invite them to try again with a password. `auth`
 * already handles the unauthenticated case; this handles the authenticated one.
 *
 * It runs *after* `auth`, so `$request->user()` is guaranteed non-null by the
 * time this is reached when both are applied.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Belt and braces: `auth` is applied on the same group, but a route
        // someone later adds this alias to on its own would otherwise call
        // isAdmin() on null. Treating that as a refusal, not an error page,
        // keeps a mis-wired route closed rather than throwing.
        if ($user === null) {
            return $this->refuse($request);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        return $this->refuse($request);
    }

    private function refuse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('admin.forbidden'),
            ], 403);
        }

        // 403 rather than a redirect: there is nowhere for a signed-in
        // non-administrator to go, and rendering the dashboard shell with an
        // empty body would look like a bug rather than a refusal.
        abort(403, __('admin.forbidden'));
    }
}
