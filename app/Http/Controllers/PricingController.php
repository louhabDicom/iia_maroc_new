<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\Edition;
use App\Models\TicketType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Registration fees.
 *
 * The member and standard prices are two columns of the same row, and which one
 * applies is decided by `TicketType::priceFor()` reading a Membership record —
 * never by a hidden form field. That is the fix for the 2024 vulnerability where
 * the member rate was selected by a `type_adherent` POST value that the client
 * controlled.
 */
class PricingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $edition = Edition::current();

        if ($edition === null) {
            return view('pages.unavailable');
        }

        $user = $request->user();

        return view('pages.pricing', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'ticketTypes' => $edition->ticketTypes()
                ->active()
                ->orderBy('sort_order')
                ->get(),
            // The visitor's own status, so the page can label the price that
            // will actually be charged. It does not grant it: the server
            // recomputes the price when the order is created.
            'isMember' => $user?->isActiveMember() ?? false,
            'registrationOpen' => $edition->registration_open,
            'canOrder' => $user?->canRegister() ?? false,
            'currentRoute' => 'pricing',
        ]);
    }
}
