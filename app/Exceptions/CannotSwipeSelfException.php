<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Exceptions\ApiException;

final class CannotSwipeSelfException extends ApiException
{
    public function __construct()
    {
        parent::__construct('You cannot swipe yourself.', 409);
    }
}
