<?php

namespace Tests\Feature\Api;

use App\Models\Favourite;
use App\Models\Interest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SafetyTest extends TestCase
{
    use RefreshDatabase;

    private function makeDiscoverable(User $user): void
    {
        $user->profile()->create([
            'profile_code' => 'QSM'.str_pad((string) $user->id, 9, '0', STR_PAD_LEFT),
            'display_name' => 'Discoverable Member',
            'date_of_birth' => now()->subYears(28)->toDateString(),
            'country' => 'CA',
            'city' => 'Toronto',
            'about_me' => 'A complete profile available for discovery.',
            'visibility' => 'members',
            'moderation_status' => 'approved',
            'discovery_opt_in' => true,
        ]);
        $user->profilePhotos()->create([
            'disk' => 'profile_photos',
            'path' => "users/{$user->id}/approved-primary.jpg",
            'is_primary' => true,
            'visibility' => 'members',
            'moderation_status' => 'approved',
        ]);
    }

    public function test_blocking_is_immediate_bidirectional_and_closes_active_relationships(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $this->makeDiscoverable($member);
        $this->makeDiscoverable($other);
        Interest::create(['sender_id' => $member->id, 'receiver_id' => $other->id, 'status' => 'accepted']);
        Favourite::create(['user_id' => $member->id, 'favourite_user_id' => $other->id]);
        Favourite::create(['user_id' => $other->id, 'favourite_user_id' => $member->id]);

        Sanctum::actingAs($member);
        $this->getJson('/api/v1/matches')->assertJsonCount(1, 'data.data');
        $this->postJson('/api/v1/blocks', ['user_id' => $other->id, 'reason' => 'Safety concern'])
            ->assertCreated();
        $this->postJson('/api/v1/blocks', ['user_id' => $other->id])->assertOk();
        $this->getJson('/api/v1/matches')->assertJsonCount(0, 'data.data');

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/matches')->assertJsonCount(0, 'data.data');

        $this->assertDatabaseHas('interests', ['sender_id' => $member->id, 'receiver_id' => $other->id, 'status' => 'blocked']);
        $this->assertDatabaseCount('favourites', 0);
        $this->assertDatabaseCount('blocks', 1);
    }

    public function test_member_can_submit_only_one_open_confidential_report_for_a_profile(): void
    {
        $reporter = User::factory()->create();
        $reported = User::factory()->create();
        Sanctum::actingAs($reporter);

        $payload = ['user_id' => $reported->id, 'reason' => 'commercial_use', 'details' => 'The account offered paid bureau services.'];
        $this->postJson('/api/v1/reports', $payload)
            ->assertCreated()
            ->assertJsonPath('message', 'Report submitted confidentially.');
        $this->postJson('/api/v1/reports', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Your existing report is still under review.');

        $this->postJson('/api/v1/reports', ['user_id' => $reported->id, 'reason' => 'invalid'])->assertUnprocessable();
        $this->assertDatabaseCount('reports', 1);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_admin_can_resolve_a_report_and_suspend_the_reported_member(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create();
        $reported = User::factory()->create();
        $this->makeDiscoverable($reported);
        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reported->id,
            'reason' => 'fake_identity',
            'details' => 'Profile images appear to belong to another person.',
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/reports')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $report->id)
            ->assertJsonPath('data.data.0.reported_user.id', $reported->id);

        $this->postJson("/api/v1/admin/reports/{$report->id}/resolve", [
            'action' => 'suspended',
            'notes' => 'Identity could not be verified after review.',
        ])->assertOk()->assertJsonPath('data.status', 'resolved');

        $this->assertDatabaseHas('users', ['id' => $reported->id, 'status' => 'suspended']);
        $this->assertDatabaseHas('profiles', ['user_id' => $reported->id, 'moderation_status' => 'suspended', 'discovery_opt_in' => false]);
        $this->assertDatabaseHas('admin_audit_logs', ['admin_id' => $admin->id, 'action' => 'report.suspended', 'target_id' => $report->id]);
    }
}
