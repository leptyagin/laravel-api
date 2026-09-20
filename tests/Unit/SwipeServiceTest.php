<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\SwipeDirection;
use App\Events\UsersMatched;
use App\Events\UserSwiped;
use App\Exceptions\AlreadySwipedException;
use App\Exceptions\CannotSwipeSelfException;
use App\Models\User;
use App\Services\SwipeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class SwipeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_throws_on_self_swipe(): void
    {
        $user = User::factory()->create();
        $service = $this->app->make(SwipeService::class);

        $this->expectException(CannotSwipeSelfException::class);

        $service->swipe($user, $user, SwipeDirection::Like);
    }

    public function test_dispatches_user_swiped_for_every_swipe_and_users_matched_only_on_match(): void
    {
        Event::fake([UserSwiped::class, UsersMatched::class]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $service = $this->app->make(SwipeService::class);

        $service->swipe($userA, $userB, SwipeDirection::Like);

        Event::assertDispatched(UserSwiped::class, fn (UserSwiped $e): bool => $e->actorId === $userA->id && $e->targetId === $userB->id);
        Event::assertNotDispatched(UsersMatched::class);

        $service->swipe($userB, $userA, SwipeDirection::Like);

        Event::assertDispatchedTimes(UserSwiped::class, 2);
        Event::assertDispatchedTimes(UsersMatched::class, 1);
    }

    public function test_failed_swipe_does_not_dispatch_user_swiped(): void
    {
        Event::fake([UserSwiped::class]);

        $user = User::factory()->create();
        $service = $this->app->make(SwipeService::class);

        try {
            $service->swipe($user, $user, SwipeDirection::Like);
        } catch (CannotSwipeSelfException) {
        }

        Event::assertNotDispatched(UserSwiped::class);
    }

    public function test_throws_on_duplicate_swipe(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $service = $this->app->make(SwipeService::class);

        $service->swipe($userA, $userB, SwipeDirection::Like);

        $this->expectException(AlreadySwipedException::class);

        $service->swipe($userA, $userB, SwipeDirection::Dislike);
    }
}
