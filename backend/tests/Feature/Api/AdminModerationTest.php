<?php

namespace Tests\Feature\Api;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_account_can_be_promoted_to_admin_from_the_console(): void
    {
        $user = User::factory()->create(['email' => 'operator@example.test']);

        $this->artisan('qismat:admin', ['email' => 'OPERATOR@example.test'])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'admin']);
    }

    public function test_member_cannot_access_admin_routes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'Administrator access is required.');
    }

    public function test_inactive_admin_cannot_access_admin_routes(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin', 'status' => 'suspended']));

        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }

    public function test_admin_can_view_dashboard_and_pending_profiles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create();
        $profile = $member->profile()->create([
            'profile_code' => 'QSM000000001',
            'display_name' => 'Amina',
            'moderation_status' => 'pending',
            'submitted_at' => now(),
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.registered_users', 1)
            ->assertJsonPath('data.pending_verification', 1);

        $this->getJson('/api/v1/admin/profiles')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $profile->id)
            ->assertJsonPath('data.data.0.user.email', $member->email);
    }

    public function test_admin_can_approve_a_pending_profile_and_action_is_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $profile = $this->pendingProfile();
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/admin/profiles/{$profile->id}/review", ['decision' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.moderation_status', 'approved')
            ->assertJsonPath('data.discovery_opt_in', false);

        $this->assertDatabaseHas('profiles', ['id' => $profile->id, 'moderation_status' => 'approved']);
        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_id' => $admin->id,
            'action' => 'profile.approved',
            'target_id' => $profile->id,
        ]);
    }

    public function test_rejection_requires_a_reason_and_cannot_review_twice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $profile = $this->pendingProfile();
        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/admin/profiles/{$profile->id}/review", ['decision' => 'rejected'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->postJson("/api/v1/admin/profiles/{$profile->id}/review", [
            'decision' => 'rejected',
            'reason' => 'Biography needs clarification.',
        ])->assertOk()
            ->assertJsonPath('data.moderation_status', 'rejected')
            ->assertJsonPath('data.moderation_feedback', 'Biography needs clarification.')
            ->assertJsonPath('data.moderated_by', $admin->id);

        $this->postJson("/api/v1/admin/profiles/{$profile->id}/review", ['decision' => 'approved'])
            ->assertStatus(409);
    }

    private function pendingProfile(): Profile
    {
        return User::factory()->create()->profile()->create([
            'profile_code' => 'QSM'.fake()->unique()->numerify('#########'),
            'display_name' => fake()->name(),
            'moderation_status' => 'pending',
            'submitted_at' => now(),
        ]);
    }
}
