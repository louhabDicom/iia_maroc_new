<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\Edition;
use Illuminate\Contracts\View\View;

/**
 * The "about the conference" page.
 *
 * Everything printed here is edition data or translation, so the 2027 edition
 * changes this page without a code change. The page is deliberately read-only:
 * there is no enquiry form and no booking, which is why it needs no FormRequest
 * and no validation.
 */
class PresentationController extends Controller
{
    public function __invoke(): View
    {
        $edition = Edition::current();

        if ($edition === null) {
            return view('pages.unavailable');
        }

        return view('pages.presentation', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'organisations' => $edition->organisations()
                ->published()
                ->orderBy('sort_order')
                ->get(),
            'currentRoute' => 'presentation',
        ]);
    }
}
