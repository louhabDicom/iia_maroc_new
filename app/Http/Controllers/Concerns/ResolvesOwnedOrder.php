<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Resolves the order a `{order}` route segment points at, owned by the caller.
 *
 * Two things this handles that a typed `Order $order` argument does not.
 *
 * First, position. A controller action's arguments are filled *positionally*
 * from the route's parameters, and every public route on this site is
 * `{locale?}/<segment>/{order}`. For `/fr/commande/21` the slots come out as
 * `['fr', '21']`, so an `Order $order` argument is handed the locale and the id
 * is dropped. That failed silently and looked like an authorization fault: the
 * ownership test compared `'fr'` against the user's id and refused the buyer's
 * own invoice. Reading the parameter by name removes the coupling.
 *
 * Second, ownership. The order is looked up inside the user's own relation
 * rather than fetched and compared afterwards, so no action can read a row
 * belonging to somebody else — there is no point in the request at which such a
 * row is in hand, and a future action cannot forget the check. A miss is a 404
 * rather than a 403, because "forbidden" confirms the order exists and turns
 * the endpoint into an oracle for guessing live reference numbers.
 *
 * The signature is the point: actions using this take `Request` only.
 */
trait ResolvesOwnedOrder
{
    /**
     * The order named by the current route, scoped to the signed-in buyer.
     */
    protected function findOrder(Request $request): Order
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $key = $request->route('order');

        abort_if($key === null, 404);

        return $user->orders()
            ->whereKey($key)
            ->with(['items', 'participants', 'payments'])
            ->firstOrFail();
    }
}
