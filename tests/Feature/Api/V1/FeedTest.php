<?php

declare(strict_types=1);

namespace Tests\Feature\Feed;

use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
use App\Models\Photo;
use App\Models\Preference;
use App\Models\Profile;
use App\Models\Swipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_asymmetric_preferences_exclude_candidate(): void
    {
        $viewer = User::factory()->create();
        $this->completeProfile($viewer, Gender::Male, age: 25, lookingFor: Gender::Female, minAge: 20, maxAge: 30);

        $candidate = User::factory()->create();
        $this->completeProfile($candidate, Gender::Female, age: 25, lookingFor: Gender::Female, minAge: 40, maxAge: 50);

        Sanctum::actingAs($viewer);

        $ids = collect($this->getJson('/api/v1/feed')->assertOk()->json('data.items'))->pluck('id');

        $this->assertFalse($ids->contains($candidate->id));
    }

    public function test_mutually_matching_profile_appears_in_feed(): void
    {
        $viewer = User::factory()->create();
        $this->completeProfile($viewer, Gender::Male, age: 25, lookingFor: Gender::Female, minAge: 20, maxAge: 30);

        $candidate = User::factory()->create();
        $this->completeProfile($candidate, Gender::Female, age: 25, lookingFor: Gender::Male, minAge: 20, maxAge: 30);

        Sanctum::actingAs($viewer);

        $ids = collect($this->getJson('/api/v1/feed')->assertOk()->json('data.items'))->pluck('id');

        $this->assertTrue($ids->contains($candidate->id));
    }

    public function test_already_swiped_profile_is_excluded(): void
    {
        $viewer = User::factory()->create();
        $this->completeProfile($viewer, Gender::Male, age: 25, lookingFor: Gender::Female, minAge: 20, maxAge: 30);

        $candidate = User::factory()->create();
        $this->completeProfile($candidate, Gender::Female, age: 25, lookingFor: Gender::Male, minAge: 20, maxAge: 30);

        Swipe::query()->create([
            'user_id_1' => min($viewer->id, $candidate->id),
            'user_id_2' => max($viewer->id, $candidate->id),
            'user_like_1' => $viewer->id < $candidate->id ? true : null,
            'user_like_2' => $viewer->id < $candidate->id ? null : true,
        ]);

        Sanctum::actingAs($viewer);

        $ids = collect($this->getJson('/api/v1/feed')->assertOk()->json('data.items'))->pluck('id');

        $this->assertFalse($ids->contains($candidate->id));
    }

    public function test_swiping_removes_candidate_from_cached_feed(): void
    {
        $viewer = User::factory()->create();
        $this->completeProfile($viewer, Gender::Male, age: 25, lookingFor: Gender::Female, minAge: 20, maxAge: 30);

        $candidate = User::factory()->create();
        $this->completeProfile($candidate, Gender::Female, age: 25, lookingFor: Gender::Male, minAge: 20, maxAge: 30);

        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/feed')->assertOk(); // прогреваем кэш

        $this->postJson("/api/v1/swipe/{$candidate->id}/dislike")->assertOk();

        $ids = collect($this->getJson('/api/v1/feed')->assertOk()->json('data.items'))->pluck('id');

        $this->assertFalse($ids->contains($candidate->id));
    }

    public function test_incomplete_profile_returns_unprocessable(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/feed')->assertStatus(422);
    }

    public function test_guest_cannot_view_feed(): void
    {
        $this->getJson('/api/v1/feed')->assertUnauthorized();
    }

    public function test_pagination_returns_next_cursor_when_more_items_remain(): void
    {
        $viewer = User::factory()->create();
        $this->completeProfile($viewer, Gender::Male, age: 25, lookingFor: Gender::Female, minAge: 20, maxAge: 30);

        User::factory()->count(3)->create()->each(
            fn (User $u) => $this->completeProfile($u, Gender::Female, age: 25, lookingFor: Gender::Male, minAge: 20, maxAge: 30)
        );

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/feed?limit=2')->assertOk();

        $this->assertCount(2, $response->json('data.items'));
        $this->assertNotNull($response->json('data.nextCursor'));
    }

    private function completeProfile(
        User $user,
        Gender $gender,
        int $age,
        Gender $lookingFor,
        int $minAge,
        int $maxAge
    ): void {
        Profile::factory()->for($user)->create([
            'gender' => $gender->value,
            'birth_date' => now()->subYears($age)->subDays(10),
            'city' => City::Montevideo->value,
            'status' => Status::Active->value,
        ]);

        Preference::factory()->for($user)->create([
            'gender' => $lookingFor->value,
            'min_age' => $minAge,
            'max_age' => $maxAge,
        ]);

        Photo::factory()->for($user)->create(['position' => 1]);
    }
}
