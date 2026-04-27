<?php

declare(strict_types=1);

namespace Tests\Feature\Preference;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class StorePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_preferences(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $data = [
            'gender' => Gender::Female->value,
            'min_age' => 20,
            'max_age' => 30,
        ];

        $this->postJson('/api/v1/preferences', $data)
            ->assertOk()
            ->assertJson([
                'gender' => Gender::Female->value,
                'min_age' => 20,
                'max_age' => 30,
            ]);

        $this->assertDatabaseHas('preferences', [
            'user_id' => $user->id,
            'gender' => Gender::Female->value,
            'min_age' => 20,
            'max_age' => 30,
        ]);
    }

    public function test_user_can_update_preferences(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/preferences', [
            'gender' => Gender::Male->value,
            'min_age' => 18,
            'max_age' => 25,
        ]);

        $this->postJson('/api/v1/preferences', [
            'gender' => Gender::Female->value,
            'min_age' => 21,
            'max_age' => 35,
        ])
            ->assertOk();

        $this->assertDatabaseHas('preferences', [
            'user_id' => $user->id,
            'gender' => Gender::Female->value,
            'min_age' => 21,
            'max_age' => 35,
        ]);
    }

    public function test_validation_fails_with_invalid_data(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/preferences', [
            'gender' => 'invalid',
            'min_age' => 10,
            'max_age' => 200,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'gender',
                'min_age',
                'max_age',
            ]);

        $this->assertDatabaseCount('preferences', 0);
    }

    public function test_validation_fails_when_min_age_greater_than_max_age(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/preferences', [
            'gender' => Gender::Male->value,
            'min_age' => 40,
            'max_age' => 20,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['max_age']);
    }

    public function test_guest_cannot_create_preferences(): void
    {
        $this->postJson('/api/v1/preferences', [
            'gender' => Gender::Male->value,
            'min_age' => 18,
            'max_age' => 30,
        ])
            ->assertUnauthorized();
    }
}
