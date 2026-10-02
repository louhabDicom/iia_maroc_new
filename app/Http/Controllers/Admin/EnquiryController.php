<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\SponsoringPackage;
use App\Models\SponsorshipEnquiry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Sponsorship enquiries.
 *
 * A lead, not an order. The distinction drives the whole design here: there is
 * no payment to reconcile and no place to allocate, so an enquiry is worked
 * through contact stages and the value is expected in the package, not on the
 * row. `SponsoringPackage` is joined in only to name the tier the prospect
 * asked about — the price is not read from it, because a package can be
 * repriced after the enquiry arrived and the note should record what was
 * actually quoted.
 */
class EnquiryController extends Controller
{
    /** @return array<int, string> */
    public static function stages(): array
    {
        return [
            SponsorshipEnquiry::STATUS_NEW,
            SponsorshipEnquiry::STATUS_CONTACTED,
            SponsorshipEnquiry::STATUS_QUOTED,
            SponsorshipEnquiry::STATUS_WON,
            SponsorshipEnquiry::STATUS_LOST,
        ];
    }

    public function index(Request $request): View
    {
        $edition = Edition::current();

        $status = $request->query('status');
        $status = is_string($status) && $status !== '' ? $status : null;

        $enquiries = SponsorshipEnquiry::query()
            ->when($edition, fn ($q) => $q->where('edition_id', $edition->getKey()))
            ->with(['package', 'handler'])
            ->when(
                $status === null,
                fn ($q) => $q->open(),
                fn ($q) => $q->where('status', $status),
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.enquiries.index', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'admin.enquiries.index',
            'enquiries' => $enquiries,
            'status' => $status,
            'stages' => self::stages(),
            'packages' => SponsoringPackage::query()
                ->when($edition, fn ($q) => $q->where('edition_id', $edition->getKey()))
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function update(Request $request, SponsorshipEnquiry $enquiry): RedirectResponse
    {
        abort_unless(
            $enquiry->edition_id === Edition::current()?->getKey(),
            404,
        );

        $data = $request->validate([
            'status' => ['required', Rule::in(self::stages())],
            'notes' => [
                'required_if:status,'.SponsorshipEnquiry::STATUS_LOST,
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'notes.required_if' => __('admin.enquiries.reason_required'),
        ], [
            'status' => __('admin.enquiries.status'),
            'notes' => __('admin.enquiries.notes'),
        ]);

        // `markHandled()` stamps handled_by/handled_at from the signed-in
        // operator, so attribution is a property of the request rather than
        // something a form can assert.
        $enquiry->markHandled($request->user(), $data['status']);

        if (filled($data['notes'] ?? null)) {
            // Appended to `internal_notes`, never to `message`. The two columns
            // hold different voices — what the prospect wrote and what the
            // organiser wrote — and a lead is worked over several contacts, so
            // overwriting either loses the history the next person needs.
            $enquiry->internal_notes = trim(
                ($enquiry->internal_notes ? $enquiry->internal_notes."\n\n" : '')
                .'['.now()->toDateTimeString().'] '.$data['notes']
            );
            $enquiry->save();
        }

        return back()->with('status', __('admin.enquiries.updated', [
            'company' => $enquiry->company,
        ]));
    }
}
