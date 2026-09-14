<?php

declare(strict_types=1);

namespace Tests\Feature\Cache;

use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
use App\Events\User\UserProfileChanged;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class ProfileCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_profile_dispatches_invalidation_event(): void
    {
        Event::fake([UserProfileChanged::class]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'name' => 'John Doe',
            'birth_date' => '1990-01-01',
            'city' => City::Montevideo->value,
            'gender' => Gender::Male->value,
            'bio' => 'Hello',
            'status' => Status::Active->value,
        ])->assertOk();

        Event::assertDispatched(
            UserProfileChanged::class,
            fn (UserProfileChanged $event): bool => $event->userId === $user->id
        );
    }

    public function test_updating_preferences_dispatches_invalidation_event(): void
    {
        Event::fake([UserProfileChanged::class]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/preferences', [
            'gender' => Gender::Female->value,
            'min_age' => 20,
            'max_age' => 30,
        ])->assertOk();

        Event::assertDispatched(
            UserProfileChanged::class,
            fn (UserProfileChanged $event): bool => $event->userId === $user->id
        );
    }

    public function test_uploading_photo_dispatches_invalidation_event(): void
    {
        UploadedFile::fake();
        Event::fake([UserProfileChanged::class]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Storage::fake('public');
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->postJson('/api/v1/photos', ['photo' => $file])->assertOk();

        Event::assertDispatched(
            UserProfileChanged::class,
            fn (UserProfileChanged $event): bool => $event->userId === $user->id
        );
    }
}
