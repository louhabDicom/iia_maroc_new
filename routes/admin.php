<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\SubmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| The staff area
|--------------------------------------------------------------------------
|
| Every route here sits behind `auth` and `admin`. The two are separate
| middleware on purpose: `auth` establishes who is asking, `admin` decides
| whether they are allowed to change money, published content or someone
| else's data. Collapsing them into one alias would make it impossible to say
| "signed in, but not staff" — and the 2024 site had no way to say it at all,
| which is how `iia_emble` users ended up operating the front-end database by
| hand.
|
| The one exception is the dashboard itself, which is still behind both. There
| is no unauthenticated admin route and there should not be one.
|
| NOT localised. `/admin/orders` is not `/fr/admin/orders`. The staff area is
| operated by one team in one working language, and the locale prefix is
| applied by `LocalizeUrls` through `URL::defaults()`. Leaving `{locale?}` off
| these URIs is what stops a visit to the English site from producing admin
| links that 404, because there is no `{locale}` parameter for the prefix to
| fill and the unprefixed path is always the real one.
|
| Route-model binding is used for the show/update actions. Binding resolves the
| row by primary key; the edition check that follows it is in the controller,
| because it depends on `Edition::current()` at request time and not on the URI.
*/

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/admin', DashboardController::class)->name('admin.dashboard');

    // --- Orders ------------------------------------------------------------
    //
    // `Order` is bound on the id, and every action re-checks the edition inside
    // the controller. The alternative — encoding `edition_id` in the URI — would
    // put the edition in every bookmark and break the moment one were wrong.

    Route::get('/admin/orders', [OrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/admin/orders/{order}', [OrderController::class, 'show'])->name('admin.orders.show');
    Route::match(['POST'], '/admin/orders/{order}/statut', [OrderController::class, 'updateStatus'])
        ->name('admin.orders.status');

    // --- Queues needing a human decision -----------------------------------

    Route::get('/admin/submissions', [SubmissionController::class, 'index'])
        ->name('admin.submissions.index');
    Route::match(['POST'], '/admin/submissions/{submission}/review', [SubmissionController::class, 'update'])
        ->name('admin.submissions.review');

    Route::get('/admin/enquiries', [EnquiryController::class, 'index'])->name('admin.enquiries.index');
    Route::match(['POST'], '/admin/enquiries/{enquiry}/statut', [EnquiryController::class, 'update'])
        ->name('admin.enquiries.status');

    Route::get('/admin/messages', [MessageController::class, 'index'])->name('admin.messages.index');
    Route::match(['POST'], '/admin/messages/{message}/statut', [MessageController::class, 'update'])
        ->name('admin.messages.status');

    // --- The door list ------------------------------------------------------
    //
    // POST for both directions of check-in. A GET that mutated a row would let
    // a link prefetcher, or a browser's history restore, check a delegate in
    // before they reached the door.

    Route::get('/admin/participants', [ParticipantController::class, 'index'])
        ->name('admin.participants.index');
    Route::match(['POST'], '/admin/participants/{participant}/check-in', [ParticipantController::class, 'checkIn'])
        ->name('admin.participants.check-in');
    Route::match(['POST'], '/admin/participants/{participant}/check-out', [ParticipantController::class, 'checkOut'])
        ->name('admin.participants.check-out');

    // --- Leaving the staff area ---------------------------------------------
    //
    // A staff member is not always a delegate, and an organiser may need to
    // hand the laptop at the desk to somebody who should not inherit the
    // session. This signs out of the session entirely rather than dropping the
    // admin flag, because the flag lives on the account and is not a session
    // attribute to abandon.

    Route::match(['POST'], '/admin/logout', \App\Http\Controllers\SessionController::class)
        ->name('admin.logout');
});