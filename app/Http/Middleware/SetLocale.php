<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the active language for the request.
 *
 * Precedence, most explicit first:
 *
 *  1. the URL prefix      ({locale}/programme) — shareable and cacheable
 *  2. an explicit query   (?lang=ar)         — the language switcher
 *  3. the session                                   — a returning visitor
 *  4. the signed-in user's stored preference
 *  5. Accept-Language                                — a first-time visitor
 *  6. the configured default
 *
 * The prefix is stripped from the generated URLs but the resolved locale is
 * pushed into the session, so a visitor who lands on /ar/... and then clicks a
 * link without a prefix keeps reading Arabic.
 *
 * Arabic is not a variant of English: it needs `dir="rtl"` on <html> and
 * `app()->setLocale('ar')` for the correct month names. Both happen here rather
 * than in a Blade template, so a controller, a validation message and a PDF all
 * agree.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale->value);

        // Carbon follows the app locale for translatedFormat().
        \Illuminate\Support\Carbon::setLocale($locale->value);

        // `locale_explicit` is a top-level key, not `locale.explicit`: session
        // writes are dot-notation array paths, so a dotted write would clobber
        // the `locale` value with an array.
        if ($request->hasSession() && ! $request->session()->has('locale_explicit')) {
            $request->session()->put('locale', $locale->value);
        }

        // Available to every view without each one calling Auth::user().
        view()->share('currentLocale', $locale);
        view()->share('isRtl', $locale->isRtl());
        view()->share('availableLocales', $this->available());

        return $next($request);
    }

    private function resolve(Request $request): Locale
    {
        $prefix = $request->route()?->parameter('locale');

        if ($prefix !== null) {
            return Locale::parse($prefix);
        }

        $query = $request->query('lang');

        if (is_string($query) && filled($query)) {
            $locale = Locale::parse($query);

            // A switcher click is an explicit choice, so it survives the
            // session and is not overwritten by a prefix-less request.
            if ($request->hasSession()) {
                $request->session()->put('locale', $locale->value);
                $request->session()->put('locale_explicit', true);
            }

            return $locale;
        }

        if ($request->hasSession()) {
            $stored = $request->session()->get('locale');

            if (filled($stored)) {
                return Locale::parse($stored);
            }
        }

        $user = $request->user();

        if ($user instanceof User && filled($user->locale)) {
            return Locale::parse($user->locale);
        }

        return $this->fromAcceptHeader($request);
    }

    /**
     * Match Accept-Language against the available locales.
     *
     * Quality values are honoured, because a browser sending
     * "fr-FR,fr;q=0.9,en;q=0.8,ar;q=0.7" should not end up on English just
     * because `ar` appears earlier in the configured list.
     */
    private function fromAcceptHeader(Request $request): Locale
    {
        $header = (string) $request->header('Accept-Language', '');

        if ($header === '') {
            return Locale::default();
        }

        $available = array_keys($this->available());
        $best = null;
        $bestQuality = -1.0;

        foreach (explode(',', $header) as $part) {
            $bits = explode(';', trim($part));
            $tag = strtolower(trim($bits[0]));

            if ($tag === '' || $tag === '*') {
                continue;
            }

            $quality = 1.0;

            foreach (array_slice($bits, 1) as $parameter) {
                if (preg_match('/^q=([0-9.]+)$/', trim($parameter), $m)) {
                    $quality = (float) $m[1];
                }
            }

            $primary = substr($tag, 0, 2);

            if (! in_array($primary, $available, true)) {
                continue;
            }

            if ($quality > $bestQuality) {
                $bestQuality = $quality;
                $best = $primary;
            }
        }

        return $best !== null
            ? Locale::parse($best)
            : Locale::default();
    }

    /** @return array<string, string> */
    private function available(): array
    {
        return Locale::options();
    }
}
