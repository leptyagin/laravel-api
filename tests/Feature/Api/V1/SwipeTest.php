<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Swipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SwipeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_like_another_profile(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/swipe/{$target->id}/like")
            ->assertOk()
            ->assertJson(['data' => ['matched' => false]]);

        $this->assertDatabaseHas('swipes', [
            'user_id_1' => min($user->id, $target->id),
            'user_id_2' => max($user->id, $target->id),
            'matched_at' => null,
        ]);
    }

    public function test_mutual_like_creates_match(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Sanctum::actingAs($userA);
        $this->postJson("/api/v1/swipe/{$userB->id}/like")
            ->assertJson(['data' => ['matched' => false]]);

        Sanctum::actingAs($userB);
        $this->postJson("/api/v1/swipe/{$userA->id}/like")
            ->assertOk()
            ->assertJson(['data' => ['matched' => true]]);

        $this->assertDatabaseHas('swipes', [
            'user_id_1' => min($userA->id, $userB->id),
            'user_id_2' => max($userA->id, $userB->id),
        ]);

        $swipe = Swipe::query()->first();
        $this->assertNotNull($swipe->matched_at);
    }

    public function test_like_then_dislike_from_other_side_does_not_match(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Sanctum::actingAs($userA);
        $this->postJson("/api/v1/swipe/{$userB->id}/like");

        Sanctum::actingAs($userB);
        $this->postJson("/api/v1/swipe/{$userA->id}/dislike")
            ->assertJson(['data' => ['matched' => false]]);

        $this->assertDatabaseHas('swipes', ['matched_at' => null]);
    }

    public function test_user_cannot_swipe_self(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/swipe/{$user->id}/like")
            ->assertStatus(409);
    }

    public function test_user_cannot_swipe_same_profile_twice(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Sanctum::actingAs($userA);

        $this->postJson("/api/v1/swipe/{$userB->id}/like")->assertOk();
        $this->postJson("/api/v1/swipe/{$userB->id}/like")->assertStatus(409);
    }

    public function test_swipe_order_is_canonical_regardless_of_who_acts_first(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Sanctum::actingAs($userB);

        $this->postJson("/api/v1/swipe/{$userA->id}/like")->assertOk();

        $this->assertDatabaseHas('swipes', [
            'user_id_1' => min($userA->id, $userB->id),
            'user_id_2' => max($userA->id, $userB->id),
        ]);
    }

    public function test_guest_cannot_swipe(): void
    {
        $target = User::factory()->create();

        $this->postJson("/api/v1/swipe/{$target->id}/like")
            ->assertUnauthorized();
    }
}
