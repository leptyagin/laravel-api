<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\SwipeDirection;
use App\Exceptions\AlreadySwipedException;
use App\Exceptions\CannotSwipeSelfException;
use App\Models\User;
use App\Services\SwipeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
