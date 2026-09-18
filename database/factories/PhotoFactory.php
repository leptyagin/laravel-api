<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Photo> */
final class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'path' => 'photos/'.fake()->uuid().'.jpg',
            'position' => 1,
        ];
    }

    public function position(int $position): self
    {
        return $this->state(fn (): array => ['position' => $position]);
    }
}
