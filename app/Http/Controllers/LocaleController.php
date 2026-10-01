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
 * means it has to remember the current path. The path is rebuilt from the
 * referer, but the referer is validated against APP_URL before it is used: an
 * unvalidated referer here is an open redirect, and this endpoint is reachable
 * by anyone without a session.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        // `Locale::parse()` is deliberately lenient, because it also resolves
        // inbound values (URL prefix, Accept-Language) where falling back to the
        // default is the correct outcome. Here the input is a deliberate
        // choice, so an unrecognised value is rejected instead of silently
        // switching someone to French. Validating against the enum also keeps a
        // crafted value from ever reaching the session or a translation path.
        $validated = $request->validate([
            'switch_to' => ['required', 'string', Rule::enum(Locale::class)],
        ]);

        $target = Locale::from($validated['switch_to']);

        $previous = url()->previous();

        // url()->previous() derives from the referer, which the client controls.
        // Only a same-origin referer may steer the redirect.
        $safePrevious = is_string($previous) && str_starts_with($previous, (string) config('app.url'))
            ? $previous
            : url()->route('home');

        $path = (string) (parse_url($safePrevious, PHP_URL_PATH) ?: '/');
        $query = parse_url($safePrevious, PHP_URL_QUERY);

        $segments = array_values(array_filter(explode('/', $path), fn ($segment) => $segment !== ''));

        // Strip any existing prefix, so switching does not accumulate:
        // /ar/programme -> /en/programme, never /en/ar/programme.
        if (isset($segments[0]) && Locale::tryFrom(strtolower(substr($segments[0], 0, 2))) !== null) {
            array_shift($segments);
        }

        $path = '/'.implode('/', $segments);
        $path = $target === Locale::default() ? $path : '/'.$target->value.$path;

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

        return redirect($path.($query ? '?'.$query : ''));
    }
}
