<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_and_read_their_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', [
            'gender' => 'female',
            'date_of_birth' => '1995-03-12',
            'city' => 'Bengaluru',
            'about_me' => 'Family-oriented software professional.',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.city', 'Bengaluru')
            ->assertJsonPath('data.profile_code', fn ($code) => is_string($code) && str_starts_with($code, 'QSM'));

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.city', 'Bengaluru');
    }

    public function test_profile_requires_an_adult_date_of_birth(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/profile', [
            'date_of_birth' => now()->subYears(10)->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('date_of_birth');
    }

    public function test_profile_age_must_not_exceed_one_hundred(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/profile', [
            'date_of_birth' => now()->subYears(100)->toDateString(),
        ])->assertOk();

        $this->putJson('/api/v1/profile', [
            'date_of_birth' => now()->subYears(100)->subDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('date_of_birth');
    }

    public function test_member_can_save_optional_faith_community_and_ethnicity_details(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', [
            'religion' => 'Muslim',
            'denomination' => 'Sunni',
            'community' => 'Rajput',
            'sub_community' => 'Self-described group',
            'ethnicity' => 'South Asian',
        ])->assertOk()
            ->assertJsonPath('data.denomination', 'Sunni')
            ->assertJsonPath('data.sub_community', 'Self-described group')
            ->assertJsonPath('data.ethnicity', 'South Asian');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'denomination' => 'Sunni',
            'sub_community' => 'Self-described group',
            'ethnicity' => 'South Asian',
        ]);
    }

    public function test_member_can_save_structured_family_and_career_details(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', [
            'company' => 'Qismat Technologies',
            'annual_income' => 85000,
            'family_details' => [
                'family_type' => 'nuclear',
                'family_values' => 'moderate',
                'father_occupation' => 'Teacher',
                'mother_occupation' => 'Doctor',
                'siblings' => 2,
                'family_location' => 'London',
                'summary' => 'A close and supportive family.',
            ],
            'partner_expectations' => [
                'summary' => 'Kind, respectful and committed to marriage.',
            ],
            'visibility' => 'private',
        ])->assertOk()
            ->assertJsonPath('data.company', 'Qismat Technologies')
            ->assertJsonPath('data.annual_income', 85000)
            ->assertJsonPath('data.family_details.family_type', 'nuclear')
            ->assertJsonPath('data.family_details.siblings', 2)
            ->assertJsonPath('data.partner_expectations.summary', 'Kind, respectful and committed to marriage.')
            ->assertJsonPath('data.visibility', 'private');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'company' => 'Qismat Technologies',
            'annual_income' => 85000,
            'visibility' => 'private',
        ]);
    }

    public function test_structured_profile_fields_are_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/profile', [
            'annual_income' => -1,
            'family_details' => [
                'family_type' => 'invalid',
                'family_values' => 'invalid',
                'siblings' => 21,
            ],
            'visibility' => 'public',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors([
                'annual_income',
                'family_details.family_type',
                'family_details.family_values',
                'family_details.siblings',
                'visibility',
            ]);
    }
}
