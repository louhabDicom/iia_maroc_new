<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\ContactMessage;
use App\Models\ContactRoute;
use App\Models\Edition;
use App\Services\Security\RateLimiter;
use App\Services\Security\SecurityEventLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * The contact form.
 *
 * Every property of the 2024 contact handling is deliberately absent here:
 *
 *  - the recipient is read from the edition row, not from a form field, so the
 *    form cannot be used to make the server mail an arbitrary address;
 *  - nothing is echoed back into the page, so the handler is not a reflected-XSS
 *    sink and the 2024 mailer does not have to guess what to escape;
 *  - it is rate limited per IP, and the submission is audited.
 */
class ContactController extends Controller
{
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly SecurityEventLogger $events,
    ) {}

    public function create(): View
    {
        $edition = Edition::current();

        return view('pages.contact', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'contact',
            // Labels come from the database so the communications team can rename
            // a route without a deploy; the enum is the fallback for a row that
            // has not been created yet.
            'topics' => ContactRoute::query()
                ->active()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (ContactRoute $route): array => [
                    'value' => $route->subject_type,
                    'label' => $route->label(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $max = (int) config('security.rate_limit_contact', 3);
        $decay = (int) config('security.contact_decay_seconds', 3600);

        $this->limiter->assertWithin('contact:submit', $request->ip() ?? 'unknown', $request->ip(), $max, $decay);

        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:filter', 'max:190'],
            // Constrained to the enum rather than accepted as free text: the
            // value is used to look up a delivery route, so an unknown value
            // would be a routing failure discovered only at send time.
            'subject_type' => ['required', Rule::in(ContactMessage::subjectTypes())],
            'subject' => ['nullable', 'string', 'max:160'],
            'organisation' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ], [
            'subject_type.in' => __('contact.topic'),
        ])->validate();

        $edition = Edition::current();
        $route = ContactRoute::forSubject($data['subject_type']);
        $recipient = $route?->to_email ?? $edition?->contact_email;

        if (blank($recipient)) {
            // No address configured is an operator problem, not a visitor one.
            // The submission is still audited so nothing is silently lost.
            $this->events->log('contact.unroutable', [
                'level' => 'error',
                'ip' => $request->ip(),
                'context' => [
                    'reason' => 'no_contact_email_configured',
                    'subject_type' => $data['subject_type'],
                ],
            ]);

            return back()->withErrors(['message' => __('contact.unavailable')]);
        }

        // Persisted before the mail is queued. The queue is the fragile part: if
        // it is down, the enquiry is still in the database and visible in the
        // admin panel, which is the difference between a lost message and a
        // delayed one.
        $record = ContactMessage::query()->create([
            'edition_id' => $edition?->getKey(),
            'subject_type' => $data['subject_type'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'organisation' => $data['organisation'] ?? null,
            'message' => $data['message'],
            'locale' => app()->getLocale(),
            'ip_address' => $request->ip(),
            'status' => ContactMessage::STATUS_NEW,
        ]);

        $this->events->log('contact.received', [
            'ip' => $request->ip(),
            'context' => [
                'contact_message_id' => $record->getKey(),
                'subject_type' => $data['subject_type'],
                'recipient' => $recipient,
                'subject' => $data['subject'] ?? null,
                // The body is deliberately not stored in the event log: the
                // security table is read by people who should not be reading
                // every enquiry, and it is in `contact_messages` instead.
                'message_length' => mb_strlen($data['message']),
            ],
        ]);

        $mailable = new \App\Mail\ContactMessage(
            $data['name'],
            $data['email'],
            $data['subject'] ?? null,
            $data['message'],
        );

        $mail = Mail::to($recipient);

        if (filled($route?->cc_email)) {
            $mail->cc($route->cc_email);
        }

        // Queued rather than sent inline, so a slow SMTP server cannot be used
        // to keep a visitor's request open.
        try {
            $mail->queue($mailable);
        } catch (Throwable $e) {
            // A queue outage is a degraded state, not a rejected submission. The
            // record exists and the visitor is told it was received, because it
            // was; the send can be retried from the admin panel.
            report($e);

            $this->events->log('contact.queued_failed', [
                'level' => 'error',
                'ip' => $request->ip(),
                'context' => [
                    'contact_message_id' => $record->getKey(),
                    'reason' => $e::class,
                ],
            ]);
        }

        $this->limiter->hit('contact:submit', $request->ip() ?? 'unknown', $request->ip());

        return back()->with('status', __('contact.sent'));
    }
}
