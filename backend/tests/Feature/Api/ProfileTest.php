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
}
