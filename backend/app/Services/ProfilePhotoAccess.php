<?php

namespace App\Services;

use App\Models\Interest;
use App\Models\ProfilePhoto;
use App\Models\User;

class ProfilePhotoAccess
{
    public function canView(User $viewer, ProfilePhoto $photo): bool
    {
        if ($viewer->id === $photo->user_id || ($viewer->role === 'admin' && $viewer->status === 'active')) {
            return true;
        }

        if ($viewer->status !== 'active' || ! $viewer->hasVerifiedEmail() || $photo->moderation_status !== 'approved') {
            return false;
        }

        $owner = $photo->user;
        $profile = $owner?->profile;
        if (! $owner || $owner->status !== 'active' || ! $owner->hasVerifiedEmail() || $profile?->moderation_status !== 'approved') {
            return false;
        }

        if ($photo->visibility === 'members') {
            return $profile->visibility === 'members' && (bool) $profile->discovery_opt_in;
        }

        if ($photo->visibility === 'matches') {
            return Interest::query()
                ->where('status', 'accepted')
                ->where(function ($query) use ($viewer, $owner) {
                    $query->where(fn ($pair) => $pair->where('sender_id', $viewer->id)->where('receiver_id', $owner->id))
                        ->orWhere(fn ($pair) => $pair->where('sender_id', $owner->id)->where('receiver_id', $viewer->id));
                })
                ->exists();
        }

        return false;
    }
}
