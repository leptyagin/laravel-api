<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

abstract class ApiException extends Exception
{
    public function __construct(string $message, public readonly int $statusCode)
    {
        parent::__construct($message);
    }
}
