<?php

declare(strict_types=1);

namespace App\Services\User;

use App\DTOs\ProfileDTO;
use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
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
            city: City::from($profile->city_value),
            bio: $profile->bio ?? '',
            status: Status::from($user->status),
            photo: $user->photos->first()?->url,
            gender: Gender::from($profile->gender),
            lookingFor: Gender::from($prefs->looking_for),
            partnerMaxAge: new Age($prefs->max_age),
            partnerMinAge: new Age($prefs->min_age),
        );
    }
}
