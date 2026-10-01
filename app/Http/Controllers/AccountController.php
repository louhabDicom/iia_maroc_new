<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The signed-in delegate's own area.
 *
 * Deliberately thin: profile editing, the verification state and the order
 * history. The registration, order and payment work all happens elsewhere, and
 * every action here is scoped through a route model binding or `$request->user()`
 * rather than an id from the request, because the 2024 build trusted `$_GET`
 * user ids and let any visitor read any participant's order.
 */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('account.show', [
            'user' => $user,
            'locale' => Locale::parse($user->locale),
            // Eager loaded: the order list renders each order's items and total,
            // and a lazy relation here is one query per order.
            'orders' => $user->orders()
                ->with('items')
                ->latest('id')
                ->limit(20)
                ->get(),
            'membership' => $user->memberships()
                ->orderByDesc('id')
                ->first(),
            'canOrder' => $user->canRegister(),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        return app(SessionController::class)->__invoke($request);
    }
}
