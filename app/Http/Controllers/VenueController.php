<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\Edition;
use App\Models\Room;
use Illuminate\Contracts\View\View;

/**
 * Venue, dates and practical information.
 *
 * Everything here is edition data, so the 2027 edition changes this page without
 * a code change. The 2024 build had "Casablanca" hardcoded in a template while
 * the venue column said something else.
 */
class VenueController extends Controller
{
    public function __invoke(): View
    {
        $edition = Edition::current();

        if ($edition === null) {
            return view('pages.unavailable');
        }

        return view('pages.venue', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'rooms' => $edition->rooms()
                ->published()
                ->orderBy('sort_order')
                ->get(),
            'currentRoute' => 'venue',
        ]);
    }
}
