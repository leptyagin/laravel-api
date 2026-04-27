<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ProfileDTO;
use App\Models\Profile;
use App\Models\User;

final class ProfileService
{
    public function upsert(User $user, ProfileDTO $dto): Profile
    {
        return Profile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $dto->name,
                'birth_date' => $dto->birthDate,
                'city' => $dto->city,
                'gender' => $dto->gender,
                'bio' => $dto->bio,
                'status' => $dto->status,
            ]
        );
    }
}
