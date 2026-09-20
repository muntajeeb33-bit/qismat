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
    }
}
