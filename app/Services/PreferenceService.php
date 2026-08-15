<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PreferenceDTO;
use App\Events\User\UserProfileChanged;
use App\Models\Preference;
use App\Models\User;

final class PreferenceService
{
    public function upsert(User $user, PreferenceDTO $dto): Preference
    {
        $preference = Preference::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'gender' => $dto->gender,
                'min_age' => $dto->minAge->value,
                'max_age' => $dto->maxAge->value,
            ]
        );

        UserProfileChanged::dispatch($user->id);

        return $preference;
    }
}
