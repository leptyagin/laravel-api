<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Contracts\ProfileCacheServiceInterface;
use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
use App\Events\User\UserProfileChanged;
use App\Models\Photo;
use App\Models\Preference;
use App\Models\Profile;
use App\Models\Swipe;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_asymmetric_preferences_exclude_candidate(): void
    {
        $viewer = $this->viewer();
        $candidate = $this->candidate(lookingFor: Gender::Female, minAge: 40, maxAge: 50);

        Sanctum::actingAs($viewer);

        $this->assertNotContains($candidate->id, $this->feedIds());
    }

    public function test_mutually_matching_profile_appears_in_feed(): void
    {
        $viewer = $this->viewer();
        $candidate = $this->candidate();

        Sanctum::actingAs($viewer);

        $this->assertContains($candidate->id, $this->feedIds());
    }

    public function test_profile_the_viewer_already_swiped_is_excluded(): void
    {
        $viewer = $this->viewer();
        $candidate = $this->candidate();
        $this->recordSwipe(swiper: $viewer, target: $candidate, liked: true);

        Sanctum::actingAs($viewer);

        $this->assertNotContains($candidate->id, $this->feedIds());
    }

    public function test_profile_who_liked_the_viewer_stays_in_feed(): void
    {
        $viewer = $this->viewer();
        $candidate = $this->candidate();
        $this->recordSwipe(swiper: $candidate, target: $viewer, liked: true);

        Sanctum::actingAs($viewer);

        $this->assertContains($candidate->id, $this->feedIds());
    }

    public function test_viewer_can_match_with_someone_found_through_the_feed(): void
    {
        $viewer = $this->viewer();
        $candidate = $this->candidate();

        Sanctum::actingAs($candidate);
        $this->postJson("/api/v1/swipe/{$viewer->id}/like")->assertOk();

        Sanctum::actingAs($viewer);
        $this->assertContains($candidate->id, $this->feedIds());
        $this->postJson("/api/v1/swipe/{$candidate->id}/like")
            ->assertOk()
            ->assertJson(['data' => ['matched' => true]]);

        $this->assertNotContains($candidate->id, $this->feedIds());
    }

    public function test_swiping_removes_candidate_from_cached_feed(): void
    {
        $viewer = $this->viewer();
        $candidate = $this->candidate();

        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/feed')->assertOk();

        $this->postJson("/api/v1/swipe/{$candidate->id}/dislike")->assertOk();

        $this->assertNotContains($candidate->id, $this->feedIds());
    }

    public function test_age_range_is_inclusive_on_both_ends(): void
    {
        $viewer = $this->viewer(minAge: 20, maxAge: 30);

        $tooYoung = $this->candidate(age: today()->subYears(20)->addDay());
        $minEdge = $this->candidate(age: today()->subYears(20));
        $maxEdge = $this->candidate(age: today()->subYears(31)->addDay());
        $tooOld = $this->candidate(age: today()->subYears(31));

        Sanctum::actingAs($viewer);

        $ids = $this->feedIds();

        $this->assertContains($minEdge->id, $ids);
        $this->assertContains($maxEdge->id, $ids);
        $this->assertNotContains($tooYoung->id, $ids);
        $this->assertNotContains($tooOld->id, $ids);
    }

    public function test_incomplete_profile_returns_unprocessable(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/feed')->assertStatus(422);
    }

    public function test_viewer_with_inactive_profile_is_forbidden(): void
    {
        foreach ([Status::Draft, Status::Hidden, Status::Banned] as $status) {
            $viewer = $this->viewer(status: $status);
            $this->candidate();

            Sanctum::actingAs($viewer);

            $this->getJson('/api/v1/feed')->assertForbidden();
        }
    }

    public function test_guest_cannot_view_feed(): void
    {
        $this->getJson('/api/v1/feed')->assertUnauthorized();
    }

    public function test_invalid_pagination_params_are_rejected(): void
    {
        Sanctum::actingAs($this->viewer());

        $this->getJson('/api/v1/feed?limit=0')->assertStatus(422)->assertJsonValidationErrors(['limit']);
        $this->getJson('/api/v1/feed?limit='.((int) config('feed.max_limit') + 1))->assertStatus(422);
        $this->getJson('/api/v1/feed?cursor=0')->assertStatus(422)->assertJsonValidationErrors(['cursor']);
    }

    public function test_cursor_walks_through_all_candidates_without_gaps_or_duplicates(): void
    {
        $viewer = $this->viewer();
        $expected = [];
        foreach (range(1, 5) as $ignored) {
            $expected[] = $this->candidate()->id;
        }

        rsort($expected);

        Sanctum::actingAs($viewer);

        $seen = [];
        $pages = 0;
        $cursor = null;

        do {
            $response = $this->getJson('/api/v1/feed?limit=2'.($cursor === null ? '' : "&cursor={$cursor}"))->assertOk();
            $seen = [...$seen, ...$this->idsOf($response)];
            $cursor = $response->json('data.next_cursor');
            $pages++;
        } while ($cursor !== null);

        $this->assertSame($expected, $seen);
        $this->assertSame(3, $pages);
    }

    public function test_cursor_stays_valid_after_swiping_the_previous_page(): void
    {
        $viewer = $this->viewer();
        $candidateIds = [];
        foreach (range(1, 6) as $ignored) {
            $candidateIds[] = $this->candidate()->id;
        }

        rsort($candidateIds);

        Sanctum::actingAs($viewer);

        $firstPage = $this->getJson('/api/v1/feed?limit=3')->assertOk();
        $this->assertSame(array_slice($candidateIds, 0, 3), $this->idsOf($firstPage));

        foreach ($this->idsOf($firstPage) as $id) {
            $this->postJson("/api/v1/swipe/{$id}/dislike")->assertOk();
        }

        $secondPage = $this->getJson('/api/v1/feed?limit=3&cursor='.$firstPage->json('data.next_cursor'))->assertOk();

        $this->assertSame(array_slice($candidateIds, 3, 3), $this->idsOf($secondPage));
        $this->assertNull($secondPage->json('data.next_cursor'));
    }

    public function test_pool_is_rebuilt_when_the_user_swipes_through_it(): void
    {
        config(['feed.pool_size' => 3, 'feed.refill_threshold' => 2]);

        $viewer = $this->viewer();
        $candidateIds = [];
        foreach (range(1, 6) as $ignored) {
            $candidateIds[] = $this->candidate()->id;
        }

        rsort($candidateIds);

        Sanctum::actingAs($viewer);

        $firstBatch = $this->feedIds();
        $this->assertSame(array_slice($candidateIds, 0, 3), $firstBatch);

        foreach ($firstBatch as $id) {
            $this->postJson("/api/v1/swipe/{$id}/dislike")->assertOk();
        }

        $this->assertSame(array_slice($candidateIds, 3, 3), $this->feedIds());
    }

    public function test_changing_preferences_rebuilds_the_feed(): void
    {
        $viewer = $this->viewer();
        $candidate = $this->candidate();

        Sanctum::actingAs($viewer);
        $this->assertContains($candidate->id, $this->feedIds());

        $this->postJson('/api/v1/preferences', [
            'gender' => Gender::Male->value,
            'min_age' => 20,
            'max_age' => 30,
        ])->assertOk();

        Sanctum::actingAs($viewer->fresh());

        $this->assertNotContains($candidate->id, $this->feedIds());
    }

    public function test_candidate_banned_after_the_pool_was_cached_disappears_from_feed(): void
    {
        $viewer = $this->viewer();
        $candidate = $this->candidate();

        Sanctum::actingAs($viewer);
        $this->assertContains($candidate->id, $this->feedIds());

        Profile::query()->where('user_id', $candidate->id)->update(['status' => Status::Banned->value]);
        UserProfileChanged::dispatch($candidate->id);

        $this->assertNotContains($candidate->id, $this->feedIds());
    }

    public function test_candidate_deleted_after_the_pool_was_cached_does_not_break_the_feed(): void
    {
        $viewer = $this->viewer();
        $deleted = $this->candidate();
        $stays = $this->candidate();

        Sanctum::actingAs($viewer);
        $this->assertContains($deleted->id, $this->feedIds());

        $this->app->make(ProfileCacheServiceInterface::class)->invalidate($deleted->id);
        $deleted->delete();

        $ids = $this->feedIds();

        $this->assertNotContains($deleted->id, $ids);
        $this->assertContains($stays->id, $ids);
    }

    public function test_pagination_returns_next_cursor_when_more_items_remain(): void
    {
        $viewer = $this->viewer();
        foreach (range(1, 3) as $ignored) {
            $this->candidate();
        }

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/feed?limit=2')->assertOk();

        $this->assertCount(2, $response->json('data.items'));
        $this->assertNotNull($response->json('data.next_cursor'));
    }

    public function test_response_exposes_only_public_candidate_fields(): void
    {
        $viewer = $this->viewer();
        $this->candidate();

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/feed')->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'items' => [['id', 'name', 'age', 'city', 'bio', 'photo', 'gender']],
                'next_cursor',
            ],
        ]);
        $response->assertJsonPath('data.items.0.age', 25);
        $response->assertJsonPath('data.items.0.gender', Gender::Female->value);
        $response->assertJsonMissingPath('data.items.0.lookingFor');
        $response->assertJsonMissingPath('data.items.0.partnerMinAge');
        $response->assertJsonMissingPath('data.items.0.partnerMaxAge');
        $response->assertJsonMissingPath('data.items.0.status');
        $this->assertStringContainsString('photos/', (string) $response->json('data.items.0.photo'));
    }

    private function viewer(int $minAge = 20, int $maxAge = 30, Status $status = Status::Active): User
    {
        $viewer = User::factory()->create();

        $this->completeProfile($viewer, Gender::Male, age: 25, lookingFor: Gender::Female, minAge: $minAge, maxAge: $maxAge, status: $status);

        return $viewer;
    }

    private function candidate(
        Carbon|int $age = 25,
        Gender $lookingFor = Gender::Male,
        int $minAge = 20,
        int $maxAge = 30,
    ): User {
        $candidate = User::factory()->create();

        $this->completeProfile($candidate, Gender::Female, age: $age, lookingFor: $lookingFor, minAge: $minAge, maxAge: $maxAge);

        return $candidate;
    }

    private function completeProfile(
        User $user,
        Gender $gender,
        Carbon|int $age,
        Gender $lookingFor,
        int $minAge,
        int $maxAge,
        Status $status = Status::Active,
    ): void {
        Profile::factory()->for($user)->create([
            'gender' => $gender->value,
            'birth_date' => $age instanceof Carbon ? $age : now()->subYears($age)->subDays(10),
            'city' => City::Montevideo->value,
            'status' => $status->value,
        ]);

        Preference::factory()->for($user)->create([
            'gender' => $lookingFor->value,
            'min_age' => $minAge,
            'max_age' => $maxAge,
        ]);

        Photo::factory()->for($user)->create(['position' => 1]);
    }

    private function recordSwipe(User $swiper, User $target, bool $liked): void
    {
        $swiperFirst = $swiper->id < $target->id;

        Swipe::query()->create([
            'user_id_1' => min($swiper->id, $target->id),
            'user_id_2' => max($swiper->id, $target->id),
            'user_like_1' => $swiperFirst ? $liked : null,
            'user_like_2' => $swiperFirst ? null : $liked,
        ]);
    }

    /**
     * @return list<int>
     */
    private function feedIds(): array
    {
        return $this->idsOf($this->getJson('/api/v1/feed')->assertOk());
    }

    /**
     * @param TestResponse<JsonResponse> $response
     * @return list<int>
     */
    private function idsOf(TestResponse $response): array
    {
        return array_map(intval(...), array_column($response->json('data.items'), 'id'));
    }
}
