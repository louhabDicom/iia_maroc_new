<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Participant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Attendees and the desk check-in list.
 *
 * This is the screen used on the door on the morning of the conference, so two
 * things drive its design. It is searched by name, email or phone from a single
 * field, because a queue at a door is the worst possible place to require a
 * person to work out which of three columns a partial name belongs in. And it
 * tolerates a slow connection, which is why the participant rows are loaded
 * flat rather than through a chain of eager loads on relations the search does
 * not touch.
 *
 * `markCheckedIn()` is used for both directions. It is idempotent — checking
 * somebody in twice updates the same row rather than appending a second event —
 * so a mis-tapped badge at the door is recoverable and produces no duplicate.
 */
class ParticipantController extends Controller
{
    public function index(Request $request): View
    {
        $edition = Edition::current();

        $search = trim((string) $request->query('q', ''));

        // A search of fewer than two characters matches a large fraction of any
        // attendee list, so the list degrades to "unfiltered" rather than
        // returning a slow page of near-random names.
        $effective = mb_strlen($search) >= 2 ? $search : '';

        $participants = Participant::query()
            ->when($edition, fn ($q) => $q->whereIn(
                'order_id',
                $edition->orders()->select('id'),
            ))
            // `badge_name` is included because it is what is printed on the
            // badge, and searching it finds somebody whose attendee record used
            // a different name spelling to their account.
            ->when($effective, fn ($q) => $q->where(function ($q) use ($effective): void {
                $like = '%'.$effective.'%';
                $q->where('full_name', 'like', $like)
                    ->orWhere('badge_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            }))
            ->orderBy('full_name')
            ->paginate(50)
            ->withQueryString();

        return view('admin.participants.index', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'admin.participants.index',
            'participants' => $participants,
            'q' => $search,
        ]);
    }

    public function checkIn(Participant $participant): RedirectResponse
    {
        abort_unless($this->isInCurrentEdition($participant), 404);

        $participant->markCheckedIn();

        return back()->with('status', __('admin.participants.checked_in', [
            'name' => $participant->full_name,
        ]));
    }

    /**
     * Undo a check-in.
     *
     * Separate from `checkIn` rather than a toggle, because "undo" and "check in"
     * are different intentions: a toggle fired twice by a confused operator
     * returns somebody to the queue and nobody notices. This is only reachable
     * by an explicit action on an already-checked-in row, and the button for it
     * is not rendered on the others.
     */
    public function checkOut(Participant $participant): RedirectResponse
    {
        abort_unless($this->isInCurrentEdition($participant), 404);

        $participant->forceFill([
            'checked_in' => false,
            'checked_in_at' => null,
        ])->save();

        return back()->with('status', __('admin.participants.checked_out', [
            'name' => $participant->full_name,
        ]));
    }

    /**
     * A participant belongs to the current edition through its order.
     *
     * Two joins deep, so this cannot be a column comparison the way the other
     * controllers do it — and that is exactly why it is written out rather than
     * left to a route-model-binding constraint: a binding cannot express
     * "belongs to the current edition by way of another table", and getting it
     * wrong would mean the door list could open somebody from another edition.
     */
    private function isInCurrentEdition(Participant $participant): bool
    {
        $edition = Edition::current();

        if ($edition === null) {
            return false;
        }

        return $participant->order()
            ->where('edition_id', $edition->getKey())
            ->exists();
    }
}
