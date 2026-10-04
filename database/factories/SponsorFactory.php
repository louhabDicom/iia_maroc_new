<?php

namespace Database\Factories;

use App\Models\Edition;
use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'tier' => Sponsor::TIER_PARTNER,
            'name' => fake()->unique()->company(),
            // The logo columns are paths, not URLs, and the page resolves them
            // through `asset()`. A sponsor created without a logo is a real state
            // — the plate falls back to the wordmark — so it is not faked here.
            'logo_path' => null,
            'logo_mono_path' => null,
            'website_url' => 'https://'.fake()->domainName(),
            'description' => null,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    /**
     * A sponsor carried over from an earlier edition.
     *
     * These render in the courtesy rail rather than on the wall, so a test that
     * means "the page has no sponsors yet" must be able to say "previous ones
     * only" without accidentally filling the section under test.
     */
    public function previous(): static
    {
        return $this->state(fn (): array => ['tier' => Sponsor::TIER_PREVIOUS]);
    }
}
