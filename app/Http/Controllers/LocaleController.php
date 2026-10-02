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
 * Changes language and returns the visitor to the page they came from.
 * The return path comes from the form itself (`return_path`, sent by the
 * switcher partial), validated as a plain relative path. The referer is only a
 * fallback, because behind some hosts/proxies it cannot be matched to the
 * request host and every switch would land on the home page.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'switch_to' => ['required', 'string', Rule::enum(Locale::class)],
        ]);

        $target = Locale::from($validated['switch_to']);

        $path = $this->safeReturnPath($request);

        $segments = array_values(array_filter(explode('/', $path), fn ($segment) => $segment !== ''));

        // Strip any existing prefix: /fr/programme -> /en/programme, never
        // /en/fr/programme. The WHOLE segment is compared, not its first two
        // letters, so "archive" is not mistaken for "ar".
        if (isset($segments[0]) && Locale::tryFrom(strtolower($segments[0])) !== null) {
            array_shift($segments);
        }

        $path = '/'.implode('/', $segments);

        // Every language, Arabic included, gets its own prefix: /ar/programme,
        // /fr/programme, /en/programme. The home page is /ar, /fr, /en.
        $path = rtrim('/'.$target->value.$path, '/');

        // Keep the query string (e.g. ?day=2026-12-17).
        $query = $this->returnQuery($request);
        if ($query !== '') {
            $path .= '?'.$query;
        }

        $request->session()->put('locale', $target->value);
        $request->session()->put('locale_explicit', true);

        $user = $request->user();

        if ($user !== null && $user->locale !== $target->value) {
            $user->forceFill(['locale' => $target->value])->save();
        }

        return redirect($path);
    }

    /**
     * The page to return to, as a path RELATIVE to the application root
     * (no install subfolder, so redirect() can add it exactly once).
     */
    private function safeReturnPath(Request $request): string
    {
        // 1. The path the form sent. Accept only a plain relative path: no
        //    scheme, no host, no "//", no backslash, no "..".
        $sent = $request->input('return_path');

        if (is_string($sent) && $this->isPlainPath($sent)) {
            return '/'.ltrim($sent, '/');
        }

        // 2. Fallback: the stored previous URL / referer, same host only.
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

    private function returnQuery(Request $request): string
    {
        $query = $request->input('return_query');

        // Query strings are data, not a destination: only printable URL-safe
        // characters, so nothing can smuggle a new target in.
        return is_string($query) && preg_match('/^[A-Za-z0-9_\-=&%.,:+\[\]]*$/', $query)
            ? $query
            : '';
    }

    private function isPlainPath(string $path): bool
    {
        return $path === ''
            || (
                preg_match('#^[A-Za-z0-9/_\-.%]*$#', $path) === 1
                && ! str_contains($path, '..')
                && ! str_contains($path, '//')
            );
    }
}