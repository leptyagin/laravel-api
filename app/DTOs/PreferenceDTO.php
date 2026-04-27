<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\Gender;
use App\ValueObjects\Age;

final readonly class PreferenceDTO
{
    public function __construct(
        public Gender $gender,
        public Age $minAge,
        public Age $maxAge,
    ) {}
}
