<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\FeedCacheServiceInterface;
use App\Events\UserSwiped;
use Throwable;

final readonly class RemoveCandidateFromFeed
{
    public function __construct(
        private FeedCacheServiceInterface $feedCache,
    ) {}

    public function handle(UserSwiped $event): void
    {
        try {
            $this->feedCache->removeCandidate($event->actorId, $event->targetId);
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }
}
