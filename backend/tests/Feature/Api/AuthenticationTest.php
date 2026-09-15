<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_register_with_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Amina Khan',
            'email' => 'amina@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'amina@example.test')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('users', ['email' => 'amina@example.test', 'status' => 'active']);
    }

    public function test_member_can_register_with_phone_only(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Rayan Ali',
            'phone' => '+919876543210',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertCreated()->assertJsonPath('data.user.phone', '+919876543210');
    }

    public function test_member_can_login_and_fetch_current_account(): void
    {
        User::factory()->create([
            'email' => 'member@example.test',
            'password' => 'StrongPass123!',
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'login' => 'member@example.test',
            'password' => 'StrongPass123!',
        ])->assertOk()->assertJsonPath('success', true);

        $this->withToken($login->json('data.token'))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'member@example.test');
    }

    public function test_inactive_member_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'suspended@example.test',
            'password' => 'StrongPass123!',
            'status' => 'suspended',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'suspended@example.test',
            'password' => 'StrongPass123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('login');
    }

    public function test_authenticated_member_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_protected_route_returns_the_api_error_contract(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'message' => 'Unauthenticated.',
                'errors' => [],
            ]);
    }
}
