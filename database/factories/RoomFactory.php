<?php

namespace Database\Factories;

use App\Casts\TranslationPayload;
use App\Models\Edition;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        $name = 'Salle '.fake()->unique()->numberBetween(1, 40);

        return [
            'edition_id' => Edition::factory(),
            'code' => 'room-'.fake()->unique()->slug(2),
            'name' => TranslationPayload::encode([
                'fr' => $name,
                'en' => 'Room '.substr($name, 6),
                'ar' => 'قاعة '.$name,
            ]),
            'capacity' => fake()->randomElement([50, 80, 120, 200, 300]),
            'floor' => fake()->numberBetween(0, 3),
            'is_published' => true,
        ];
    }
}
