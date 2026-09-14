<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Swipe;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class UsersMatched implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public Swipe $swipe,
    ) {}
}
