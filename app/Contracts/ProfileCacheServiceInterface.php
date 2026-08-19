<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\ProfileDTO;

interface ProfileCacheServiceInterface
{
    public function get(int $userId): ProfileDTO;

    public function invalidate(int $userId): void;
}
