<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Events\User\UserProfileChanged;
use App\Services\User\ProfileCacheService;

final readonly class InvalidateProfileCache
{
    public function __construct(
        private ProfileCacheService $cacheService,
    ) {}

    public function handle(UserProfileChanged $event): void
    {
        $this->cacheService->invalidate($event->userId);
    }
}
