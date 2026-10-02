<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Locale;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Order administration.
 *
 * Scoped to the current edition on every action, including the ones that take
 * an order in the URL. An `Order` is found by its own key and then constrained
 * by edition — not filtered in a `where` clause that a later edit could drop —
 * because the order id in the URL is the one piece of this request an operator
 * can type by hand. The 2024 `mescommandes.php` took the id straight from
 * `$_POST['id']` and updated whatever it pointed at; that is the shape of bug
 * this file exists to not have.
 */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $edition = Edition::current();

        $status = $request->query('status');
        $status = OrderStatus::tryFrom(is_string($status) ? $status : '');

        $orders = Order::query()
            // `currentEdition()` narrows to the edition under management. Null in
            // setup, and the empty-collection branch below covers it, so the
            // admin area is reachable before an edition exists.
            ->when($edition, fn ($q) => $q->where('edition_id', $edition->getKey()))
            ->with(['user', 'ticketType'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            // The filter has to survive pagination, otherwise choosing a status
            // and then turning to page two silently shows every status.
            ->withQueryString();

        return view('admin.orders.index', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'admin.orders.index',
            'orders' => $orders,
            'status' => $status,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(int $order): View
    {
        $order = $this->findForCurrentEdition($order);

        $order->load(['user', 'ticketType', 'items', 'participants', 'payments']);

        return view('admin.orders.show', [
            'order' => $order,
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'admin.orders.index',

            // The legal next states, taken from the enum rather than from a list
            // written out here. A second copy of the transition table is a
            // second thing to keep correct, and the 2024 site offered "cancel"
            // on a paid order with nothing guarding it.
            'allowed' => $order->status->allowedTransitions(),
        ]);
    }

    /**
     * Move an order to another state.
     *
     * Refuses an illegal transition with a validation error rather than a 403:
     * the request is well-formed, the operator just picked something that does
     * not apply to this order's current state, and a field error on the form
     * tells them that. A replayed gateway callback, which is the case this
     * actually defends against, is not an interactive operator and is answered
     * by `PaymentController` refusing it separately.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->assertInCurrentEdition($order);

        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'status' => __('order.status'),
            'notes' => __('admin.orders.notes'),
        ]);

        $target = OrderStatus::from($data['status']);

        if (! $order->status->canTransitionTo($target)) {
            return back()->withErrors([
                'status' => __('admin.orders.illegal_transition', [
                    'from' => $order->status->label(app()->getLocale()),
                    'to' => $target->label(app()->getLocale()),
                ]),
            ]);
        }

        $order->status = $target;

        // Timestamps are derived from the target state rather than set by the
        // form, so an operator cannot mark an order paid without `paid_at`
        // agreeing — which is what the CMI reconciliation reads.
        match ($target) {
            OrderStatus::Paid => $order->paid_at = now(),
            OrderStatus::Cancelled => $order->cancelled_at = now(),
            OrderStatus::Refunded, OrderStatus::PartiallyRefunded => $order->refunded_at = now(),
            default => null,
        };

        // A note is only appended when one was given, so an unannotated status
        // change does not write an empty string into the audit trail.
        if (filled($data['notes'] ?? null)) {
            $order->notes = trim(($order->notes ? $order->notes."\n\n" : '').'['
                .now()->toDateTimeString().'] '.$data['notes']);
        }

        $order->save();

        return back()->with('status', __('admin.orders.status_updated', [
            'reference' => $order->reference,
            'status' => $target->label(app()->getLocale()),
        ]));
    }

    /**
     * Resolve an order id within the current edition, or refuse.
     *
     * Aborting with 404 rather than redirecting means a stale bookmark or a
     * guessed id from a previous edition reports "gone", which is true, instead
     * of landing on somebody else's order.
     */
    private function findForCurrentEdition(int $orderId): Order
    {
        $edition = Edition::current();

        abort_if($edition === null, 404);

        $order = Order::query()
            ->where('edition_id', $edition->getKey())
            ->findOrFail($orderId);

        return $order;
    }

    /**
     * Refuse an order that is not in the edition under management.
     *
     * Used on the actions that take an order bound through route-model binding
     * rather than looked up by id, so the check happens after the row exists and
     * is therefore a comparison rather than a query. 404, not 403: from the
     * operator's point of view an order belonging to another edition is not
     * present in this one.
     */
    private function assertInCurrentEdition(Order $order): void
    {
        abort_unless(
            $order->edition_id === Edition::current()?->getKey(),
            404,
        );
    }
}