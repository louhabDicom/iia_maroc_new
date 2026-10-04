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
 *
 *  - the ownership check *is* the query — see findOrder(). An order is found
 *    through the buyer's own relation, so no action here can read one that is
 *    not theirs, now or when a third action is added;
 *
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
    public function show(Request $request): View
    {
        $order = $this->findOrder($request);

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
    public function invoice(Request $request): BinaryFileResponse|StreamedResponse|RedirectResponse
    {
        $order = $this->findOrder($request);

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
    public function cancel(Request $request): RedirectResponse
    {
        $order = $this->findOrder($request);

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
     * The id of the order this route is pointing at.
     *
     * Read by name from the route rather than taken as a typed action argument,
     * because a controller action's arguments are filled *positionally* from the
     * route's parameters, and this URI is `{locale?}/commande/{order}`.
     *
     * With `{locale?}` leading the path, `/fr/commande/21` fills the slots as
     * `['fr', '21']`, so an `Order $order` argument receives the string `'fr'`.
     * The symptom was silent and looked like an authorization failure: the
     * ownership check compared a locale against the signed-in user's id and
     * aborted with 403, for the delegate's own invoice, on every prefixed URL.
     *
     * Taking only `Request` removes the positional coupling entirely.
     */
    private function orderKey(Request $request): string
    {
        $key = $request->route('order');

        abort_if($key === null, 404);

        return (string) $key;
    }

    /**
     * Resolve an order from the signed-in buyer's own orders.
     *
     * The order is looked up *through the user's relation* rather than fetched
     * and then compared. That is a stronger guarantee than a post-fetch check:
     * there is no window in which a row belonging to somebody else is in hand,
     * and the ownership rule cannot be forgotten by a new action added later.
     *
     * It also stops relying on implicit route-model binding, which did not
     * resolve on the `{locale?}/commande/{order}` variant and passed the raw id
     * through as a string.
     *
     * A miss is a 404, not a 403. "Forbidden" confirms the order exists, which
     * turns this endpoint into an oracle for guessing which reference numbers
     * are real; a 404 is the same answer whether the order belongs to someone
     * else or was never issued.
     */
    private function findOrder(Request $request): Order
    {
        $user = $request->user();

        abort_if($user === null, 403);

        return $user->orders()
            ->whereKey($this->orderKey($request))
            ->with(['items', 'participants', 'payments'])
            ->firstOrFail();
    }
}
