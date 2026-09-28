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
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'name_bn' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '017'.str_pad((string) fake()->unique()->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'manager',
            'is_active' => true,
            'two_factor_enabled' => false,
            'two_factor_method' => 'sms',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
