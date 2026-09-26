<?php

namespace Tests\Feature\Api;

use App\Models\Block;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_read_and_update_notification_preferences(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/account/notification-preferences')
            ->assertOk()
            ->assertJsonPath('data.email_new_interest', true)
            ->assertJsonPath('data.email_product_updates', false);

        $this->putJson('/api/v1/account/notification-preferences', [
            'email_new_interest' => false,
            'push_new_message' => false,
        ])->assertOk()
            ->assertJsonPath('data.email_new_interest', false)
            ->assertJsonPath('data.push_new_message', false);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'email_new_interest' => false,
            'push_new_message' => false,
        ]);
    }

    public function test_member_can_revoke_every_api_session(): void
    {
        $user = User::factory()->create();
        $first = $user->createToken('first');
        $user->createToken('second');

        $this->withToken($first->plainTextToken)
            ->postJson('/api/v1/auth/logout-all')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_account_deletion_requires_explicit_confirmation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/v1/account', ['confirmation' => 'delete'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('confirmation');
    }

    public function test_member_can_delete_profile_data_and_leave_a_login_tombstone(): void
    {
        $user = User::factory()->create(['firebase_uid' => 'firebase-deleted-member']);
        $other = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        Block::create(['blocker_id' => $user->id, 'blocked_user_id' => $other->id]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/account', ['confirmation' => 'DELETE'])
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'firebase_uid' => 'firebase-deleted-member',
            'status' => 'deleted',
            'name' => 'Deleted member',
        ]);
        $this->assertDatabaseMissing('profiles', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('blocks', ['blocker_id' => $user->id]);
    }

    public function test_deleted_identity_cannot_be_reactivated_by_token_exchange(): void
    {
        User::factory()->create([
            'firebase_uid' => 'firebase-user-123',
            'email' => 'deleted-10@deleted.invalid',
            'status' => 'deleted',
        ]);
        $this->mockFirebaseClaims([
            'sub' => 'firebase-user-123',
            'email' => 'former@example.test',
            'email_verified' => true,
        ]);

        $this->withToken('valid-firebase-token')
            ->postJson('/api/v1/auth/firebase')
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'former@example.test']);
    }

    private function mockFirebaseClaims(array $claims): void
    {
        $this->mock(\App\Contracts\FirebaseTokenVerifier::class)
            ->shouldReceive('verify')
            ->once()
            ->with('valid-firebase-token')
            ->andReturn($claims);
    }
}
