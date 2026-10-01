<?php

namespace Database\Factories;

use App\Casts\TranslationPayload;
use App\Models\Edition;
use App\Models\Speaker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Speaker>
 */
class SpeakerFactory extends Factory
{
    protected $model = Speaker::class;

    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        // The brief requires exactly three sentences, so the default bio is
        // built as three (faker's sentence() already ends with a period)
        // rather than left to paragraph(), which produces an arbitrary count
        // and would fail hasValidBiography().
        $biography = implode(' ', [
            fake()->sentence(9),
            fake()->sentence(7),
            fake()->sentence(5),
        ]);

        return [
            'edition_id' => Edition::factory(),
            'first_name' => $first,
            'last_name' => $last,
            'job_title' => TranslationPayload::encode([
                'fr' => 'Directeur de recherche',
                'en' => 'Research director',
                'ar' => 'مدير البحث',
            ]),
            'organisation' => fake()->company(),
            'country_iso2' => fake()->randomElement(['MA', 'FR', 'ES', 'DZ', 'TN']),
            'biography' => $biography,
            'biography_sentences' => 3,
            'photo_path' => 'speakers/'.$first.'-'.$last.'.jpg',
            'email' => fake()->unique()->safeEmail(),
            'talk_title' => TranslationPayload::encode([
                'fr' => 'Titre de la communication',
                'en' => 'Talk title',
                'ar' => 'عنوان العرض',
            ]),
            'status' => Speaker::STATUS_CONFIRMED,
            'is_keynote' => false,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status' => Speaker::STATUS_CONFIRMED,
            'is_published' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => Speaker::STATUS_PENDING,
            'is_published' => false,
        ]);
    }

    public function keynote(): static
    {
        return $this->state(fn (): array => ['is_keynote' => true]);
    }
}
