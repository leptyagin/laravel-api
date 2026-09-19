<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Contracts\FeedCacheServiceInterface;
use App\Events\User\UserProfileChanged;

final readonly class InvalidateFeedCache
{
    public function __construct(
        private FeedCacheServiceInterface $feedCache,
    ) {}

    public function handle(UserProfileChanged $event): void
    {
        $this->feedCache->invalidate($event->userId);
    }
}
