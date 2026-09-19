<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class FeedPageParamsDTO
{
    public function __construct(
        public ?int $cursor,
        public int $limit,
    ) {}
}
