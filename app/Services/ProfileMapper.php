<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ProfileDTO;
use App\Models\User;
use App\ValueObjects\Age;
use DomainException;

final class ProfileMapper
{
    public function map(User $user): ProfileDTO
    {
        throw_if(! $user->profile || ! $user->preferences, DomainException::class, 'Profile incomplete');

        $profile = $user->profile;
        $prefs = $user->preferences;

        return new ProfileDTO(
            id: $user->id,
            name: $user->name,
            age: Age::fromBirthDate($profile->birth_date),
            city: $profile->city,
            bio: $profile->bio ?? '',
            status: $profile->status,
            photo: $user->photos->first()?->url,
            gender: $profile->gender,
            lookingFor: $prefs->gender,
            partnerMaxAge: new Age($prefs->max_age),
            partnerMinAge: new Age($prefs->min_age),
        );
    }
}
