<?php

namespace Tests\Feature\Api;

use App\Models\Interest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InterestTest extends TestCase
{
    use RefreshDatabase;

    private function makeDiscoverable(User $user): void
    {
        $user->profile()->create([
            'profile_code' => 'QSM'.str_pad((string) $user->id, 9, '0', STR_PAD_LEFT),
            'display_name' => 'Available Member',
            'date_of_birth' => now()->subYears(25)->toDateString(),
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

    public function test_duplicate_interests_are_prevented_and_only_one_activity_is_recorded(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $this->makeDiscoverable($receiver);
        Sanctum::actingAs($sender);

        $payload = ['receiver_id' => $receiver->id, 'message' => 'Would like to connect.'];

        $this->postJson('/api/v1/interests', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->postJson('/api/v1/interests', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Interest already exists.');

        $this->assertDatabaseCount('interests', 1);
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseCount('member_notifications', 1);
        $this->assertDatabaseHas('member_notifications', ['user_id' => $receiver->id, 'type' => 'interest_received']);
    }

    public function test_interest_cannot_bypass_profile_discovery_rules(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        Sanctum::actingAs($sender);

        $this->postJson('/api/v1/interests', ['receiver_id' => $receiver->id])
            ->assertNotFound();

        $this->makeDiscoverable($receiver);
        $this->postJson('/api/v1/interests', ['receiver_id' => $receiver->id])
            ->assertCreated();

        $receiver->profile()->update(['visibility' => 'private']);
        $this->postJson('/api/v1/interests', ['receiver_id' => $receiver->id])
            ->assertNotFound();
    }

    public function test_only_the_receiver_can_accept_an_interest(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $interest = Interest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($sender);
        $this->postJson("/api/v1/interests/{$interest->id}/respond", ['status' => 'accepted'])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        Sanctum::actingAs($receiver);
        $this->postJson("/api/v1/interests/{$interest->id}/respond", ['status' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('interests', ['id' => $interest->id, 'status' => 'accepted']);
        $this->assertDatabaseHas('conversations', [
            'user_one_id' => min($sender->id, $receiver->id),
            'user_two_id' => max($sender->id, $receiver->id),
        ]);
        $this->assertDatabaseHas('member_notifications', ['user_id' => $sender->id, 'type' => 'interest_accepted']);
    }

    public function test_members_can_list_and_cancel_their_pending_interests(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $this->makeDiscoverable($receiver);
        $interest = Interest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => 'pending',
            'message' => 'I appreciated your profile.',
        ]);

        Sanctum::actingAs($sender);
        $this->getJson('/api/v1/interests?direction=sent')
            ->assertOk()
            ->assertJsonPath('data.data.0.direction', 'sent')
            ->assertJsonPath('data.data.0.member.user_id', $receiver->id)
            ->assertJsonPath('data.data.0.message', 'I appreciated your profile.');

        $this->deleteJson("/api/v1/interests/{$interest->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->deleteJson("/api/v1/interests/{$interest->id}")->assertStatus(409);
    }

    public function test_an_interest_cannot_be_answered_twice(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $interest = Interest::create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id, 'status' => 'pending']);
        Sanctum::actingAs($receiver);

        $this->postJson("/api/v1/interests/{$interest->id}/respond", ['status' => 'declined'])->assertOk();
        $this->postJson("/api/v1/interests/{$interest->id}/respond", ['status' => 'accepted'])->assertStatus(409);

        $this->assertDatabaseHas('interests', ['id' => $interest->id, 'status' => 'declined']);
    }
}
