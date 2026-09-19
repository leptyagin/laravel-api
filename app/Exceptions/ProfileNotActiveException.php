<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ProfileNotActiveException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Activate your profile before viewing the feed.', 403);
    }
}
