<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Swipe;

final readonly class SwipeResultDTO
{
    public function __construct(
        public bool $matched,
        public Swipe $swipe,
    ) {}
}