<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

abstract class ApiException extends DomainException
{
    public function __construct(string $message, public readonly int $statusCode)
    {
        parent::__construct($message);
    }
}
