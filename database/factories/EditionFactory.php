<?php

namespace Database\Factories;

use App\Casts\TranslationPayload;
use App\Enums\EditionStatus;
use App\Models\Edition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Edition>
 */
class EditionFactory extends Factory
{
    protected $model = Edition::class;

    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2024, 2030);

        return [
            'year' => $year,
            'code' => 'ARABCIA'.$year,
            'organiser' => 'ARABCIA',
            'host_institute' => 'IIA Maroc',
            'title' => TranslationPayload::encode([
                'fr' => 'Conférence internationale ARABCIA '.$year,
                'en' => 'ARABCIA '.$year.' International Conference',
                'ar' => 'المؤتمر الدولي ARABCIA '.$year,
            ]),
            'theme' => TranslationPayload::encode([
                'fr' => "Thème de l'édition ".$year,
                'en' => "Edition theme ".$year,
                'ar' => 'موضوع النسخة '.$year,
            ]),
            'introduction' => TranslationPayload::encode([
                'fr' => 'Introduction',
                'en' => 'Introduction',
                'ar' => 'مقدمة',
            ]),
            'city' => 'Rabat',
            'country_iso2' => 'MA',
            'venue_name' => 'Four Seasons Hotel Rabat',
            'starts_on' => now()->addMonths(2)->startOfDay(),
            'ends_on' => now()->addMonths(2)->addDay()->startOfDay(),
            // Plain array, not json_encode(): the model casts `languages` with
            // 'array', which encodes on write and would double-encode a string.
            'languages' => ['fr', 'en', 'ar'],
            'status' => EditionStatus::Draft,
            'is_current' => false,
            'registration_open' => false,
        ];
    }

    public function current(): static
    {
        return $this->state(fn (): array => [
            'status' => EditionStatus::Published,
            'is_current' => true,
            'registration_open' => true,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => EditionStatus::Archived,
            'is_current' => false,
            'registration_open' => false,
        ]);
    }
}
