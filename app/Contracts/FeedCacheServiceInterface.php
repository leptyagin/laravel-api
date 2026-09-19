<?php

declare(strict_types=1);

namespace App\Contracts;

use Closure;

interface FeedCacheServiceInterface
{
    /**
     * @param Closure(): list<int> $rebuild
     * @return list<int>
     */
    public function remember(int $userId, Closure $rebuild): array;

    public function removeCandidate(int $userId, int $candidateId): void;

    public function invalidate(int $userId): void;
}
