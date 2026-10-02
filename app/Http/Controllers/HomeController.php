<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Enums\SessionFormat;
use App\Models\Edition;
use App\Models\Membership;
use App\Models\TicketType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

        // A sample of the programme for the home page: the first few sessions of
        // each day, not the first few sessions overall.
        //
        // `limit(6)` on an ordered query takes the six earliest sessions, which
        // on a two-day programme is six sessions from day one — so the day tabs
        // never appeared and the page showed one day's timetable while calling
        // it "the programme". Sampling per day is what makes both tabs real.
        $sessions = $edition->sessions()
            ->published()
            ->with(['speakers', 'track', 'room'])
            ->orderBy('session_date')
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn ($session) => $session->session_date->toDateString())
            ->flatMap(fn ($day) => $day->take(3))
            ->sortBy(fn ($session) => $session->session_date->timestamp)
            ->values();

        // Group the programme by day and then by start time, which is the shape
        // the home page's day tabs need. Built here rather than in the view so
        // the tabs and the full programme page agree on what "day one" is.
        //
        // `startsAtString()` and not `->format('H:i')`: `starts_at` is a `time`
        // column, which the driver hands back as a plain string rather than a
        // Carbon instance. The model already exposes the formatted value every
        // other view uses, and using it here keeps the two pages identical.
        $slots = $sessions->groupBy(fn ($session) => $session->session_date->toDateString())
            ->map(fn ($day) => $day->groupBy(fn ($session) => $session->startsAtString())->all());

        $days = $sessions->map(fn ($session) => $session->session_date)
            ->unique()
            ->sort()
            ->values();

        return view('pages.home', [
            'edition' => $edition,
            'locale' => $locale,
            'sessions' => $sessions,
            'slots' => $slots,
            'days' => $days,
            // `?day=` on the home page selects the tab, the same way it does on
            // the programme page, so a shared link lands on the day it names.
            'selectedDay' => $this->selectedDay($request, $days),
            // Whether the visitor is entitled to the member rate. Read from the
            // Membership record, never from anything the visitor chose — the
            // rate is granted server-side at checkout either way.
            'isMember' => $this->isMember($request),
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
            // ARABCIA and IIA Maroc, for the "who is behind this" band.
            //
            // Only published rows, and read from the table rather than written
            // into the template: an organiser is a fact about the conference, and
            // the 2024 build printed two hardcoded names that outlived a change
            // of host institute.
            'organisations' => $edition->organisations()
                ->published()
                ->orderBy('sort_order')
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

            // The downloadable programme behind the landing page's "Télécharger"
            // button. Taken from the documents table rather than linked to a
            // hard-coded PDF, so publishing a revision updates the button
            // instead of leaving last year's file on the page. Null when
            // nothing is published, and the component then points at the
            // programme page rather than at a 404.
            'programmeDocument' => $edition->documents()
                ->published()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first(),
        ]);
    }

    /**
     * The day the visitor asked for, falling back to the first one published.
     *
     * A `?day=` that names no real day falls back rather than 404s: the page
     * still has a programme to show, and a shared link whose programme has
     * since been rescheduled should land on today's programme rather than an
     * error page.
     *
     * @param  Collection<int, Carbon>  $days
     */
    private function selectedDay(Request $request, $days): ?Carbon
    {
        if ($days->isEmpty()) {
            return null;
        }

        $requested = $request->query('day');

        if (is_string($requested) && $requested !== '') {
            $match = $days->first(fn ($day) => $day->isSameDay($requested));

            if ($match !== null) {
                return $match;
            }
        }

        return $days->first();
    }

    /** Whether the signed-in visitor holds an active membership. */
    private function isMember(Request $request): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        return Membership::query()
            ->where('user_id', $user->getKey())
            ->active()
            ->exists();
    }
}
