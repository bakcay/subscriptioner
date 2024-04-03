<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'email'             => fake()->unique()->safeEmail(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'email_verified_at' => now(),
            'name'              => fake()->name(),
            'address1'          => fake()->address(),
            'city'              => fake()->city(),
            'region'            => fake()->state(),
            'country'           => fake()->countryCode(),
            'phone'             => '+90.5'.fake()->numberBetween(300000000,559999999),
            'tax_office'        => fake()->company(),
            'tax_number'        => fake()->numberBetween(1000000000,9999999999),
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
}
