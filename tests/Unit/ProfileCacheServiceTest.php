<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\ProfileCacheServiceInterface;
use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
use App\Models\Profile;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProfileCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_profile_dto_matching_stored_data(): void
    {
        $user = $this->createUserWithCompleteProfile();
        $service = $this->app->make(ProfileCacheServiceInterface::class);

        $dto = $service->get($user->id);

        $this->assertSame($user->id, $dto->id);
        $this->assertSame($user->name, $dto->name);
        $this->assertSame(25, $dto->age->value);
        $this->assertSame(City::Montevideo, $dto->city);
        $this->assertSame('Hello there', $dto->bio);
        $this->assertSame(Status::Active, $dto->status);
        $this->assertSame(Gender::Male, $dto->gender);
        $this->assertSame(Gender::Female, $dto->lookingFor);
        $this->assertSame(20, $dto->partnerMinAge->value);
        $this->assertSame(35, $dto->partnerMaxAge->value);
        $this->assertNull($dto->photo);
    }

    public function test_get_throws_when_profile_is_missing(): void
    {
        $user = User::factory()->create();
        $user->preferences()->create([
            'gender' => Gender::Female,
            'min_age' => 20,
            'max_age' => 35,
        ]);
        $service = $this->app->make(ProfileCacheServiceInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Profile incomplete');

        $service->get($user->id);
    }

    public function test_get_throws_when_preferences_are_missing(): void
    {
        $user = User::factory()->create();
        $this->makeProfile($user);
        $service = $this->app->make(ProfileCacheServiceInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Profile incomplete');

        $service->get($user->id);
    }

    public function test_second_call_is_served_from_cache_without_hitting_the_database(): void
    {
        $user = $this->createUserWithCompleteProfile();
        $service = $this->app->make(ProfileCacheServiceInterface::class);

        $service->get($user->id);

        DB::enableQueryLog();
        $service->get($user->id);

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_cached_result_is_stale_until_explicitly_invalidated(): void
    {
        $user = $this->createUserWithCompleteProfile();
        $service = $this->app->make(ProfileCacheServiceInterface::class);

        $service->get($user->id);

        Profile::query()->where('user_id', $user->id)->update(['bio' => 'Updated bio']);
        $stillCached = $service->get($user->id);

        $this->assertSame('Hello there', $stillCached->bio);
    }

    public function test_invalidate_forces_the_next_get_to_read_fresh_data(): void
    {
        $user = $this->createUserWithCompleteProfile();
        $service = $this->app->make(ProfileCacheServiceInterface::class);

        $service->get($user->id);

        Profile::query()->where('user_id', $user->id)->update(['bio' => 'Updated bio']);
        $service->invalidate($user->id);
        $fresh = $service->get($user->id);

        $this->assertSame('Updated bio', $fresh->bio);
    }

    /**
     * capture the current behavior
     * @todo need to be fixed to assertNotNull($dto->photo) later
     */
    public function test_photo_is_still_null_even_when_user_has_an_uploaded_photo(): void
    {
        $user = $this->createUserWithCompleteProfile();
        $user->photos()->create(['path' => 'photos/avatar.jpg']);
        $service = $this->app->make(ProfileCacheServiceInterface::class);

        $dto = $service->get($user->id);

        $this->assertNull($dto->photo);
    }

    private function createUserWithCompleteProfile(): User
    {
        $user = User::factory()->create();

        $this->makeProfile($user);

        $user->preferences()->create([
            'gender' => Gender::Female,
            'min_age' => 20,
            'max_age' => 35,
        ]);

        return $user;
    }

    private function makeProfile(User $user): Profile
    {
        return $user->profile()->create([
            'name' => $user->name,
            'birth_date' => now()->subYears(25),
            'gender' => Gender::Male,
            'city' => City::Montevideo,
            'bio' => 'Hello there',
            'status' => Status::Active,
        ]);
    }
}
