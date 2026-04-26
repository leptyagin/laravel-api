<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\Carbon;
use InvalidArgumentException;

final readonly class Age
{
    public function __construct(
        public int $value,
    ) {
        throw_if($value < 18, InvalidArgumentException::class, 'User must be at least 18 years old');

        throw_if($value > 100, InvalidArgumentException::class, 'Age must be less than 100');
    }

    public static function fromBirthDate(Carbon $birthDate): self
    {
        return new self(
            value: $birthDate->age
        );
    }
}
