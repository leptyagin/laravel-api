<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\User;

final class ProfileQueryService
{
    public function getById(int $userId): User
    {
        return User::query()
            ->with([
                'profile',
                'preferences',
                'photos' => fn ($q) => $q->where('position', 1),
            ])
            ->findOrFail($userId);
    }
}
