<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DiscoverableProfiles
{
    public function query(User $viewer): Builder
    {
        return Profile::query()
            ->where('user_id', '!=', $viewer->id)
            ->where('visibility', 'members')
            ->where('moderation_status', 'approved')
            ->where('discovery_opt_in', true)
            ->whereNotNull('date_of_birth')
            ->whereDate('date_of_birth', '<=', now()->subYears(18)->toDateString())
            ->whereHas('photos', fn ($photos) => $photos
                ->where('is_primary', true)
                ->where('moderation_status', 'approved')
                ->where('visibility', 'members'))
            ->whereHas('user', fn ($user) => $user
                ->where('status', 'active')
                ->whereNotNull('email_verified_at'));
    }

    public function find(User $viewer, int $profileId): Profile
    {
        return $this->query($viewer)->whereKey($profileId)->firstOrFail();
    }
}
