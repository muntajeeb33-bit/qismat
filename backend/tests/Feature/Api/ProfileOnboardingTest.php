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
        $profile = $user->profile()->create([
            'profile_code' => 'QSM'.str_pad((string) $user->id, 9, '0', STR_PAD_LEFT),
            'display_name' => 'Example Member',
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'country' => 'CA',
            'city' => 'Toronto',
            'about_me' => 'A complete member biography.',
            'visibility' => 'members',
        ]);
        $user->profilePhotos()->create([
            'disk' => 'profile_photos',
            'path' => "users/{$user->id}/approved-primary.jpg",
            'is_primary' => true,
            'visibility' => 'members',
            'moderation_status' => 'approved',
        ]);

        return $profile;
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
            ->assertJsonPath('data.discovery_opt_in', false)
            ->assertJsonPath('data.submitted_at', null);
    }

    public function test_member_sees_rejection_feedback_and_editing_clears_the_decision(): void
    {
        $member = User::factory()->create();
        $profile = $this->completeProfile($member);
        $profile->forceFill([
            'moderation_status' => 'rejected',
            'moderation_feedback' => 'Add more detail about your goals.',
            'moderated_by' => User::factory()->create(['role' => 'admin'])->id,
            'moderated_at' => now(),
        ])->save();
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/profile/onboarding-status')
            ->assertOk()
            ->assertJsonPath('data.moderation_status', 'rejected')
            ->assertJsonPath('data.moderation_feedback', 'Add more detail about your goals.');

        $this->putJson('/api/v1/profile', ['about_me' => 'I have added more detail about my goals.'])
            ->assertOk()
            ->assertJsonPath('data.moderation_status', 'draft')
            ->assertJsonPath('data.moderation_feedback', null)
            ->assertJsonPath('data.moderated_by', null);
    }

    public function test_profile_update_calculates_completion_percentage(): void
    {
        $member = User::factory()->create();
        Sanctum::actingAs($member);

        $this->putJson('/api/v1/profile', [
            'display_name' => 'Example Member',
            'date_of_birth' => now()->subYears(25)->toDateString(),
            'country' => 'Canada',
            'city' => 'Toronto',
            'about_me' => 'A complete member biography.',
        ])->assertOk()->assertJsonPath('data.profile_completion', 42);

        $this->getJson('/api/v1/profile/onboarding-status')
            ->assertOk()
            ->assertJsonPath('data.required_fields_complete', true)
            ->assertJsonPath('data.profile_completion', 42);
    }

    public function test_other_profiles_are_not_discoverable_without_approval_and_opt_in(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        $profile = $this->completeProfile($other);
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/matches')->assertOk()->assertJsonCount(0, 'data.data');

        $profile->forceFill(['moderation_status' => 'approved', 'discovery_opt_in' => true])->save();
        $profile->forceFill(['moderation_feedback' => 'Internal review feedback'])->save();
        $this->getJson('/api/v1/matches')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonMissingPath('data.data.0.moderation_feedback')
            ->assertJsonMissingPath('data.data.0.moderated_by');
    }

    public function test_private_profiles_cannot_enter_or_appear_in_discovery(): void
    {
        $member = User::factory()->create();
        $profile = $this->completeProfile($member);
        $profile->forceFill([
            'moderation_status' => 'approved',
            'discovery_opt_in' => true,
            'visibility' => 'private',
        ])->save();
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/profile/onboarding-status')
            ->assertOk()
            ->assertJsonPath('data.discoverable', false);
        $this->putJson('/api/v1/profile/discovery', ['enabled' => true])
            ->assertForbidden();

        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);
        $this->getJson('/api/v1/matches')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');
    }

    public function test_profile_requires_an_approved_primary_photo_for_discovery(): void
    {
        $member = User::factory()->create();
        $profile = $this->completeProfile($member);
        $profile->forceFill(['moderation_status' => 'approved'])->save();
        $member->profilePhotos()->update(['moderation_status' => 'pending']);
        Sanctum::actingAs($member);

        $this->getJson('/api/v1/profile/onboarding-status')
            ->assertOk()
            ->assertJsonPath('data.has_approved_primary_photo', false)
            ->assertJsonPath('data.discoverable', false);
        $this->putJson('/api/v1/profile/discovery', ['enabled' => true])
            ->assertForbidden();

        $member->profilePhotos()->update(['moderation_status' => 'approved']);
        $this->putJson('/api/v1/profile/discovery', ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.discoverable', true);
    }

    public function test_match_filters_reject_invalid_age_ranges(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/matches?min_age=17')->assertUnprocessable();
        $this->getJson('/api/v1/matches?min_age=40&max_age=30')->assertUnprocessable();
    }
}
