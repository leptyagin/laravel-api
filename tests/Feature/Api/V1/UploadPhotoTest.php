<?php

declare(strict_types=1);

namespace Tests\Feature\Photo;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class UploadPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->postJson('/api/v1/photos', [
            'photo' => $file,
        ]);

        $response->assertOk();

        $path = $response->json('data.path');

        Storage::disk('public')->assertExists($path);
    }

    public function test_photo_positions_are_assigned_sequentially_per_user(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $other = User::factory()->create();

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/photos', ['photo' => UploadedFile::fake()->image('a.jpg')])->assertOk();
        $this->postJson('/api/v1/photos', ['photo' => UploadedFile::fake()->image('b.jpg')])->assertOk();

        Sanctum::actingAs($other);
        $this->postJson('/api/v1/photos', ['photo' => UploadedFile::fake()->image('c.jpg')])->assertOk();

        $this->assertSame([1, 2], $user->photos()->orderBy('id')->pluck('position')->all());
        $this->assertSame([1], $other->photos()->pluck('position')->all());
    }

    public function test_photo_url_points_to_the_stored_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $path = $this->postJson('/api/v1/photos', ['photo' => UploadedFile::fake()->image('a.jpg')])
            ->assertOk()
            ->json('data.path');

        $this->assertSame(Storage::disk('public')->url($path), $user->photos()->firstOrFail()->url);
    }

    public function test_validation_fails_with_invalid_file(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $file = UploadedFile::fake()->create('file.pdf');

        $this->postJson('/api/v1/photos', [
            'photo' => $file,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_guest_cannot_upload_photo(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->postJson('/api/v1/photos', [
            'photo' => $file,
        ])
            ->assertUnauthorized();
    }
}
