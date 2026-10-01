<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // Unique across the whole test run: the column is indexed, and a
            // collision would fail as a constraint violation rather than a
            // readable assertion.
            'phone' => '+2126'.fake()->unique()->numerify('########'),
            'phone_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'terms_accepted_at' => now(),
            'remember_token' => Str::random(10),
            // Registration always records a locale and SetLocale reads it off
            // the user. A DB default is not enough: the in-memory model only
            // carries the attributes the factory set, and strict mode turns a
            // read of anything else into a MissingAttributeException.
            'locale' => 'fr',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A user who cannot order: no verified phone, no accepted terms.
     */
    public function unverifiedPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
            'terms_accepted_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function member(): static
    {
        return $this->afterCreating(function (User $user): void {
            Membership::factory()->create(['user_id' => $user->id, 'status' => MembershipStatus::Active]);
        });
    }
}
