<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class StoreProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_profile(): void
    {
        $this->postJson('/api/v1/profile', [])
            ->assertUnauthorized();
    }

    public function test_user_can_create_profile(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/profile', [
            'name' => 'John Doe',
            'birth_date' => '1990-01-01',
            'city' => City::Montevideo->value,
            'gender' => Gender::Male->value,
            'bio' => 'Hello',
            'status' => Status::Active->value,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'John Doe',
                    'status' => 'active',
                ],
            ]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'name' => 'John',
            'birth_date' => '1990-01-01',
            'city' => City::Montevideo->value,
            'gender' => Gender::Male->value,
            'bio' => 'Old bio',
            'status' => Status::Active->value,
        ]);

        $this->postJson('/api/v1/profile', [
            'name' => 'John Updated',
            'birth_date' => '1990-01-01',
            'city' => City::BuenosAires->value,
            'gender' => Gender::Female->value,
            'bio' => 'New bio',
            'status' => Status::Active->value,
        ]);

        $this->assertDatabaseHas('profiles', [
            'name' => 'John Updated',
            'city' => 'buenos_aires',
        ]);
    }

    public function test_validation_fails_with_invalid_data(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', [
            'name' => 'J',
            'birth_date' => 'invalid-date',
            'city' => 'invalid',
            'gender' => 'invalid',
            'bio' => '',
            'status' => Status::Active->value,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'birth_date',
                'city',
                'gender',
                'bio',
            ]);
    }
}
