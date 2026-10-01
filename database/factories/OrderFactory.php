<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Edition;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * `uuid` and `reference` are deliberately absent: the model's `creating`
     * hook derives both, exactly as it does at checkout. Overriding the
     * reference in a test should be done explicitly, not by accident.
     */
    public function definition(): array
    {
        // Standard rate for one seat, in minor units, per brief row 15.
        $standard = 850000;

        return [
            'edition_id' => Edition::factory(),
            'user_id' => User::factory(),
            'ticket_type_id' => TicketType::factory(),

            'status' => OrderStatus::Pending,
            'payment_driver' => 'cmi',

            // Snapshot of the buyer at checkout time, not a live relation.
            'billing' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'organisation' => fake()->company(),
                'address' => fake()->address(),
                'city' => 'Rabat',
                'country_iso2' => 'MA',
                'email' => fake()->unique()->safeEmail(),
                'phone' => '+2126'.fake()->unique()->numerify('########'),
            ],

            // Totals are snapshots in minor units and are never recomputed.
            'subtotal' => $standard,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => $standard,
            'currency' => 'MAD',
            'currency_numeric' => '504',

            'member_count' => 0,
            'standard_count' => 1,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }

    public function forEdition(Edition $edition): static
    {
        return $this->state(fn (): array => ['edition_id' => $edition->id]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
