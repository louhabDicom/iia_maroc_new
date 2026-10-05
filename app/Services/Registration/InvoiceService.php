<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Edition;
use App\Models\Order;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders an order's invoice.
 *
 * The 2024 `facture.php` re-joined `iia_offre` and re-read the *live* price
 * every time it was opened. Changing the 2026 tariff after a delegate had paid
 * therefore silently rewrote the figure on an already-issued invoice. Here the
 * PDF is built exclusively from the snapshot columns on `orders` and
 * `order_items`, which are written once at checkout and never recomputed.
 *
 * The rendered file is cached on the `local` disk. It is not stored on a
 * public disk, and it is never reachable by guessing a URL: the download goes
 * through a controller that authorises against the order's owner.
 */
class InvoiceService
{
    /**
     * Generate (or regenerate) the PDF for a paid order.
     *
     * Allocates the sequential invoice number on first call. Returns the path
     * relative to the storage disk.
     */
    public function generate(Order $order): string
    {
        if (blank((string) $order->invoice_number)) {
            $order->assignInvoiceNumber();
        }

        $order->loadMissing(['items', 'participants', 'edition', 'user', 'ticketType']);

        $pdf = Pdf::loadView('invoices.pdf', [
            'order' => $order,
            'edition' => $order->edition,
            'user' => $order->user,
            // The locale of the *invoice*, not of the request. An invoice
            // issued in French must stay in French when the customer later
            // browses the site in Arabic, or their accounting department
            // receives a document in a language nobody there reads.
            'invoiceLocale' => $this->invoiceLocale($order),
        ]);

        $pdf->setPaper(
            (string) config('pdf.paper', 'a4'),
            (string) config('pdf.orientation', 'portrait'),
        );

        $filename = $this->filename($order);

        Storage::disk($this->disk())->put($filename, $pdf->output());

        $order->forceFill(['invoice_path' => $filename])->save();

        return $filename;
    }

    /**
     * Stream an existing invoice, generating it first if it is missing.
     *
     * Generation is attempted on demand so an order that was paid while the
     * renderer was broken still produces a document rather than a 404.
     *
     * Returns StreamedResponse rather than BinaryFileResponse because that is
     * what `Storage::download()` actually produces; declaring the narrower type
     * is a TypeError the first time anyone downloads an invoice.
     */
    public function download(Order $order): StreamedResponse
    {
        $path = (string) $order->invoice_path;
        $disk = Storage::disk($this->disk());

        if ($path === '' || ! $disk->exists($path)) {
            $path = $this->generate($order);
        }

        return $disk->download($path, $this->downloadName($order));
    }

    // --- The proforma ---------------------------------------------------------

    /**
     * A proforma invoice for an order that has not been paid yet.
     *
     * Delegates routinely need a document *before* they pay: an employer or a
     * procurement office will not release funds against a screenshot of a basket.
     * What they cannot be given is a numbered invoice, because a numbered
     * invoice is an accounting document and issuing one for money that has not
     * arrived puts a paper the bank will not honour into circulation. So this
     * renders the same information under the heading "proforma", allocates no
     * invoice number, and never touches `orders.invoice_number` or
     * `orders.invoice_path` — the real invoice is still generated once, later,
     * by generate() at settlement.
     *
     * Generated on every request rather than cached: it is built from a basket
     * that can still change, so a stored copy would be a document describing a
     * basket the delegate no longer has.
     */
    public function orderProforma(Order $order): StreamedResponse
    {
        $payload = $this->orderPayload($order);

        return $this->streamProforma(
            $payload,
            $this->proformaName((string) $payload['reference']),
        );
    }

    /**
     * The same figures the PDF prints, as data.
     *
     * Public because the printable HTML page has to render from it too. Two
     * documents describing one basket that disagree over a total is precisely
     * the defect this service exists to prevent, so the browser printout and the
     * downloaded PDF are built from one payload rather than from two
     * calculations that look alike.
     *
     * @return array<string, mixed>
     */
    public function orderPayload(Order $order): array
    {
        $order->loadMissing(['items', 'edition', 'user']);

        $currency = (string) $order->currency;

        return [
            'edition' => $order->edition,
            'reference' => (string) $order->reference,
            'issuedOn' => $order->created_at->format('d/m/Y'),
            'lines' => $order->items->map(fn ($item): array => [
                'label' => (string) $item->label,
                'quantity' => $item->totalQuantity(),
                'total' => $item->formattedLineTotal($currency),
            ])->all(),
            'subtotal' => (int) $order->subtotal,
            'discount' => (int) $order->discount_total,
            'tax' => (int) $order->tax_total,
            'total' => (int) $order->total,
            'currency' => $currency,
            'billedTo' => $this->orderBillingLines($order),
            'invoiceLocale' => $this->invoiceLocale($order),
        ];
    }

    /**
     * A proforma invoice for a basket, before any order exists.
     *
     * The figures are the basket's estimate, which is the same indicative total
     * the cart page shows and is labelled as such on the document: the member
     * rate is only granted for participants matched against an active Membership
     * record, and that match cannot happen until the checkout. The proforma
     * therefore says plainly that the definitive figure is issued after
     * checkout, rather than presenting an estimate with the confidence of a
     * settled total.
     */
    public function cartProforma(Cart $cart, ?User $user): StreamedResponse
    {
        return $this->streamProforma($this->cartPayload($cart, $user), 'facture-provisoire.pdf');
    }

    /**
     * The basket's proforma figures, as data. See orderPayload().
     *
     * @return array<string, mixed>
     */
    public function cartPayload(Cart $cart, ?User $user): array
    {
        $cart->loadMissing(['items.ticketType', 'user']);

        $currency = (string) ($cart->items->first()?->ticketType?->currency
            ?? config('conference.default_currency', 'MAD'));

        $estimate = (int) $cart->items->sum(fn (CartItem $item): int => $item->estimatedTotal());

        return [
            'edition' => Edition::current(),
            // The basket has no reference of its own, so the document carries
            // the edition instead of a number that does not exist yet.
            'reference' => null,
            'issuedOn' => now()->format('d/m/Y'),
            'lines' => $cart->items->map(fn (CartItem $item): array => [
                'label' => (string) ($item->ticketType?->name ?? __('order.ticket')),
                'quantity' => (int) $item->quantity,
                'total' => $item->formattedEstimatedTotal(),
            ])->all(),
            'subtotal' => $estimate,
            'discount' => 0,
            'tax' => 0,
            'total' => $estimate,
            'currency' => $currency,
            'billedTo' => $this->cartBillingLines($user),
            'invoiceLocale' => $this->cartLocale($user),
        ];
    }

    /**
     * Render the proforma template and stream it as a download.
     *
     * `@return array<string, mixed>`
     */
    private function streamProforma(array $payload, string $filename): StreamedResponse
    {
        $pdf = Pdf::loadView('invoices.proforma', $payload);

        $pdf->setPaper(
            (string) config('pdf.paper', 'a4'),
            (string) config('pdf.orientation', 'portrait'),
        );

        $output = $pdf->output();

        return response()->streamDownload(
            static function () use ($output): void {
                echo $output;
            },
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @return array<int, string>
     */
    private function orderBillingLines(Order $order): array
    {
        $billing = (array) $order->billing;

        $lines = array_filter([
            trim(((string) ($billing['first_name'] ?? '')).' '.((string) ($billing['last_name'] ?? ''))),
            (string) ($billing['organisation'] ?? ''),
            (string) ($billing['address'] ?? ''),
            (string) ($billing['city'] ?? ''),
            (string) ($billing['country_iso2'] ?? ''),
            (string) ($billing['email'] ?? ''),
            (string) ($billing['phone'] ?? ''),
        ]);

        return $lines === [] ? [__('order.invoice.billed_to_pending')] : array_values($lines);
    }

    /**
     * A guest's basket carries no billing details at all, so the block says so
     * instead of printing an empty "Facturé à".
     *
     * @return array<int, string>
     */
    private function cartBillingLines(?User $user): array
    {
        if ($user === null) {
            return [__('order.invoice.billed_to_pending')];
        }

        $lines = array_filter([
            $user->displayName(),
            (string) ($user->organisation ?? ''),
            (string) ($user->email ?? ''),
            (string) ($user->phone ?? ''),
        ]);

        return $lines === [] ? [__('order.invoice.billed_to_pending')] : array_values($lines);
    }

    private function cartLocale(?User $user): string
    {
        $locale = (string) ($user?->locale ?? app()->getLocale());

        return in_array($locale, ['fr', 'en', 'ar'], true) ? $locale : 'fr';
    }

    private function proformaName(string $reference): string
    {
        return sprintf('facture-provisoire-%s.pdf', Str::slug($reference));
    }

    /**
     * A stable, collision-free filename.
     *
     * Keyed on the order's uuid rather than its id so the name cannot be used
     * to enumerate orders, and suffixed with a short random segment so two
     * regenerations of the same invoice do not overwrite each other mid-read.
     */
    private function filename(Order $order): string
    {
        return sprintf(
            'invoices/%s/%s-%s.pdf',
            $order->reference,
            Str::uuid()->toString(),
            Str::slug((string) $order->invoice_number),
        );
    }

    private function downloadName(Order $order): string
    {
        return sprintf(
            'facture-%s.pdf',
            Str::slug((string) ($order->invoice_number ?? $order->reference)),
        );
    }

    private function disk(): string
    {
        return 'local';
    }

    /**
     * The language the invoice is issued in.
     *
     * French unless the buyer explicitly registered in English or Arabic. The
     * brief's working language for the event is French, and an invoice is a
     * legal document an organisation files locally.
     */
    private function invoiceLocale(Order $order): string
    {
        $userLocale = (string) ($order->user?->locale ?? 'fr');

        return in_array($userLocale, ['fr', 'en', 'ar'], true) ? $userLocale : 'fr';
    }
}
