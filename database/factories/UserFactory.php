<?php

namespace Database\Factories;

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
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'timezone' => 'UTC',
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
     * An account made with Google sign-in, which has no password.
     */
    public function google(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => null,
            'google_id' => (string) fake()->unique()->randomNumber(9, true),
        ]);
    }

    public function demo(): static
    {
        return $this->state(['is_demo' => true]);
    }

    public function sample(): static
    {
        return $this->state(['is_sample' => true, 'password' => null]);
    }
}
