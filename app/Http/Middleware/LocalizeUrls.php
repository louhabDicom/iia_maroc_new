<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Give the URL generator a language prefix.
 *
 * All public routes live under an optional `{locale?}` segment, so the generator
 * has to know which form to emit. Without this, `route('programme')` would drop
 * the prefix on an Arabic page and the visitor would silently flip to French.
 *
 * A 301 redirect is issued for an unprefixed request to a prefixed canonical
 * URL, so a page has exactly one address for search engines and for sharing.
 */
class LocalizeUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route()?->parameter('locale');

        if (is_string($locale) && filled($locale)) {
            URL::defaults(['locale' => $locale]);
        }

        return $next($request);
    }
}
