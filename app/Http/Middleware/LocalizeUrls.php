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
 * the prefix on an English page and the visitor would silently flip to Arabic.
 *
 * The default language (Arabic) has no prefix at all, so there is nothing to set
 * for it: the route simply matches with the segment absent.
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
