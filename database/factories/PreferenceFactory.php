<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Preference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Preference> */
final class PreferenceFactory extends Factory
{
    protected $model = Preference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'gender' => fake()->randomElement(Gender::cases())->value,
            'min_age' => 18,
            'max_age' => 40,
        ];
    }

    public function lookingFor(Gender $gender): self
    {
        return $this->state(fn (): array => ['gender' => $gender->value]);
    }

    public function ageRange(int $min, int $max): self
    {
        return $this->state(fn (): array => ['min_age' => $min, 'max_age' => $max]);
    }
}
