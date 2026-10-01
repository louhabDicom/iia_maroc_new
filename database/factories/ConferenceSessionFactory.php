<?php

namespace Database\Factories;

use App\Casts\TranslationPayload;
use App\Enums\SessionFormat;
use App\Models\ConferenceSession;
use App\Models\Edition;
use App\Models\Room;
use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConferenceSession>
 */
class ConferenceSessionFactory extends Factory
{
    protected $model = ConferenceSession::class;

    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('09:00', '16:00');
        $endsAt = (clone $startsAt)->modify('+45 minutes');

        return [
            'edition_id' => Edition::factory(),
            'format' => SessionFormat::Plenary,
            'title' => TranslationPayload::encode([
                'fr' => 'Plénière '.$this->faker->sentence(3),
                'en' => 'Plenary '.$this->faker->sentence(3),
                'ar' => 'plenière '.$this->faker->sentence(3),
            ]),
            'summary' => TranslationPayload::encode([
                'fr' => $this->faker->paragraph(),
                'en' => $this->faker->paragraph(),
                'ar' => $this->faker->paragraph(),
            ]),
            'session_date' => now()->addMonth()->toDateString(),
            'starts_at' => $startsAt->format('H:i:s'),
            'ends_at' => $endsAt->format('H:i:s'),
            'language' => 'fr',
            'interpretation_available' => true,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function forEdition(Edition $edition): static
    {
        return $this->state(fn (): array => ['edition_id' => $edition->id]);
    }

    public function inRoom(Room $room): static
    {
        return $this->state(fn (): array => ['room_id' => $room->id]);
    }

    public function onTrack(Track $track): static
    {
        return $this->state(fn (): array => ['track_id' => $track->id]);
    }

    public function format(SessionFormat $format): static
    {
        return $this->state(fn (): array => ['format' => $format]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
