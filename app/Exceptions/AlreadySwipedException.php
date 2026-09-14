<?php

declare(strict_types=1);

namespace App\Exceptions;

final class AlreadySwipedException extends ApiException
{
    public function __construct()
    {
        parent::__construct('You have already swiped this profile.', 409);
    }
}
