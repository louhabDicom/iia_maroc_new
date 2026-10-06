<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Enums\SessionFormat;
use App\Models\Document;
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

    if ($edition === null) {
        return view('pages.unavailable');
    }

    $locale = Locale::parse(app()->getLocale());
    $user = $request->user();

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

    $slots = $sessions
        ->groupBy(fn ($session) => $session->session_date->toDateString())
        ->map(fn ($day) => $day
            ->groupBy(fn ($session) => $session->startsAtString())
            ->all());

    $days = $sessions
        ->map(fn ($session) => $session->session_date)
        ->unique()
        ->sort()
        ->values();

    return view('pages.home', [
        // Shared variables with PricingController
        'edition' => $edition,
        'locale' => $locale,
        'ticketTypes' => $edition->ticketTypes()
            ->active()
            ->orderBy('sort_order')
            ->get(),
        'isMember' => $user?->isActiveMember() ?? false,
        'registrationOpen' => $edition->registration_open,
        'canOrder' => $user?->canRegister() ?? false,

        // Homepage-specific variables
        'sessions' => $sessions,
        'slots' => $slots,
        'days' => $days,
        'selectedDay' => $this->selectedDay($request, $days),

        'speakers' => $edition->speakers()
            ->published()
            ->orderBy('sort_order')
            ->limit(8)
            ->get(),

        'sponsors' => $edition->sponsors()
            ->published()
            ->inDisplayOrder()
            ->whereNotNull('logo_path')
            ->limit(8)
            ->get(),

        'organisations' => $edition->organisations()
            ->published()
            ->orderBy('sort_order')
            ->get(),

        'stats' => [
            'attendees' => (int) config('conference.capacity', 300),
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

        'programmeDocument' => $edition->documents()
            ->published()
            ->ofType(Document::TYPE_PROGRAMME)
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
