<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UsersMatched;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SendMatchNotifications implements ShouldQueue
{
    public function handle(UsersMatched $event): void
    {
        // TODO: push/email notifications to both users
    }
}
