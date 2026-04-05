<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_api_login_returns_token(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_api_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
            'device_name' => 'test',
        ]);

        $response->assertStatus(422);
    }

    public function test_api_register_creates_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'API User',
            'email' => 'api@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'device_name' => 'test',
        ]);

        $response->assertStatus(201)->assertJsonStructure(['token', 'user']);
        $this->assertDatabaseHas('users', ['email' => 'api@example.com']);
    }

    public function test_authenticated_user_can_access_protected_route(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/auth/user', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $response = $this->getJson('/api/v1/cart');
        $response->assertStatus(401);
    }
}
