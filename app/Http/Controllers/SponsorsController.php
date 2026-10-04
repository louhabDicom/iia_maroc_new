<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\Document;
use App\Models\Edition;
use App\Models\Sponsor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * Sponsoring.
 *
 * The page answers a commercial question, so it is read-only like the
 * presentation page and needs no FormRequest.
 *
 * Two separate sponsor reads rather than one list filtered twice, because they
 * answer two different questions and sit in two different sections: `$sponsors`
 * is the edition being sold, `$previousSponsors` is the courtesy strip for the
 * editions that came before it.
 *
 * The tier catalogue (`Sponsor::tierOrder()`) is deliberately NOT rendered
 * here. The page states that opportunities are defined case by case and points
 * at the downloadable dossier, which is where the package grid and the terms
 * live; a table of tiers on the page would contradict that and go stale the
 * moment the dossier is reissued.
 */
class SponsorsController extends Controller
{
    /**
     * How many plates the "logo to come" wall reserves.
     *
     * The wall is a fixed four-up so it keeps the reference layout whether
     * three sponsors have confirmed or none have, and so a lone sponsor does
     * not sit in a row of one stretched across the section. Reserved rather
     * than rendered as a caption, because a plate that says "logo to come" is
     * an honest statement about a page that is still being sold, where an empty
     * row reads as a page that failed to load.
     */
    private const PENDING_SLOTS = 4;

    public function __invoke(): View
    {
        $edition = Edition::current();

        if ($edition === null) {
            return view('pages.unavailable');
        }

        $sponsors = $edition->sponsors()
            ->published()
            ->where('tier', '!=', Sponsor::TIER_PREVIOUS)
            ->inDisplayOrder()
            ->get();

        return view('pages.sponsors', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'sponsors' => $sponsors,
            'previousSponsors' => $edition->sponsors()
                ->published()
                ->where('tier', Sponsor::TIER_PREVIOUS)
                ->inDisplayOrder()
                ->get(),
            'dossier' => $this->dossier($edition, app()->getLocale()),
            'gallery' => $this->gallery(),
            'pendingSlots' => $this->pendingSlots($sponsors->count()),
            'currentRoute' => 'sponsors',
        ]);
    }

    /**
     * The plates the wall still reserves.
     *
     * Returns `[]` rather than a descending range once the wall is full: PHP's
     * `range(1, 0)` is `[1, 0]`, not an empty array, so a page with four
     * confirmed sponsors would have printed two extra "logo to come" plates.
     *
     * @return list<int>
     */
    private function pendingSlots(int $confirmed): array
    {
        $remaining = self::PENDING_SLOTS - $confirmed;

        return $remaining > 0 ? range(1, $remaining) : [];
    }

    /**
     * The downloadable sponsorship dossier, when one is published.
     *
     * Null rather than an empty link: a download button that points at nothing
     * is worse than one that sends the visitor to the organisers, so the view
     * swaps the button for a contact link when this is null.
     */
    private function dossier(Edition $edition, string $locale): ?Document
    {
        return $edition->documents()
            ->published()
            ->ofType(Document::TYPE_SPONSORSHIP_DOSSIER)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->first(fn (Document $document): bool => $document->isAvailableIn($locale));
    }

    /**
     * Photographs of the conferences that came before.
     *
     * These are files on disk rather than rows, so the list is a scan of the six
     * slots the archive page also uses. A missing file is skipped rather than
     * rendered as a broken image: this set is allowed to be incomplete, and a
     * grey rectangle where a photograph should be is worse than one fewer
     * picture.
     *
     * @return Collection<int, array{file: string, width: int, height: int}>
     */
    private function gallery(): Collection
    {
        return collect(range(1, 6))
            ->map(function (int $index): ?array {
                $file = "assets/images/conference/previous-editions-0{$index}.jpg";

                return file_exists(public_path($file))
                    ? ['file' => $file, 'width' => 613, 'height' => 408]
                    : null;
            })
            ->filter()
            ->values();
    }
}
