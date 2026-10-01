<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\ConferenceSession;
use App\Models\Document;
use App\Models\Edition;
use App\Models\Speaker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * The 2024 archive.
 *
 * The brief asks for the 2024 edition to be preserved in a dedicated space, not
 * deleted and not mixed into the current programme. That is why `editions` holds
 * every year: this controller reads a different row, and the same views render
 * it.
 *
 * 2024 sessions and speakers are not shown live. The legacy data was extracted
 * from a WordPress install with inconsistent encoding and untranslated content,
 * and presenting it as though it were a curated programme would misrepresent the
 * record. What is offered instead is the downloadable programme PDF that the
 * organisers kept.
 */
class ArchiveController extends Controller
{
    public function __invoke(): View
    {
        $archived = $this->requestedEdition();

        return view('pages.archive', [
            'edition' => Edition::current(),
            'locale' => Locale::parse(app()->getLocale()),
            'archived' => $archived,
            'editions' => Edition::archive(),
            'documents' => $archived === null
                ? collect()
                : $archived->documents()->published()->orderBy('sort_order')->get(),
            'stats' => $archived === null ? null : [
                'sessions' => $archived->sessions()->published()->count(),
                'speakers' => $archived->speakers()->published()->count(),
            ],
            'currentRoute' => 'archive',
        ]);
    }

    /**
     * The archived edition being asked for.
     *
     * The year comes from the route, never from the query string, and it is
     * resolved against `editions` — so a request for 2025 (which does not exist
     * yet) returns null rather than an empty page that looks broken.
     */
    private function requestedEdition(): ?Edition
    {
        return Edition::archive()->firstWhere('year', (int) request()->route('year'));
    }
}
