<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\FeedCacheServiceInterface;
use Tests\TestCase;

final class FeedCacheServiceTest extends TestCase
{
    private FeedCacheServiceInterface $cache;

    private int $rebuilds = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['feed.ttl' => 60, 'feed.refill_threshold' => 2]);

        $this->cache = $this->app->make(FeedCacheServiceInterface::class);
        $this->rebuilds = 0;
    }

    public function test_pool_is_built_once_and_then_served_from_cache(): void
    {
        $this->assertSame([5, 4, 3], $this->remember([5, 4, 3]));
        $this->assertSame([5, 4, 3], $this->remember([9, 9, 9]));

        $this->assertSame(1, $this->rebuilds);
    }

    public function test_pool_is_rebuilt_after_ttl_expires(): void
    {
        $this->remember([5, 4, 3]);

        $this->travel(61)->seconds();

        $this->assertSame([9, 8, 7], $this->remember([9, 8, 7]));
        $this->assertSame(2, $this->rebuilds);
    }

    public function test_empty_pool_is_cached_like_any_other(): void
    {
        $this->remember([]);
        $this->remember([]);

        $this->assertSame(1, $this->rebuilds);
    }

    public function test_removing_a_candidate_drops_it_from_the_pool(): void
    {
        $this->remember([5, 4, 3, 2]);

        $this->cache->removeCandidate(1, 4);

        $this->assertSame([5, 3, 2], $this->remember([]));
        $this->assertSame(1, $this->rebuilds);
    }

    public function test_removing_a_candidate_does_not_prolong_the_pool_lifetime(): void
    {
        $this->remember([5, 4, 3, 2]);

        $this->travel(40)->seconds();
        $this->cache->removeCandidate(1, 4);

        $this->travel(30)->seconds();

        $this->assertSame([9, 8, 7], $this->remember([9, 8, 7]));
        $this->assertSame(2, $this->rebuilds);
    }

    public function test_removing_a_candidate_that_is_not_in_the_pool_changes_nothing(): void
    {
        $this->remember([5, 4, 3]);

        $this->cache->removeCandidate(1, 42);

        $this->assertSame([5, 4, 3], $this->remember([]));
        $this->assertSame(1, $this->rebuilds);
    }

    public function test_pool_is_dropped_when_it_runs_below_the_refill_threshold(): void
    {
        $this->remember([5, 4, 3]);

        $this->cache->removeCandidate(1, 5);
        $this->assertSame([4, 3], $this->remember([]));

        $this->cache->removeCandidate(1, 4);

        $this->assertSame([2, 1], $this->remember([2, 1]));
        $this->assertSame(2, $this->rebuilds);
    }

    public function test_removing_from_a_missing_pool_is_a_noop(): void
    {
        $this->cache->removeCandidate(1, 5);

        $this->assertSame([7, 6], $this->remember([7, 6]));
        $this->assertSame(1, $this->rebuilds);
    }

    public function test_invalidate_forces_a_rebuild(): void
    {
        $this->remember([5, 4, 3]);

        $this->cache->invalidate(1);

        $this->assertSame([9, 8], $this->remember([9, 8]));
        $this->assertSame(2, $this->rebuilds);
    }

    public function test_pools_of_different_users_are_independent(): void
    {
        $this->cache->remember(1, fn (): array => [5, 4, 3]);
        $this->cache->remember(2, fn (): array => [9, 8, 7]);

        $this->cache->removeCandidate(1, 4);

        $this->assertSame([5, 3], $this->cache->remember(1, fn (): array => []));
        $this->assertSame([9, 8, 7], $this->cache->remember(2, fn (): array => []));
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function remember(array $ids): array
    {
        return $this->cache->remember(1, function () use ($ids): array {
            $this->rebuilds++;

            return $ids;
        });
    }
}
