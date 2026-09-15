<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_register_with_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Amina Khan',
            'email' => 'amina@example.test',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'amina@example.test')
            ->assertJsonPath('data.email_verification_required', true)
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('users', ['email' => 'amina@example.test', 'status' => 'active']);
        Notification::assertSentTo(User::where('email', 'amina@example.test')->firstOrFail(), VerifyEmail::class);
    }

    public function test_registration_requires_an_email_address(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Rayan Ali',
            'phone' => '+919876543210',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('email');
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

    public function test_signed_link_verifies_email_address(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.email_verified', true);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_member_cannot_access_member_features(): void
    {
        Sanctum::actingAs(User::factory()->unverified()->create());

        $this->getJson('/api/v1/profile')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_unverified_member_can_request_another_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/email/verification-notification')
            ->assertOk()
            ->assertJsonPath('message', 'Verification email sent.');

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
