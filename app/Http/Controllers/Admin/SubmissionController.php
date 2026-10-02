<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\SpeakerSubmission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The speaker submission review queue.
 *
 * `SpeakerSubmission::review()` is the only writer of `reviewed_by`/`reviewed_at`,
 * and this is its only caller. That matters beyond tidiness: the columns are the
 * record of who accepted a talk, and a status change made by a script with no
 * reviewer attached would leave an accepted submission nobody can be asked about.
 *
 * The `Rule::in` list below is built from the model's own status constants
 * rather than typed out, so a state added to the model is offered in the form
 * immediately and cannot be selected from the form but rejected by the model.
 */
class SubmissionController extends Controller
{
    /**
     * Every state a reviewer can assign.
     *
     * @return array<int, string>
     */
    public static function reviewableStatuses(): array
    {
        return [
            SpeakerSubmission::STATUS_ACCEPTED,
            SpeakerSubmission::STATUS_WAITLISTED,
            SpeakerSubmission::STATUS_REJECTED,
            SpeakerSubmission::STATUS_UNDER_REVIEW,
            SpeakerSubmission::STATUS_WITHDRAWN,
        ];
    }

    public function index(Request $request): View
    {
        $edition = Edition::current();

        $status = $request->query('status');
        $status = is_string($status) && $status !== '' ? $status : null;

        $submissions = SpeakerSubmission::query()
            ->when($edition, fn ($q) => $q->where('edition_id', $edition->getKey()))
            ->with(['user', 'requestedTrack'])
            // `pending()` is the default view because the queue exists to clear
            // the undecided pile; the filter below is how you see the rest.
            ->when(
                $status === null,
                fn ($q) => $q->pending(),
                fn ($q) => $q->where('status', $status),
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.submissions.index', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'admin.submissions.index',
            'submissions' => $submissions,
            'status' => $status,
            'statuses' => self::reviewableStatuses(),
            'tracks' => $edition?->tracks()->orderBy('sort_order')->get() ?? collect(),
        ]);
    }

    public function update(Request $request, SpeakerSubmission $submission): RedirectResponse
    {
        $this->assertInCurrentEdition($submission);

        $data = $request->validate([
            'status' => ['required', Rule::in(self::reviewableStatuses())],
            // Notes are optional on acceptance and effectively mandatory on a
            // rejection: refusing a talk without recording why leaves the
            // speaker asking and nobody able to answer. Enforced with `required_if`
            // rather than in the controller so the message renders against the
            // field the reviewer needs to fill.
            'review_notes' => [
                'required_if:status,'.SpeakerSubmission::STATUS_REJECTED,
                'nullable',
                'string',
                'max:5000',
            ],
        ], [
            'review_notes.required_if' => __('admin.submissions.notes_required'),
        ], [
            'status' => __('admin.submissions.status'),
            'review_notes' => __('admin.submissions.notes'),
        ]);

        $submission->review(
            $data['status'],
            $request->user(),
            $data['review_notes'] ?? null,
        );

        return back()->with('status', __('admin.submissions.updated'));
    }

    private function assertInCurrentEdition(SpeakerSubmission $submission): void
    {
        abort_unless(
            $submission->edition_id === Edition::current()?->getKey(),
            404,
        );
    }
}