<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Contracts\FeedCacheServiceInterface;
use App\Events\UserSwiped;
use App\Listeners\RemoveCandidateFromFeed;
use Illuminate\Support\Facades\Exceptions;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class RemoveCandidateFromFeedTest extends TestCase
{
    public function test_it_removes_the_target_from_the_actors_feed(): void
    {
        $feedCache = Mockery::mock(FeedCacheServiceInterface::class);
        $feedCache->shouldReceive('removeCandidate')
            ->once()
            ->with(1, 2);

        $listener = new RemoveCandidateFromFeed($feedCache);

        $listener->handle(new UserSwiped(actorId: 1, targetId: 2));
    }

    public function test_cache_failure_is_reported_but_not_thrown(): void
    {
        Exceptions::fake();

        $feedCache = Mockery::mock(FeedCacheServiceInterface::class);
        $feedCache->shouldReceive('removeCandidate')->andThrow(new RuntimeException('redis is down'));

        $listener = new RemoveCandidateFromFeed($feedCache);

        $listener->handle(new UserSwiped(actorId: 1, targetId: 2));

        Exceptions::assertReported(fn (RuntimeException $e): bool => $e->getMessage() === 'redis is down');
    }
}
