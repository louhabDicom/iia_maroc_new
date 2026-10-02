<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The language switcher.
 *
 * It changes language and returns the visitor to the page they came from, which
 * means it has to remember the current path. Which URL that is, and how it is
 * decided, is the whole of this class's responsibility — see safeReturnPath().
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        // Validating against the enum keeps a crafted value from ever reaching
        // the session or a translation path.
        $validated = $request->validate([
            'switch_to' => ['required', 'string', Rule::enum(Locale::class)],
        ]);

        $target = Locale::from($validated['switch_to']);

        $path = $this->safeReturnPath($request);

        $segments = array_values(array_filter(explode('/', $path), fn ($segment) => $segment !== ''));

        // Strip any existing prefix, so switching does not accumulate:
        // /fr/programme -> /en/programme, never /en/fr/programme.
        //
        // The WHOLE first segment is compared, not its first two letters:
        // substr($segment, 0, 2) would read "archive" as "ar" and drop it.
        if (isset($segments[0]) && Locale::tryFrom(strtolower($segments[0])) !== null) {
            array_shift($segments);
        }

        $path = '/'.implode('/', $segments);

        // The default language is unprefixed, so switching *to* it strips the
        // segment and switching *from* it adds one. `prefix()` is the single
        // source of truth for that rule, shared with the router's own pattern.
        $prefix = $target->prefix();
        $path = $prefix === null ? $path : '/'.$prefix.$path;

        // The chosen language is sticky: it survives a prefix-less URL instead
        // of snapping back to the default on the next click.
        //
        // The flag is a separate top-level key, NOT `locale.explicit`. Session
        // writes use dot notation as an array path, so `put('locale.explicit', …)`
        // would replace the `locale` string with `['explicit' => true]` and the
        // locale would be lost.
        $request->session()->put('locale', $target->value);
        $request->session()->put('locale_explicit', true);

        $user = $request->user();

        if ($user !== null && $user->locale !== $target->value) {
            $user->forceFill(['locale' => $target->value])->save();
        }

        return redirect($path);
    }

    /**
     * The page to return the visitor to, as a path on this site, RELATIVE to the
     * application root.
     *
     * This endpoint is reachable by anyone without a session, so whatever
     * address it redirects to is attacker-influenced. An unvalidated referer
     * here is an open redirect, so the guard is a same-ORIGIN check done by
     * comparing hosts (not by string-matching config('app.url')): a staging
     * domain, www vs apex, http vs https behind a proxy or a different port are
     * configuration differences, not attacks, and must not cost a visitor their
     * place on the page. `$request->getHost()` is the host this request was
     * accepted for, so a crafted `Referer: https://evil.test/` still fails.
     *
     * The session's stored previous URL is preferred over the Referer header,
     * because it is recorded by this application rather than supplied by the
     * client. Returns '/' when neither is same-origin.
     *
     * SUBFOLDER INSTALLS: when the app is served from a subfolder (for example
     * https://diprojets.com/iia_maroc_new/public), the referer's path contains
     * that subfolder. redirect()/url() add it back themselves, so it is removed
     * here — otherwise it is doubled: /iia_maroc_new/public/iia_maroc_new/public.
     * On a domain root the base path is empty and nothing changes.
     */
    private function safeReturnPath(Request $request): string
    {
        $candidates = array_filter([
            $request->hasSession() ? $request->session()->previousUrl() : null,
            $request->headers->get('referer'),
        ]);

        $base = $request->getBasePath();

        foreach ($candidates as $candidate) {
            $host = parse_url($candidate, PHP_URL_HOST);

            if ($host === null || strcasecmp($host, $request->getHost()) !== 0) {
                continue;
            }

            $path = parse_url($candidate, PHP_URL_PATH);

            if (is_string($path) && $path !== '') {
                if ($base !== '' && str_starts_with($path, $base)) {
                    $path = substr($path, strlen($base)) ?: '/';
                }

                return $path;
            }
        }

        return '/';
    }
}