<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\Edition;
use Illuminate\Contracts\View\View;

class SpeakersController extends Controller
{
    public function __invoke(): View
    {
        $edition = Edition::current();

        if ($edition === null) {
            return view('pages.unavailable');
        }

        return view('pages.speakers', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),

            'keynotes' => $edition->speakers()
                ->published()
                ->keynotes()
                ->with('sessions')
                ->orderBy('sort_order')
                ->get(),

            'speakers' => $edition->speakers()
                ->published()
                ->where('is_keynote', false)
                ->with('sessions')
                ->orderBy('sort_order')
                ->orderBy('last_name')
                ->get(),

            'currentRoute' => 'speakers',
        ]);
    }
}
