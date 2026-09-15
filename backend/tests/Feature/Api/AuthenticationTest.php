<?php

namespace Tests\Feature\Api;

use App\Contracts\FirebaseTokenVerifier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_firebase_exchange_requires_an_id_token(): void
    {
        $this->postJson('/api/v1/auth/firebase')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_invalid_firebase_token_is_rejected(): void
    {
        $this->mock(FirebaseTokenVerifier::class)
            ->shouldReceive('verify')
            ->once()
            ->with('invalid-token')
            ->andThrow(new RuntimeException('Invalid token'));

        $this->withToken('invalid-token')
            ->postJson('/api/v1/auth/firebase')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Firebase ID token is invalid or expired.');
    }

    public function test_verified_firebase_user_is_synchronized_and_receives_a_sanctum_token(): void
    {
        $this->mockFirebaseClaims([
            'sub' => 'firebase-user-123',
            'email' => 'amina@example.test',
            'email_verified' => true,
            'name' => 'Amina Khan',
        ]);

        $response = $this->withToken('valid-firebase-token')
            ->postJson('/api/v1/auth/firebase')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'amina@example.test')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('users', ['firebase_uid' => 'firebase-user-123', 'email' => 'amina@example.test']);

        $this->withToken($response->json('data.token'))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'amina@example.test');
    }

    public function test_unverified_firebase_email_is_rejected(): void
    {
        $this->mockFirebaseClaims([
            'sub' => 'firebase-user-123',
            'email' => 'unverified@example.test',
            'email_verified' => false,
        ]);

        $this->withToken('valid-firebase-token')
            ->postJson('/api/v1/auth/firebase')
            ->assertForbidden()
            ->assertJsonPath('message', 'Firebase email address is not verified.');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_existing_firebase_identity_is_not_duplicated(): void
    {
        User::factory()->create(['firebase_uid' => 'firebase-user-123', 'email' => 'amina@example.test']);
        $this->mockFirebaseClaims([
            'sub' => 'firebase-user-123',
            'email' => 'amina@example.test',
            'email_verified' => true,
            'name' => 'Amina Updated',
        ]);

        $this->withToken('valid-firebase-token')->postJson('/api/v1/auth/firebase')->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['firebase_uid' => 'firebase-user-123', 'name' => 'Amina Updated']);
    }

    public function test_suspended_qismat_account_cannot_exchange_a_firebase_token(): void
    {
        User::factory()->create([
            'firebase_uid' => 'firebase-user-123',
            'email' => 'suspended@example.test',
            'status' => 'suspended',
        ]);
        $this->mockFirebaseClaims([
            'sub' => 'firebase-user-123',
            'email' => 'suspended@example.test',
            'email_verified' => true,
        ]);

        $this->withToken('valid-firebase-token')
            ->postJson('/api/v1/auth/firebase')
            ->assertForbidden()
            ->assertJsonPath('message', 'This Qismat account is not active.');
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

    private function mockFirebaseClaims(array $claims): void
    {
        $this->mock(FirebaseTokenVerifier::class)
            ->shouldReceive('verify')
            ->once()
            ->with('valid-firebase-token')
            ->andReturn($claims);
    }
}
