<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Enums\SessionFormat;
use App\Models\ConferenceSession;
use App\Models\Edition;
use App\Models\TicketType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The conference landing page.
 *
 * Every figure, date and label is read from the `editions` row or from
 * config rather than hardcoded, because the 2024 build repeated "2024" in
 * templates, in a seeder, in the mail templates and in a PDF controller, and the
 * brief asks for all of them to be replaceable in one place.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $edition = Edition::current();

        // An unpublished edition is a data-entry state, not a 404: the site stays
        // reachable and says so, instead of showing a framework error page to a
        // visitor who simply arrived early.
        if ($edition === null) {
            return view('pages.unavailable');
        }

        $locale = Locale::parse(app()->getLocale());

        $sessions = $edition->sessions()
            ->published()
            ->with(['speakers', 'track', 'room'])
            ->orderBy('session_date')
            ->orderBy('starts_at')
            ->limit(6)
            ->get();

        return view('pages.home', [
            'edition' => $edition,
            'locale' => $locale,
            'sessions' => $sessions,
            'speakers' => $edition->speakers()
                ->published()
                ->orderBy('sort_order')
                ->limit(8)
                ->get(),
            // Only the partners a visitor can see, and only the ones carrying a
            // logo. The 2024 homepage had a hand-maintained row of eight images
            // in the template, which is how a logo for a partner who had not
            // renewed stayed on the site for a year; reading the list means it
            // disappears the moment the sponsor is unpublished.
            'sponsors' => $edition->sponsors()
                ->published()
                ->inDisplayOrder()
                ->whereNotNull('logo_path')
                ->limit(8)
                ->get(),
            'ticketTypes' => TicketType::query()
                ->where('edition_id', $edition->getKey())
                // active() also honours the sales window, so a ticket type that
                // is switched off for the moment is not shown as available.
                ->active()
                ->orderBy('sort_order')
                ->get(),
            'stats' => [
                'attendees' => (int) config('conference.capacity', 300),
                // Counted from the published programme, not from a constant, so
                // the "18 ateliers" figure on the page cannot drift from the
                // programme an attendee actually sees.
                'workshops' => $edition->sessions()
                    ->published()
                    ->where('format', SessionFormat::Workshop->value)
                    ->count(),
                'labs' => $edition->sessions()
                    ->published()
                    ->where('format', SessionFormat::InnovationLab->value)
                    ->count(),
                'days' => $edition->starts_on->diffInDays($edition->ends_on) + 1,
            ],
            'currentRoute' => 'home',
        ]);
    }
}
