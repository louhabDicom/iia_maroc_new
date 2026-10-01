<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reference' => 'ADH-'.fake()->unique()->numerify('#####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'job_title' => fake()->jobTitle(),
            'organisation' => fake()->company(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-22 years')->format('Y-m-d'),
            'address' => fake()->address(),
            'professional_email' => fake()->unique()->companyEmail(),
            'landline' => '+212522'.fake()->numerify('######'),
            'mobile' => '+2126'.fake()->numerify('########'),
            'status' => MembershipStatus::Pending,
            'amount_paid' => 50000,
            'currency' => 'MAD',
            'activated_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => MembershipStatus::Active,
            'activated_at' => now(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->id]);
    }
}
