<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\ConferenceSession;
use App\Models\Document;
use App\Models\Edition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The scientific programme.
 *
 * Grouped by day and, within a day, by start time, with parallel sessions kept
 * side by side rather than flattened into a single list. The 2024 build rendered
 * one long table, which made the four-room grid unreadable — and made it
 * impossible to see that a room was double-booked.
 */
class ProgrammeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $edition = Edition::current();

        if ($edition === null) {
            return view('pages.unavailable');
        }

        $locale = Locale::parse(app()->getLocale());

        $day = $this->resolveDay($request, $edition);

        // Every day is fetched, not just the selected one: the day tabs carry
        // each day's grid in the document and the `?day=` in the address bar
        // only decides which panel is open, so switching a day is instant
        // instead of a reload. Without a script the tabs are real links and the
        // server renders the same page with the chosen day open.
        $sessions = $edition->sessions()
            ->published()
            // room() and track() are rendered for every row, so both are loaded
            // up front: without this the page runs one query per session.
            ->with(['room', 'track', 'speakers'])
            ->orderBy('session_date')
            ->orderBy('starts_at')
            ->orderBy('room_id')
            ->get();

        return view('pages.programme', [
            'edition' => $edition,
            'locale' => $locale,
            'sessions' => $sessions,
            // Grouped once here rather than in the template: nested loops with
            // array_filter inside Blade is where view logic goes wrong quietly.
            'slots' => $this->groupIntoSlots($sessions),
            'selectedDay' => $day,
            'days' => $this->daysOf($edition),
            // The downloadable programme behind the page's PDF link. Same
            // resolution as the landing page button, so a published document
            // wins over the file in `public/` and both pages always agree.
            'programmeDocument' => $edition->documents()
                ->published()
                ->ofType(Document::TYPE_PROGRAMME)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first(),
            'currentRoute' => 'programme',
        ]);
    }

    /**
     * The requested day, defaulting to the first.
     *
     * A day outside the edition is ignored rather than 404'd: a stale bookmark
     * or a hand-edited query string should land on the programme, not an error.
     */
    private function resolveDay(Request $request, Edition $edition): ?Carbon
    {
        $requested = $request->query('day');

        foreach ($this->daysOf($edition) as $day) {
            if ($requested !== null && $day->isSameDay($requested)) {
                return $day;
            }
        }

        return $this->daysOf($edition)[0] ?? null;
    }

    /** @return list<Carbon> */
    private function daysOf(Edition $edition): array
    {
        $days = [];

        for ($date = $edition->starts_on->copy(); $date->lte($edition->ends_on); $date->addDay()) {
            $days[] = $date->copy();
        }

        return $days;
    }

    /**
     * Day => start time => sessions.
     *
     * @param  Collection<int, ConferenceSession>  $sessions
     * @return array<string, array<string, Collection<int, ConferenceSession>>>
     */
    private function groupIntoSlots(Collection $sessions): array
    {
        $slots = [];

        foreach ($sessions as $session) {
            $slots[$session->session_date->toDateString()][$session->startsAtString()][] = $session;
        }

        return $slots;
    }
}
