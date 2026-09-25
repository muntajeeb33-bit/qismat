<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PartnerPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_update_and_read_partner_preferences(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/profile/partner-preferences')
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->putJson('/api/v1/profile/partner-preferences', [
            'min_age' => 25,
            'max_age' => 35,
            'min_height_cm' => 150,
            'max_height_cm' => 190,
            'marital_statuses' => ['never_married', 'divorced'],
            'religions' => ['Islam'],
            'countries' => ['United Kingdom', 'Pakistan'],
            'open_to_relocation' => true,
            'summary' => 'Seeking a kind, family-oriented partner.',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.min_age', 25)
            ->assertJsonPath('data.marital_statuses.1', 'divorced')
            ->assertJsonPath('data.open_to_relocation', true);

        $this->putJson('/api/v1/profile/partner-preferences', [
            'max_age' => 38,
        ])->assertOk()->assertJsonPath('data.max_age', 38);

        $this->getJson('/api/v1/profile/partner-preferences')
            ->assertOk()
            ->assertJsonPath('data.min_age', 25)
            ->assertJsonPath('data.max_age', 38)
            ->assertJsonPath('data.countries.0', 'United Kingdom');

        $this->assertDatabaseCount('partner_preferences', 1);
        $this->assertDatabaseHas('partner_preferences', [
            'user_id' => $user->id,
            'min_age' => 25,
            'max_age' => 38,
        ]);
    }

    public function test_partner_preferences_reject_invalid_ranges_and_duplicate_values(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/profile/partner-preferences', [
            'min_age' => 40,
            'max_age' => 30,
            'min_height_cm' => 190,
            'max_height_cm' => 170,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['max_age']);

        $this->putJson('/api/v1/profile/partner-preferences', [
            'min_height_cm' => 190,
            'max_height_cm' => 170,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['max_height_cm']);

        $this->putJson('/api/v1/profile/partner-preferences', [
            'countries' => ['Pakistan', 'Pakistan'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['countries.0', 'countries.1']);
    }

    public function test_partial_updates_cannot_break_an_existing_range(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/profile/partner-preferences', [
            'min_age' => 25,
            'max_age' => 35,
            'min_height_cm' => 150,
            'max_height_cm' => 190,
        ])->assertOk();

        $this->putJson('/api/v1/profile/partner-preferences', [
            'min_age' => 36,
        ])->assertUnprocessable()->assertJsonValidationErrors('max_age');

        $this->putJson('/api/v1/profile/partner-preferences', [
            'max_height_cm' => 140,
        ])->assertUnprocessable()->assertJsonValidationErrors('max_height_cm');
    }

    public function test_preferences_are_scoped_to_the_authenticated_member(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        Sanctum::actingAs($firstUser);
        $this->putJson('/api/v1/profile/partner-preferences', [
            'religions' => ['Islam'],
        ])->assertOk();

        Sanctum::actingAs($secondUser);
        $this->getJson('/api/v1/profile/partner-preferences')
            ->assertOk()
            ->assertJsonPath('data', null);
    }
}
