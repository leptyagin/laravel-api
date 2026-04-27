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

        $this->postJson('/api/v1/photos', [
            'photo' => $file,
        ])
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'path',
            ]);

        Storage::disk('public')->assertExists('photos/'.$file->hashName());
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
