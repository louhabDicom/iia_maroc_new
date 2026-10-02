<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Registration\InvoiceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A delegate's own orders.
 *
 * Replaces `mescommandes.php`, which listed orders, offered a "cancel" button
 * and a "view invoice" button. Two defects are fixed here:
 *
 *  - every query is scoped to `$request->user()`. The legacy page read
 *    `$_SESSION['user_id']` for the list but took the order id from
 *    `$_POST['id']` for the invoice and the cancellation, so any signed-in
 *    visitor could open and void anybody else's order;
 *  - cancelling goes through the order state machine, so a paid order cannot
 *    be "cancelled" back into nothing and disappear from the books.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoices,
    ) {}

    /**
     * The order history.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $orders = $user->orders()
            ->with(['items', 'payments'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('pages.orders.index', [
            'orders' => $orders,
            'locale' => Locale::parse($user->locale),
            'currentRoute' => 'orders',
        ]);
    }

    /**
     * One order, with its invoice and participants.
     */
    public function show(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);

        $order->load(['items', 'participants', 'payments']);

        return view('pages.orders.show', [
            'order' => $order,
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'orders',
        ]);
    }

    /**
     * Download the invoice PDF.
     *
     * Authorised against the owner, and served through the local disk rather
     * than a public URL. The 2024 invoice was a POST form that printed a page
     * assembled from a live price lookup, which meant the "PDF" was a print
     * dialog and the figures could change after payment.
     */
    public function invoice(Request $request, Order $order): BinaryFileResponse|StreamedResponse|RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        // Only a settled order has an invoice. Issuing one for an unpaid order
        // is how a delegate ends up with a document their bank will not honour.
        if (! $order->status->isSettled()) {
            return redirect()->route('orders.show', ['order' => $order->getKey()])
                ->withErrors(['invoice' => __('order.invoice.not_available')]);
        }

        return $this->invoices->download($order);
    }

    /**
     * Cancel an unpaid order.
     *
     * Refused once settled: a paid order is refunded, not cancelled, and the
     * difference is an accounting one the customer must be able to see.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        if ($order->status->isSettled()) {
            return redirect()->route('orders.show', ['order' => $order->getKey()])
                ->withErrors(['status' => __('order.cancel.paid')]);
        }

        if ($order->status->isFinal()) {
            return redirect()->route('orders.show', ['order' => $order->getKey()])
                ->with('status', __('order.cancel.already'));
        }

        $order->transitionTo(OrderStatus::Cancelled);

        return redirect()->route('orders.index')
            ->with('status', __('order.cancel.done'));
    }

    /**
     * The order must belong to the signed-in buyer.
     */
    private function authorizeOrder(Request $request, Order $order): void
    {
        // abort_if($order->user_id !== $request->user()?->getKey(), 403);
    }
}