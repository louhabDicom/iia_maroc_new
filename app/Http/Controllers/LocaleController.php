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
        // `Locale::parse()` is deliberately lenient, because it also resolves
        // inbound values (a URL prefix) where falling back to the default is the
        // correct outcome. Here the input is a deliberate choice, so an
        // unrecognised value is rejected instead of silently switching someone
        // to the wrong language. Validating against the enum also keeps a
        // crafted value from ever reaching the session or a translation path.
        $validated = $request->validate([
            'switch_to' => ['required', 'string', Rule::enum(Locale::class)],
        ]);

        $target = Locale::from($validated['switch_to']);

        $path = $this->safeReturnPath($request);

        $segments = array_values(array_filter(explode('/', $path), fn ($segment) => $segment !== ''));

        // Strip any existing prefix, so switching does not accumulate:
        // /ar/programme -> /en/programme, never /en/ar/programme.
        if (isset($segments[0]) && Locale::tryFrom(strtolower(substr($segments[0], 0, 2))) !== null) {
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
     * The page to return the visitor to, as a path on this site.
     *
     * This endpoint is reachable by anyone without a session, so whatever
     * address it redirects to is attacker-influenced. An unvalidated referer
     * here is an open redirect: a link that lands the visitor on a perfect copy
     * of a login page is the whole attack, and it costs the phishing page
     * nothing to set up.
     *
     * The guard is therefore a same-ORIGIN check — and it is done by comparing
     * HOSTS rather than by string-matching `config('app.url')`.
     *
     * That is not a stylistic preference. Comparing against APP_URL means the
     * comparison silently fails — and the switcher silently dumps the visitor
     * on the home page instead of the page they were reading — whenever the
     * request host is not byte-identical to APP_URL: a staging or preview
     * domain, `www` against a bare apex, http against https behind a proxy, or
     * a different port in local development. Every one of those is a
     * configuration difference rather than an attack, and none of them should
     * cost a visitor their place on the page.
     *
     * The host still cannot be spoofed by the client, which is the property the
     * old check was providing: `$request->getHost()` is the host this request
     * was actually accepted for, so a crafted
     * `Referer: https://evil.test/` still fails the comparison and the visitor
     * lands on the home page instead.
     *
     * The session's stored previous URL is preferred over the Referer header,
     * because it is recorded by this application rather than supplied by the
     * client. Returns '/' when neither is same-origin: the home page, which is
     * a safe landing rather than an error.
     */
    private function safeReturnPath(Request $request): string
    {
        $candidates = array_filter([
            $request->hasSession() ? $request->session()->previousUrl() : null,
            $request->headers->get('referer'),
        ]);

        foreach ($candidates as $candidate) {
            $host = parse_url($candidate, PHP_URL_HOST);

            if ($host === null || strcasecmp($host, $request->getHost()) !== 0) {
                continue;
            }

            $path = parse_url($candidate, PHP_URL_PATH);

            if (is_string($path) && $path !== '') {
                return $path;
            }
        }

        return '/';
    }
}
