<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners\User;

use App\Contracts\ProfileCacheServiceInterface;
use App\Events\User\UserProfileChanged;
use App\Listeners\User\InvalidateProfileCache;
use Mockery;
use Tests\TestCase;

final class InvalidateProfileCacheTest extends TestCase
{
    public function test_it_invalidates_cache_for_correct_user(): void
    {
        $cacheService = Mockery::mock(ProfileCacheServiceInterface::class);
        $cacheService->shouldReceive('invalidate')
            ->once()
            ->with(42);

        $listener = new InvalidateProfileCache($cacheService);

        $listener->handle(new UserProfileChanged(userId: 42));
    }
}
