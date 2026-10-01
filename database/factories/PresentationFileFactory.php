<?php

namespace Database\Factories;

use App\Models\ConferenceSession;
use App\Models\Edition;
use App\Models\PresentationFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PresentationFile>
 */
class PresentationFileFactory extends Factory
{
    protected $model = PresentationFile::class;

    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'conference_session_id' => ConferenceSession::factory(),
            'title' => 'Slides - '.fake()->words(3, true),
            'file_path' => 'presentations/2026/'.fake()->uuid().'.pdf',
            'thumbnail_path' => null,
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(200_000, 12_000_000),
            'sort_order' => 0,
            // Unpublished by default: the brief defers public uploads to the end
            // of the conference.
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['is_published' => true]);
    }
}
