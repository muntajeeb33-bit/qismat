<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\User;

class DiscoveryProfilePresenter
{
    public function payload(Profile $profile, User $viewer, bool $detail = false): array
    {
        $photo = $profile->photos->first(fn (ProfilePhoto $item) => $item->is_primary && $item->moderation_status === 'approved' && $item->visibility === 'members');
        $favourite = $profile->relationLoaded('favouritedBy')
            ? $profile->favouritedBy->isNotEmpty()
            : false;

        $payload = [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'profile_code' => $profile->profile_code,
            'display_name' => $profile->display_name ?: $profile->user?->name,
            'age' => $profile->date_of_birth?->age,
            'gender' => $profile->gender,
            'height_cm' => $profile->height_cm,
            'marital_status' => $profile->marital_status,
            'religion' => $profile->religion,
            'community' => $profile->community,
            'mother_tongue' => $profile->mother_tongue,
            'country' => $profile->country,
            'state' => $profile->state,
            'city' => $profile->city,
            'education' => $profile->education,
            'occupation' => $profile->occupation,
            'verification_status' => $profile->verification_status,
            'last_active_at' => $profile->last_active_at,
            'is_favourite' => $favourite,
            'match_reasons' => $this->matchReasons($profile, $viewer),
            'primary_photo' => $photo ? [
                'id' => $photo->id,
                'content_url' => url('/api/v1/profile/photos/'.$photo->id.'/content'),
                'width' => $photo->width,
                'height' => $photo->height,
            ] : null,
        ];

        if ($detail) {
            $payload += [
                'about_me' => $profile->about_me,
                'photos' => $profile->photos->map(fn (ProfilePhoto $item) => [
                    'id' => $item->id,
                    'content_url' => url('/api/v1/profile/photos/'.$item->id.'/content'),
                    'width' => $item->width,
                    'height' => $item->height,
                    'is_primary' => (bool) $item->is_primary,
                ])->values(),
            ];
        }

        return $payload;
    }

    private function matchReasons(Profile $profile, User $viewer): array
    {
        $preference = $viewer->partnerPreference;
        if (! $preference) {
            return [];
        }

        $checks = [
            'Within your preferred age range' => $profile->date_of_birth && $preference->min_age !== null && $preference->max_age !== null && $profile->date_of_birth->age >= $preference->min_age && $profile->date_of_birth->age <= $preference->max_age,
            'Matches your religion preference' => in_array($profile->religion, $preference->religions ?? [], true),
            'Matches your community preference' => in_array($profile->community, $preference->communities ?? [], true),
            'Matches your language preference' => in_array($profile->mother_tongue, $preference->mother_tongues ?? [], true),
            'Matches your location preference' => in_array($profile->city, $preference->cities ?? [], true) || in_array($profile->country, $preference->countries ?? [], true),
        ];

        return array_keys(array_filter($checks));
    }
}
