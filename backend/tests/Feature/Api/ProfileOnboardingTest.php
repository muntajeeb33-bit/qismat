<?php

namespace Tests\Feature\Api;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function completeProfile(User $user): Profile
    {
        return $user->profile()->create([
            'profile_code' => 'QSM'.str_pad((string) $user->id, 9, '0', STR_PAD_LEFT),
            'display_name' => 'Example Member',
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'country' => 'CA',
            'city' => 'Toronto',
            'about_me' => 'A complete member biography.',
            'visibility' => 'members',
        ]);
    }

    public function test_profile_stays_hidden_until_approved_and_member_opts_in(): void
    {
        $member = User::factory()->create();
        $profile = $this->completeProfile($member);
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/profile/onboarding-status')
            ->assertOk()->assertJsonPath('data.discoverable', false);

        $this->putJson('/api/v1/profile/discovery', ['enabled' => true])->assertForbidden();

        $this->postJson('/api/v1/profile/submit')
            ->assertOk()->assertJsonPath('data.moderation_status', 'pending')
            ->assertJsonPath('data.discovery_opt_in', false);

        $this->postJson('/api/v1/profile/submit')->assertOk();
        $this->assertDatabaseHas('profiles', ['id' => $profile->id, 'moderation_status' => 'pending']);

        // Simulate a separate, authorized moderation decision.
        $profile->forceFill(['moderation_status' => 'approved', 'approved_at' => now()])->save();

        $this->getJson('/api/v1/profile/onboarding-status')
            ->assertJsonPath('data.discoverable', false);
        $this->putJson('/api/v1/profile/discovery', ['enabled' => true])
            ->assertOk()->assertJsonPath('data.discoverable', true);
        $this->putJson('/api/v1/profile/discovery', ['enabled' => false])
            ->assertOk()->assertJsonPath('data.discoverable', false);
    }

    public function test_incomplete_profile_cannot_be_submitted_without_a_photo_requirement(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);
        $member->profile()->create(['city' => 'Toronto']);

        $this->postJson('/api/v1/profile/submit')->assertUnprocessable();

        $this->putJson('/api/v1/profile', [
            'display_name' => 'Example Member',
            'date_of_birth' => now()->subYears(21)->toDateString(),
            'country' => 'CA',
            'city' => 'Toronto',
            'about_me' => 'I am looking for a meaningful connection.',
        ])->assertOk();

        $this->postJson('/api/v1/profile/submit')
            ->assertOk()->assertJsonPath('data.moderation_status', 'pending');
    }

    public function test_editing_approved_profile_returns_it_to_hidden_draft(): void
    {
        $member = User::factory()->create();
        $profile = $this->completeProfile($member);
        $profile->forceFill(['moderation_status' => 'approved', 'discovery_opt_in' => true])->save();
        Sanctum::actingAs($member);

        $this->putJson('/api/v1/profile', ['about_me' => 'An updated biography.'])
            ->assertOk()->assertJsonPath('data.moderation_status', 'draft')
            ->assertJsonPath('data.discovery_opt_in', false);
    }

    public function test_other_profiles_are_not_discoverable_without_approval_and_opt_in(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $profile = $this->completeProfile($other);
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/matches')->assertOk()->assertJsonCount(0, 'data.data');

        $profile->forceFill(['moderation_status' => 'approved', 'discovery_opt_in' => true])->save();
        $this->getJson('/api/v1/matches')->assertOk()->assertJsonCount(1, 'data.data');
    }
}
