<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\ResolvesOwnedOrder;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Payment\PaymentGateway;
use App\Services\Registration\CheckoutService;
use App\Services\Registration\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * The checkout: names, then payment.
 *
 * Replaces `inscription.php` and `process_form.php`. The legacy pair had the
 * form in one file and the database writes in another, passed between them
 * through hidden fields â€” which is why a customer could edit a posted `id` and
 * read another person's order, and why a failure between the order insert and
 * the participant inserts left a paid order with nobody on it.
 *
 * Here the buyer names the people the tickets are for, sees the authoritative
 * total, and is sent to the gateway. The order is written once, in a
 * transaction, by CheckoutService.
 */
class CheckoutController extends Controller
{
    use ResolvesOwnedOrder;

    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    /**
     * Show the checkout for the current basket.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $cart = $this->currentCart($request);

        if ($cart->items()->doesntExist()) {
            return redirect()->route('pricing')->with('status', __('order.cart_empty'));
        }

        $cart->load(['items.ticketType']);

        // Not signed in, or not yet enrolled: the buyer is sent where they can
        // fix it, with a reason, rather than bounced to a bare 403.
        if ($request->user() === null) {
            return redirect()->route('login')->with('status', __('order.sign_in_to_checkout'));
        }

        if (! $request->user()->canRegister()) {
            return redirect()->route('totp.setup')
                ->with('status', __('order.enroll_to_checkout'));
        }

        return view('pages.checkout', [
            'cart' => $cart,
            'quote' => $this->quoteFor($request, $cart),
            'locale' => Locale::parse(app()->getLocale()),
            'currentRoute' => 'checkout',
            'user' => $request->user(),
        ]);
    }

    /**
     * Create the order and hand the customer to the gateway.
     */
    public function store(Request $request): RedirectResponse
    {
        $cart = $this->currentCart($request);

        if ($cart->items()->doesntExist()) {
            return redirect()->route('pricing')->with('status', __('order.cart_empty'));
        }

        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->canRegister()) {
            return redirect()->route('totp.setup')
                ->with('status', __('order.enroll_to_checkout'));
        }

        $quote = $this->quoteFor($request, $cart);

        $data = $this->validated($request, $quote->participantCount);

        try {
            $order = $this->checkout->place(
                cart: $cart,
                user: $user,
                quote: $quote,
                participants: $data['participants'],
                billing: [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'organisation' => $data['organisation'] ?? null,
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'country_iso2' => $data['country_iso2'] ?? null,
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                ],
                ip: $request->ip(),
            );
        } catch (RuntimeException $e) {
            // A capacity or consistency refusal is the customer's answer, not
            // a server fault: send them back with the reason rather than a 500.
            return back()->withInput()->withErrors(['participants' => $e->getMessage()]);
        }

        // The delegate is shown the invoice before any money moves.
        //
        // This used to redirect straight to `checkout.pay`, which handed the
        // order to CMI immediately. That made the invoice unreachable before
        // payment: there was no screen to look at what had just been bought,
        // check the seats, or print. The order already exists and is unpaid at
        // this point, so `orders.show` renders the full document — status "non
        // payée", the priced seats, the total — with the gateway one click
        // behind its own confirm button.
        return redirect()->route('orders.show', ['order' => $order->getKey()]);
    }

    /**
     * Hand the order to the configured gateway.
     *
     * A GET that produces a redirect to a payment page, not a state change:
     * no money moves and no row is written, so a reload is harmless. The order
     * already exists from the previous step.
     */
    public function pay(Request $request): View|RedirectResponse
    {
        // Resolved through the buyer's own orders rather than bound from the
        // route: the URI is `{locale?}/inscription/payer/{order}`, so a typed
        // `Order $order` argument is handed the locale. The ownership test here
        // was commented out, which left nothing behind to catch it.
        $order = $this->findOrder($request);

        if (! $order->isPayable()) {
            return redirect()->route('orders.show', ['order' => $order->getKey()])
                ->with('status', __('order.not_payable'));
        }

        if ($order->status === OrderStatus::Pending) {
            $order->transitionTo(OrderStatus::AwaitingPayment);
        }

        $redirect = app(PaymentGateway::class)->redirectFor(
            $order,
            route('payment.return', ['order' => $order->getKey()]),
            route('payment.cancel', ['order' => $order->getKey()]),
        );

        // A self-submitting form rather than a Location header, because that
        // is what the gateway's own integration expects and because some
        // corporate proxies drop 302s to external hosts.
        return view('pages.checkout.redirect', [
            'order' => $order,
            'redirect' => $redirect,
        ]);
    }

    // --- Helpers ---------------------------------------------------------

    private function currentCart(Request $request): Cart
    {
        return Cart::forSession(
            $request->session()->getId(),
            $request->user(),
        );
    }

    /**
     * A basket that cannot be quoted â€” empty, or holding two currencies â€” is a
     * validation failure on the basket rather than a 500.
     *
     * Thrown rather than redirected from here, because the redirect has to
     * happen in the caller: a redirect returned from a method typed to produce
     * a Quote is how a broken basket would end up being rendered as a checkout
     * page with no quote attached.
     */
    private function quoteFor(Request $request, Cart $cart): Quote
    {
        try {
            return $this->checkout->quote($cart, $request->user());
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['cart' => $e->getMessage()]);
        }
    }

    /**
     * Validate the billing block and exactly one entry per place.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $placeCount): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'organisation' => ['nullable', 'string', 'max:190'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country_iso2' => ['nullable', 'string', 'size:2'],
            'email' => ['required', 'email:filter', 'max:190'],
            'phone' => ['required', 'string', 'max:32'],
            'participants' => ['required', 'array', 'min:1', 'max:20'],
            'participants.*.full_name' => ['required', 'string', 'max:190'],
            'participants.*.job_title' => ['nullable', 'string', 'max:160'],
            'participants.*.email' => ['nullable', 'email:filter', 'max:190'],
            'participants.*.phone' => ['nullable', 'string', 'max:32'],
            'participants.*.is_member' => ['nullable', 'boolean'],
            'participants.*.badge_name' => ['nullable', 'string', 'max:48'],
        ], [], [
            'first_name' => __('register.first_name'),
            'last_name' => __('register.last_name'),
            'organisation' => __('register.organisation'),
            'address' => __('order.address'),
            'city' => __('register.city'),
            'country_iso2' => __('register.country'),
            'email' => __('register.email'),
            'phone' => __('register.phone'),
            'participants' => __('order.participants'),
            'participants.*.full_name' => __('order.participant_name'),
            'participants.*.job_title' => __('register.job_title'),
            'participants.*.email' => __('register.email'),
            'participants.*.phone' => __('register.phone'),
        ]);

        // Checked here as well as in the service. The service is the
        // authority â€” it runs inside the transaction â€” but failing here with a
        // field-level error is kinder than a generic message, and it stops a
        // tampered form from reaching the database at all.
        if (count($data['participants']) !== $placeCount) {
            throw ValidationException::withMessages([
                'participants' => __('order.participant_count_mismatch', [
                    'expected' => $placeCount,
                ]),
            ]);
        }

        return $data;
    }
}
