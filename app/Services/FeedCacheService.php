<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\FeedCacheServiceInterface;
use Closure;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final readonly class FeedCacheService implements FeedCacheServiceInterface
{
    private const string PREFIX = 'user_feed:';

    private const string LOCK_PREFIX = 'user_feed_lock:';

    private const int LOCK_TTL_SECONDS = 10;

    private const int LOCK_WAIT_SECONDS = 5;

    public function __construct(
        private CacheRepository $cache,
    ) {}

    public function remember(int $userId, Closure $rebuild): array
    {
        $payload = $this->payload($userId);

        if ($payload !== null) {
            return $payload['ids'];
        }

        return $this->withLock($userId, function () use ($userId, $rebuild): array {
            $payload = $this->payload($userId);

            if ($payload !== null) {
                return $payload['ids'];
            }

            $ids = $rebuild();

            $this->store($userId, $ids, now()->getTimestamp() + (int) config('feed.ttl'));

            return $ids;
        });
    }

    public function removeCandidate(int $userId, int $candidateId): void
    {
        $this->withLock($userId, function () use ($userId, $candidateId): void {
            $payload = $this->payload($userId);

            if ($payload === null) {
                return;
            }

            $ids = array_values(array_diff($payload['ids'], [$candidateId]));

            if (count($ids) === count($payload['ids'])) {
                return;
            }

            if (count($ids) < (int) config('feed.refill_threshold')) {
                $this->invalidate($userId);

                return;
            }

            $this->store($userId, $ids, $payload['expires_at']);
        });
    }

    public function invalidate(int $userId): void
    {
        $this->cache->forget($this->key($userId));
    }

    /**
     * @return array{ids: list<int>, expires_at: int}|null
     */
    private function payload(int $userId): ?array
    {
        $payload = $this->cache->get($this->key($userId));

        if (! is_array($payload) || ! isset($payload['ids'], $payload['expires_at']) || $payload['expires_at'] <= now()->getTimestamp()) {
            return null;
        }

        /** @var list<int> $ids */
        $ids = $payload['ids'];

        return ['ids' => $ids, 'expires_at' => (int) $payload['expires_at']];
    }

    /**
     * @param list<int> $ids
     */
    private function store(int $userId, array $ids, int $expiresAt): void
    {
        $ttl = $expiresAt - now()->getTimestamp();

        if ($ttl <= 0) {
            return;
        }

        $this->cache->put($this->key($userId), ['ids' => $ids, 'expires_at' => $expiresAt], $ttl);
    }

    /**
     * @template T
     *
     * @param Closure(): T $callback
     * @return T
     */
    private function withLock(int $userId, Closure $callback): mixed
    {
        $store = $this->cache->getStore();

        if (! $store instanceof LockProvider) {
            return $callback();
        }

        return $store
            ->lock(self::LOCK_PREFIX.$userId, self::LOCK_TTL_SECONDS)
            ->block(self::LOCK_WAIT_SECONDS, $callback);
    }

    private function key(int $userId): string
    {
        return self::PREFIX.$userId;
    }
}
