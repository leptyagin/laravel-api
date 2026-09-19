<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Status;
use App\Exceptions\IncompleteProfileException;
use App\Models\User;
use App\ValueObjects\Age;
use Illuminate\Database\Query\Builder;

final readonly class FeedQueryService
{
    /**
     * @return list<int>
     */
    public function candidateIdsFor(User $user, int $limit): array
    {
        $profile = $user->profile;
        $preference = $user->preferences;

        throw_if(
            $profile === null || $preference === null,
            IncompleteProfileException::class
        );

        $myAge = Age::fromBirthDate($profile->birth_date)->value;

        $latestBirthDate = today()->subYears($preference->min_age);
        $earliestBirthDate = today()->subYears($preference->max_age + 1)->addDay();

        return User::query()
            ->select('users.id')
            ->join('profiles', 'profiles.user_id', '=', 'users.id')
            ->join('preferences', 'preferences.user_id', '=', 'users.id')
            ->where('users.id', '!=', $user->id)
            ->where('profiles.status', Status::Active->value)
            ->where('profiles.gender', $preference->gender->value)
            ->whereBetween('profiles.birth_date', [$earliestBirthDate->toDateString(), $latestBirthDate->toDateString()])
            ->where('preferences.gender', $profile->gender->value)
            ->where('preferences.min_age', '<=', $myAge)
            ->where('preferences.max_age', '>=', $myAge)
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')
                ->from('swipes')
                ->where('swipes.user_id_1', $user->id)
                ->whereColumn('swipes.user_id_2', 'users.id')
                ->whereNotNull('swipes.user_like_1'))
            ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')
                ->from('swipes')
                ->where('swipes.user_id_2', $user->id)
                ->whereColumn('swipes.user_id_1', 'users.id')
                ->whereNotNull('swipes.user_like_2'))
            ->orderByDesc('users.id')
            ->limit($limit)
            ->pluck('users.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }
}
