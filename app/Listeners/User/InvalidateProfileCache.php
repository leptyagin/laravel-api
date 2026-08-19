<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Contracts\ProfileCacheServiceInterface;
use App\Events\User\UserProfileChanged;

final readonly class InvalidateProfileCache
{
    public function __construct(
        private ProfileCacheServiceInterface $cacheService,
    ) {}

    public function handle(UserProfileChanged $event): void
    {
        $this->cacheService->invalidate($event->userId);
    }
}
