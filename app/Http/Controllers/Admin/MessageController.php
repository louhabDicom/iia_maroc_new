<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Locale;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Edition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Contact messages.
 *
 * The one queue where the operator is expected to leave a record, because the
 * message is the only place the exchange exists: there is no threaded
 * conversation, and no email is sent by the platform, so `internal_notes` is
 * where "answered by phone on the 12th, number in the CRM" has to live.
 *
 * Marking a message answered does not send anything. That is deliberate and
 * worth not changing by accident — the button says "mark answered", and an
 * operator who expects it to mail the sender will find out at the worst moment
 * if it is quietly renamed to "reply".
 */
class MessageController extends Controller
{
    /** @return array<int, string> */
    public static function statuses(): array
    {
        return [
            ContactMessage::STATUS_NEW,
            ContactMessage::STATUS_OPEN,
            ContactMessage::STATUS_ANSWERED,
            ContactMessage::STATUS_SPAM,
        ];
    }

    public function index(Request $request): View
    {
        $edition = Edition::current();

        $status = $request->query('status');
        $status = is_string($status) && $status !== '' ? $status : null;

        $messages = ContactMessage::query()
            ->when($edition, fn ($q) => $q->where('edition_id', $edition->getKey()))
            ->with('handler')
            ->when(
                $status === null,
                fn ($q) => $q->open(),
                fn ($q) => $q->where('status', $status),
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.messages.index', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'admin.messages.index',
            'messages' => $messages,
            'status' => $status,
            'statuses' => self::statuses(),
            'subjects' => ContactMessage::subjectTypes(),
        ]);
    }

    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        abort_unless(
            $message->edition_id === Edition::current()?->getKey(),
            404,
        );

        $data = $request->validate([
            'status' => ['required', Rule::in(self::statuses())],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [], [
            'status' => __('admin.messages.status'),
            'notes' => __('admin.messages.notes'),
        ]);

        // The notes are appended before `answer()` writes, so the state change
        // and the record of why are saved together — a message closed with no
        // explanation is indistinguishable from one closed by mistake.
        if (filled($data['notes'] ?? null)) {
            $message->internal_notes = trim(
                ($message->internal_notes ? $message->internal_notes."\n\n" : '')
                .'['.now()->toDateTimeString().'] '.$data['notes']
            );
        }

        // `answer()` is the model's own transition and stamps the handler. It
        // hardcodes the answered state, so the other two are set directly and
        // the handler stamped the same way — spelled out rather than abstracted
        // into a helper, because there are three call sites and one of them is
        // not "answer".
        match ($data['status']) {
            ContactMessage::STATUS_ANSWERED => $message->answer($request->user(), $message->internal_notes),
            default => $message->forceFill([
                'status' => $data['status'],
                'handled_by' => $request->user()?->getKey(),
                'handled_at' => now(),
            ])->save(),
        };

        return back()->with('status', __('admin.messages.updated'));
    }
}