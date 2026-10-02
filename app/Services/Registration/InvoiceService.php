<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Models\Order;
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