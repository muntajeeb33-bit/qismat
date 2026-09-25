<?php

namespace Tests\Feature\Api;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovery_filters_profiles_and_returns_only_safe_fields(): void
    {
        $viewer = User::factory()->create();
        $match = $this->discoverable(['city' => 'Toronto', 'religion' => 'Muslim', 'occupation' => 'Engineer']);
        $this->discoverable(['city' => 'Ottawa', 'religion' => 'Muslim']);
        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/matches?city=Toronto&religion=Muslim&occupation=Eng')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $match->id)
            ->assertJsonPath('data.data.0.city', 'Toronto')
            ->assertJsonPath('data.data.0.primary_photo.id', $match->photos()->first()->id)
            ->assertJsonMissingPath('data.data.0.date_of_birth')
            ->assertJsonMissingPath('data.data.0.primary_photo.path')
            ->assertJsonMissingPath('data.data.0.moderation_feedback');
    }

    public function test_ineligible_profiles_are_excluded_from_discovery_and_details(): void
    {
        $viewer = User::factory()->create();
        $hidden = $this->discoverable(['visibility' => 'private']);
        $pending = $this->discoverable(['moderation_status' => 'pending']);
        $noPhoto = $this->discoverable();
        $noPhoto->photos()->delete();
        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/matches')->assertOk()->assertJsonCount(0, 'data.data');
        $this->getJson('/api/v1/matches/'.$hidden->id)->assertNotFound();
        $this->getJson('/api/v1/matches/'.$pending->id)->assertNotFound();
    }

    public function test_profile_detail_records_only_one_view_per_day(): void
    {
        $viewer = User::factory()->create();
        $match = $this->discoverable();
        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/matches/'.$match->id)->assertOk()->assertJsonPath('data.about_me', 'A thoughtful introduction for a genuine marriage search.');
        $this->getJson('/api/v1/matches/'.$match->id)->assertOk();

        $this->assertDatabaseCount('profile_views', 1);
        $this->assertDatabaseHas('profile_views', ['viewer_id' => $viewer->id, 'viewed_user_id' => $match->user_id]);
    }

    public function test_member_can_save_list_and_remove_a_discoverable_profile(): void
    {
        $viewer = User::factory()->create();
        $match = $this->discoverable();
        Sanctum::actingAs($viewer);

        $this->postJson('/api/v1/favourites', ['profile_id' => $match->id])->assertCreated();
        $this->postJson('/api/v1/favourites', ['profile_id' => $match->id])->assertOk();
        $this->assertDatabaseCount('favourites', 1);
        $this->getJson('/api/v1/favourites')->assertOk()->assertJsonPath('data.data.0.is_favourite', true);
        $this->deleteJson('/api/v1/favourites/'.$match->id)->assertOk();
        $this->assertDatabaseCount('favourites', 0);
    }

    public function test_match_reasons_use_saved_partner_preferences(): void
    {
        $viewer = User::factory()->create();
        $viewer->partnerPreference()->create([
            'min_age' => 22,
            'max_age' => 32,
            'religions' => ['Muslim'],
            'cities' => ['Toronto'],
        ]);
        $this->discoverable(['city' => 'Toronto', 'religion' => 'Muslim']);
        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/matches')
            ->assertOk()
            ->assertJsonPath('data.data.0.match_reasons.0', 'Within your preferred age range')
            ->assertJsonPath('data.data.0.match_reasons.1', 'Matches your religion preference')
            ->assertJsonPath('data.data.0.match_reasons.2', 'Matches your location preference');
    }

    public function test_admin_can_inspect_discovery_eligibility(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $eligible = $this->discoverable();
        $this->discoverable(['discovery_opt_in' => false]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/discovery?q='.$eligible->profile_code)
            ->assertOk()
            ->assertJsonPath('data.summary.approved_and_opted_in', 1)
            ->assertJsonPath('data.summary.visible_with_approved_photo', 1)
            ->assertJsonPath('data.profiles.data.0.id', $eligible->id)
            ->assertJsonPath('data.profiles.data.0.eligible', true);
    }

    private function discoverable(array $overrides = []): Profile
    {
        $user = User::factory()->create();
        $profile = $user->profile()->create(array_merge([
            'profile_code' => 'QSM'.str_pad((string) $user->id, 9, '0', STR_PAD_LEFT),
            'display_name' => 'Available Member',
            'gender' => 'female',
            'date_of_birth' => now()->subYears(27)->toDateString(),
            'height_cm' => 165,
            'marital_status' => 'never_married',
            'religion' => 'Muslim',
            'community' => 'Sunni',
            'mother_tongue' => 'Urdu',
            'country' => 'Canada',
            'state' => 'Ontario',
            'city' => 'Toronto',
            'education' => 'Bachelor degree',
            'occupation' => 'Teacher',
            'about_me' => 'A thoughtful introduction for a genuine marriage search.',
            'visibility' => 'members',
            'moderation_status' => 'approved',
            'discovery_opt_in' => true,
        ], $overrides));
        $user->profilePhotos()->create([
            'disk' => 'profile_photos',
            'path' => 'users/'.$user->id.'/primary.jpg',
            'is_primary' => true,
            'visibility' => 'members',
            'moderation_status' => 'approved',
            'width' => 800,
            'height' => 900,
        ]);

        return $profile;
    }
}
