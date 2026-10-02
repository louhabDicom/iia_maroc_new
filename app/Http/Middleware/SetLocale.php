<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
 *  5. the configured default — Arabic
 *
 * `Accept-Language` is deliberately NOT consulted. The site is Arabic first: an
 * Arab confederation's annual conference, hosted in Morocco, whose primary
 * audience reads Arabic. Auto-negotiating from the browser header meant a
 * visitor whose operating system happened to be set to French or English landed
 * on a translated page before ever seeing the default — which is precisely the
 * outcome the site's language policy is meant to prevent. The browser header is
 * also a poor signal for this audience: a delegate in Riyadh or Dakar using an
 * English Windows install should still reach the Arabic site first and choose to
 * switch. Choosing is a click; being silently switched is a bug.
 *
 * The prefix is stripped from the generated URLs but the resolved locale is
 * pushed into the session, so a visitor who lands on /en/... and then clicks a
 * link without a prefix keeps reading English.
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
        Carbon::setLocale($locale->value);

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

        return Locale::default();
    }

    /** @return array<string, string> */
    private function available(): array
    {
        return Locale::options();
    }
}
