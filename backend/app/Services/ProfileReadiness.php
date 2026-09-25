<?php

namespace App\Services;

use App\Models\Profile;

class ProfileReadiness
{
    private const COMPLETION_FIELDS = [
        'display_name', 'date_of_birth', 'gender', 'marital_status', 'religion', 'mother_tongue',
        'country', 'city', 'education', 'occupation', 'about_me', 'partner_expectations',
    ];

    public function complete(Profile $profile): bool
    {
        return filled($profile->display_name)
            && filled($profile->about_me)
            && filled($profile->country)
            && filled($profile->city)
            && $profile->date_of_birth !== null
            && $profile->date_of_birth->lte(now()->subYears(18)->startOfDay());
    }

    public function percentage(Profile $profile): int
    {
        $completed = collect(self::COMPLETION_FIELDS)
            ->filter(fn (string $field) => filled($profile->{$field}))
            ->count();

        return (int) round(($completed / count(self::COMPLETION_FIELDS)) * 100);
    }
}
