<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\Edition;
use App\Models\Speaker;
use Illuminate\Contracts\View\View;

/**
 * The speaker list.
 *
 * Only published speakers appear. The brief states the 2026 line-up is still
 * being confirmed, so the view states that too rather than showing a grid of
 * placeholders that read as "these are the speakers".
 */
class SpeakersController extends Controller
{
    public function __invoke(): View
    {
        $edition = Edition::current();

        if ($edition === null) {
            return view('pages.unavailable');
        }

        return view('pages.speakers', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'keynotes' => $edition->speakers()
                ->published()
                ->keynotes()
                ->orderBy('sort_order')
                ->get(),
            'speakers' => $edition->speakers()
                ->published()
                ->where('is_keynote', false)
                // sessions is rendered on each card, so it is eager loaded.
                ->with('sessions')
                ->orderBy('sort_order')
                ->orderBy('last_name')
                ->get(),
            'currentRoute' => 'speakers',
        ]);
    }
}
