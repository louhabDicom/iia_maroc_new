<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the customer their order's state changed.
 *
 * Queued, so a slow mail server cannot hold the payment callback open — the
 * gateway times out in seconds and will treat a timeout as a failure, which is
 * how a captured payment ends up looking unpaid.
 *
 * The locale follows the recipient's stored preference rather than the request
 * that happened to trigger the mail, so a French-reading delegate is not
 * emailed an invoice notification in Arabic because an administrator was
 * browsing the site in Arabic when the callback landed.
 */
class OrderStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $event,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->event) {
            'paid' => __('order.mail.paid_subject', [
                'reference' => $this->order->reference,
            ], $this->recipientLocale()),
            default => __('order.mail.failed_subject', [
                'reference' => $this->order->reference,
            ], $this->recipientLocale()),
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            // text, not view: this is the plain-text part. See the view for
            // why a notification about money avoids HTML entirely.
            text: 'mail.order-status',
            with: [
                'order' => $this->order,
                'event' => $this->event,
                'locale' => $this->recipientLocale(),
                'total' => Money::format($this->order->total, $this->order->currency, $this->recipientLocale()),
                'invoiceNumber' => $this->order->invoice_number,
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    public function attachments(): array
    {
        // The invoice rides along on a paid order when it has been rendered.
        // A missing file is not a failure: the mail still says where to
        // download it, and an attachment that fails would take the whole
        // notification with it.
        if ($this->event !== 'paid' || blank((string) $this->order->invoice_path)) {
            return [];
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $path = (string) $this->order->invoice_path;

        if (! $disk->exists($path)) {
            return [];
        }

        return [$path];
    }

    private function recipientLocale(): string
    {
        $locale = (string) ($this->order->user?->locale ?? 'fr');

        return in_array($locale, ['fr', 'en', 'ar'], true) ? $locale : 'fr';
    }
}