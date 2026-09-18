<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Profile> */
final class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->firstName(),
            'birth_date' => fake()->dateTimeBetween('-45 years', '-18 years'),
            'city' => fake()->randomElement(City::cases())->value,
            'gender' => fake()->randomElement(Gender::cases())->value,
            'bio' => fake()->sentence(),
            'status' => Status::Active->value,
        ];
    }

    public function male(): self
    {
        return $this->state(fn (): array => ['gender' => Gender::Male->value]);
    }

    public function female(): self
    {
        return $this->state(fn (): array => ['gender' => Gender::Female->value]);
    }

    public function withAge(int $age): self
    {
        return $this->state(fn (): array => [
            'birth_date' => now()->subYears($age)->subDays(10),
        ]);
    }

    public function status(Status $status): self
    {
        return $this->state(fn (): array => ['status' => $status->value]);
    }
}
