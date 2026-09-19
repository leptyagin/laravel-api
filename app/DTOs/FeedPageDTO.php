<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class FeedPageDTO
{
    /**
     * @param list<ProfileDTO> $items
     */
    public function __construct(
        public array $items,
        public ?int $nextCursor,
    ) {}
}
