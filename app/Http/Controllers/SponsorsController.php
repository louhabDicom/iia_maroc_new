<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\ContentBlock;
use App\Models\Edition;
use App\Models\Room;
use App\Models\Sponsor;
use Illuminate\Contracts\View\View;

/**
 * Partners and sponsoring.
 *
 * Tier sections are rendered for every tier in the catalogue, including the
 * empty ones. A blank gold section reads as "gold sponsorship is still
 * available", which is the point of showing it — but only if the section is
 * present when nobody has bought it yet.
 */
class SponsorsController extends Controller
{
    public function __invoke(): View
    {
        $edition = Edition::current();

        if ($edition === null) {
            return view('pages.unavailable');
        }

        // Sponsors for the current edition (ARABCIA 2026)
        $sponsors = $edition->sponsors()
            ->published()
            ->where('tier', '!=', Sponsor::TIER_PREVIOUS)
            ->inDisplayOrder()
            ->get();

        // Previous edition sponsors (shown as courtesy strip)
        $previousSponsors = $edition->sponsors()
            ->published()
            ->where('tier', Sponsor::TIER_PREVIOUS)
            ->inDisplayOrder()
            ->get();

        return view('pages.sponsors', [
            'edition' => $edition,
            'locale' => Locale::parse(app()->getLocale()),
            'tiers' => $this->groupByTier($sponsors),
            'tierOrder' => Sponsor::tierOrder(),
            'organisations' => $edition->organisations()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->get(),
            'previousSponsors' => $previousSponsors,
            // Tier pricing and benefits live in content blocks so an editor can
            // change them without a deploy, and so the figure shown here is the
            // same string that goes on the invoice.
            'tierContent' => ContentBlock::query()
                ->where('edition_id', $edition->getKey())
                ->inGroup('sponsoring')
                ->published()
                ->get()
                ->keyBy('key'),
            'currentRoute' => 'sponsors',
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Sponsor>  $sponsors
     * @return array<string, \Illuminate\Support\Collection<int, Sponsor>>
     */
    private function groupByTier(\Illuminate\Support\Collection $sponsors): array
    {
        // `groupBy()` returns a Collection; `all()` flattens it to a plain array
        // of Collections, which is what the declared return type promises. The
        // view iterates the array with a `foreach` and indexes it by tier, so the
        // top level needs to be a real array to keep missing tiers absent.
        return $sponsors->groupBy('tier')->all();
    }
}
