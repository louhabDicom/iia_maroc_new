<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SpeakersController;
use App\Http\Controllers\SponsorsController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Localised public routes
|--------------------------------------------------------------------------
|
| Every page is served at two addresses:
|
|   /programme          the default language (Arabic), unprefixed
|   /fr/programme       French
|   /en/programme       English
|
| Arabic is the default, so /ar/programme is deliberately NOT an address: the
| prefix belongs to the languages that are not the default, and honouring /ar/
| as well would leave one page with two canonical addresses for a search engine
| to choose between.
|
| Each page is therefore registered twice, and the two registrations are not
| interchangeable. That is not a stylistic choice, it is a framework constraint:
| a route URI with an *optional leading* segment, `{locale?}/programme`, compiles
| to a pattern where the separator after the segment is mandatory. It matches
| /en/programme and /ar/programme, and it does not match /programme at all â€”
| the empty-prefix case produces a double slash that the pattern rejects. The
| probe in the test suite asserts this, because the failure mode is a 404 on
| every unprefixed link with no error anywhere.
|
| So the pair is:
|
|   '/programme'          name 'programme.ar'  â€” matching only, no prefix
|   '/{locale?}/programme' name 'programme'     â€” matching, and the name every
|                                                   `route()` call in the app uses
|
| The canonical name belongs to the prefixed route because that is the one that
| has a `{locale}` parameter to fill. `LocalizeUrls` sets `URL::defaults(['locale'
| => $code])`, and a null default omits the segment, so the same call yields
| /programme in Arabic and /en/programme in English with no branching at the call
| site. Attaching the canonical name to the unprefixed route instead would make
| the default inert and every link Arabic-only.
|
| `Route::pattern` is the security-relevant part. The constraint is what stops a
| request such as /xx/anything from being swallowed by the prefix and reaching a
| route it was never meant to reach. Arabic is deliberately absent from the
| list, which also makes /ar/anything a clean 404 rather than a redirect loop
| back to the default.
|
*/

Route::pattern('locale', 'en|fr');

/**
 * Register a page at its unprefixed address and at its prefixed addresses.
 *
 * Wrapping the pair in one call rather than repeating the pattern inline is what
 * keeps the two registrations from drifting apart: the failure mode of getting it
 * wrong is a page that works in one language and 404s in the other, and it is a
 * failure no type checker or linter reports.
 *
 * @param  class-string|array{0: class-string, 1: string}|callable  $action
 * @param  array<string>  $methods
 */
$page = function (string $name, string $path, string|array|callable $action, array $methods = ['GET']): void {
    // Route::match() has no invokable shorthand the way Route::get() does, so an
    // invokable controller is expanded to its [class, '__invoke'] pair here.
    $action = is_string($action) ? [$action, '__invoke'] : $action;

    // The unprefixed address. Named `<name>.ar` so `route:list` still shows
    // something meaningful; nothing links to it by name, because generating the
    // default-locale URL is what the canonical name below is for.
    Route::match($methods, '/'.ltrim($path, '/'), $action)
        ->name($name.'.ar');

    // The prefixed address, which also matches the bare path when the prefix is
    // absent only in generated URLs, not in incoming requests.
    Route::match($methods, '/{locale?}/'.ltrim($path, '/'), $action)
        ->name($name);
};

// --- Public pages ----------------------------------------------------------

$page('home', '', HomeController::class);
$page('presentation', 'presentation', PresentationController::class);
$page('programme', 'programme', ProgrammeController::class);
$page('speakers', 'speakers', SpeakersController::class);

// Both spellings exist because the 2024 site linked /tarifs and inbound links
// point at /pricing. They are separate names pointing at one action, so the
// canonical URL can be declared in a <link rel="canonical"> without collapsing the
// address people already have in their bookmarks or in printed material.
$page('pricing', 'tarifs', PricingController::class);
Route::get('/pricing', PricingController::class)->name('pricing-alt.ar');
Route::get('/{locale?}/pricing', PricingController::class)->name('pricing-alt');

$page('venue', 'lieu', VenueController::class);
Route::get('/venue', VenueController::class)->name('venue-alt.ar');
Route::get('/{locale?}/venue', VenueController::class)->name('venue-alt');

$page('sponsors', 'partenaires', SponsorsController::class);
Route::get('/sponsors', SponsorsController::class)->name('sponsors-alt.ar');
Route::get('/{locale?}/sponsors', SponsorsController::class)->name('sponsors-alt');

$page('contact', 'contact', [ContactController::class, 'create']);
Route::match(['POST'], '/contact', [ContactController::class, 'store'])->name('contact.store.ar');
Route::post('/{locale?}/contact', [ContactController::class, 'store'])->name('contact.store');

// Constrained separately rather than through the helper, because the pattern has
// to apply to both registrations and the helper does not chain. A year is four
// digits and nothing else, so /archive/2024 is the archive and /archive/latest is
// a 404 rather than a query for a null edition.
Route::get('/archive/{year?}', [ArchiveController::class, '__invoke'])
    ->where('year', '\d{4}')
    ->name('archive.ar');
Route::get('/{locale?}/archive/{year?}', [ArchiveController::class, '__invoke'])
    ->where('year', '\d{4}')
    ->name('archive');
// --- Account creation and sign-in -----------------------------------------
//
// Guest-only, and inside the locale prefix so a code sent in Arabic is read on
// an Arabic page rather than on a French one.

Route::middleware('guest')->group(function () use ($page): void {
    $page('register', 'register', [RegisterController::class, 'create']);
    Route::match(['POST'], '/register', [RegisterController::class, 'store'])->name('register.store.ar');
    Route::post('/{locale?}/register', [RegisterController::class, 'store'])->name('register.store');

    $page('login', 'login', [SessionController::class, 'create']);
    Route::match(['POST'], '/login', [SessionController::class, 'store'])->name('login.store.ar');
    Route::post('/{locale?}/login', [SessionController::class, 'store'])->name('login.store');
});

Route::match(['POST'], '/logout', SessionController::class)
    ->middleware('auth')
    ->name('logout.ar');
Route::post('/{locale?}/logout', SessionController::class)
    ->middleware('auth')
    ->name('logout');

// --- The signed-in delegate area ------------------------------------------

Route::middleware('auth')->group(function () use ($page): void {
    $page('account', 'compte', [AccountController::class, 'show']);
    Route::get('/account', [AccountController::class, 'show'])->name('account-alt.ar');
    Route::get('/{locale?}/account', [AccountController::class, 'show'])->name('account-alt');
});

// --- Phone verification ---------------------------------------------------
//
// Reachable while authenticated but unverified â€” that is the whole point, since
// a half-finished registration has to be resumable. Every action reads the number
// from the stored account rather than from the request, so a challenge can never
// be aimed at a third party's handset.
//
// Ordering is gated separately by the `verified.phone` middleware, applied per
// route where it is needed. Applying it to this group would make
// /verify-phone unreachable, because that page *is* the unverified state.

Route::middleware('auth')->group(function () use ($page): void {
    $page('verification.notice', 'verify-phone', [VerificationController::class, 'show']);
    $page('verification.done', 'verify-phone/done', [VerificationController::class, 'done']);

    Route::match(['POST'], '/verify-phone/verify', [VerificationController::class, 'verify'])
        ->name('verification.verify.ar');
    Route::post('/{locale?}/verify-phone/verify', [VerificationController::class, 'verify'])
        ->name('verification.verify');

    Route::match(['POST'], '/verify-phone/resend', [VerificationController::class, 'resend'])
        ->name('verification.resend.ar');
    Route::post('/{locale?}/verify-phone/resend', [VerificationController::class, 'resend'])
        ->name('verification.resend');
});

// --- The basket -------------------------------------------------------------
//
// Reachable while signed out: a visitor fills a basket and is asked to sign in
// at the checkout, and the basket must survive that. The 2024 build only showed
// the basket to a signed-in user, so the pricing page's "add to cart" buttons
// silently did nothing for anyone not already logged in.

$page('cart', 'panier', [CartController::class, 'show']);
Route::post('/panier', [CartController::class, 'store'])->name('cart.store.ar');
Route::post('/{locale?}/panier', [CartController::class, 'store'])->name('cart.store');

Route::post('/panier/ligne/{cart}/{ticketType}', [CartController::class, 'update'])->name('cart.update.ar');
Route::post('/{locale?}/panier/ligne/{cart}/{ticketType}', [CartController::class, 'update'])->name('cart.update');

Route::post('/panier/ligne/{cart}/{ticketType}/supprimer', [CartController::class, 'destroy'])->name('cart.destroy.ar');
Route::post('/{locale?}/panier/ligne/{cart}/{ticketType}/supprimer', [CartController::class, 'destroy'])->name('cart.destroy');

Route::post('/panier/vider', [CartController::class, 'clear'])->name('cart.clear.ar');
Route::post('/{locale?}/panier/vider', [CartController::class, 'clear'])->name('cart.clear');

// --- The checkout -----------------------------------------------------------

$page('checkout', 'inscription', [CheckoutController::class, 'create']);
Route::post('/inscription', [CheckoutController::class, 'store'])->name('checkout.store.ar');
Route::post('/{locale?}/inscription', [CheckoutController::class, 'store'])->name('checkout.store');

Route::get('/inscription/payer/{order}', [CheckoutController::class, 'pay'])->name('checkout.pay.ar');
Route::get('/{locale?}/inscription/payer/{order}', [CheckoutController::class, 'pay'])->name('checkout.pay');

// --- Orders -----------------------------------------------------------------
//
// Behind auth, and additionally scoped to the signed-in buyer inside each
// controller. The 2024 `mescommandes.php` listed orders by session but took the
// id for the invoice and the cancellation from `$_POST['id']`, so any signed-in
// visitor could open and void somebody else's order.

Route::middleware('auth')->group(function () use ($page): void {
    $page('orders.index', 'mes-commandes', [OrderController::class, 'index']);
    Route::get('/commandes', [OrderController::class, 'index'])->name('orders.alt.ar');
    Route::get('/{locale?}/commandes', [OrderController::class, 'index'])->name('orders.alt');

    $page('orders.show', 'commande', [OrderController::class, 'show']);
    Route::get('/{locale?}/commande/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.alt-show.ar');
    Route::get('/{locale?}/orders/{order}', [OrderController::class, 'show'])->name('orders.alt-show');

    Route::post('/commande/{order}/facture', [OrderController::class, 'invoice'])->name('orders.invoice.ar');
    Route::post('/{locale?}/commande/{order}/facture', [OrderController::class, 'invoice'])->name('orders.invoice');

    Route::post('/commande/{order}/annuler', [OrderController::class, 'cancel'])->name('orders.cancel.ar');
    Route::post('/{locale?}/commande/{order}/annuler', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/paiement/retour/{order}', [PaymentController::class, 'return'])->name('payment.return.ar');
    Route::get('/{locale?}/paiement/retour/{order}', [PaymentController::class, 'return'])->name('payment.return');

    Route::get('/paiement/annule/{order}', [PaymentController::class, 'cancel'])->name('payment.cancel.ar');
    Route::get('/{locale?}/paiement/annule/{order}', [PaymentController::class, 'cancel'])->name('payment.cancel');
});

// The CMI server-to-server callback. Deliberately NOT localised and NOT behind
// auth: it is a POST from the gateway's own servers, with no session, and it
// answers in CMI's protocol rather than in HTML. The gateway signature is what
// authenticates it, and it is verified before anything is written.
Route::post('/payment/cmi/callback', [PaymentController::class, 'callback'])
    ->name('payment.callback');

// The local rehearsal page, refused unless PAYMENT_DRIVER=test, so it can never
// become a way to settle an order in production. Not localised: a developer
// affordance, not a page a delegate ever sees.
Route::get('/payment/test/{uuid}', [PaymentController::class, 'testGatewayPage'])
    ->middleware('auth')
    ->name('payment.test');

// --- Documents --------------------------------------------------------------
//
// `Document::downloadUrl()` has always pointed at `documents.download`; before
// this route existed that call raised a RouteNotFoundException and any page
// listing a document was a 500.

Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
    ->name('documents.download');
Route::get('/{locale?}/documents/{document}/download', [DocumentController::class, 'download'])
    ->name('documents.download.locale');

Route::get('/presentations/{file}/download', [DocumentController::class, 'presentation'])
    ->middleware('auth')
    ->name('presentations.download');
Route::get('/{locale?}/presentations/{file}/download', [DocumentController::class, 'presentation'])
    ->middleware('auth')
    ->name('presentations.download.locale');

// --- The language switcher ------------------------------------------------
//
// POST, not GET: it writes to the session and to the user's stored preference,
// so a GET would let a third-party page flip a visitor's language with an <img>
// tag. It is CSRF-protected for the same reason.
//
// Not localised: there is nothing to localise *about* the switcher, and a
// prefixed POST target would make the form in the layout depend on the current
// page's prefix for no gain.

Route::post('/locale', LocaleController::class)->name('locale.switch');
