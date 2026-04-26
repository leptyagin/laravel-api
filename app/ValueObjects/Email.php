<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

final readonly class Email
{
    public function __construct(
        public string $value,
    ) {
        throw_unless(filter_var($value, FILTER_VALIDATE_EMAIL), InvalidArgumentException::class, 'Invalid email format');
    }

    public function toString(): string
    {
        return $this->value;
    }
}
