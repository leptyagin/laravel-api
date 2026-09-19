<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners\User;

use App\Contracts\FeedCacheServiceInterface;
use App\Events\User\UserProfileChanged;
use App\Listeners\User\InvalidateFeedCache;
use Mockery;
use Tests\TestCase;

final class InvalidateFeedCacheTest extends TestCase
{
    public function test_it_invalidates_feed_for_correct_user(): void
    {
        $feedCache = Mockery::mock(FeedCacheServiceInterface::class);
        $feedCache->shouldReceive('invalidate')
            ->once()
            ->with(42);

        $listener = new InvalidateFeedCache($feedCache);

        $listener->handle(new UserProfileChanged(userId: 42));
    }
}
