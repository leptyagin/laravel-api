<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\StoreProfileDTO;
use App\Events\User\UserProfileChanged;
use App\Models\Profile;
use App\Models\User;

final class ProfileService
{
    public function upsert(User $user, StoreProfileDTO $dto): Profile
    {
        $profile = Profile::query()->updateOrCreate(
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

        UserProfileChanged::dispatch($user->id);

        return $profile;
    }
}
