<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\ProfileReadiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileOnboardingController extends Controller
{
    use RespondsWithJson;

    public function __construct(private readonly ProfileReadiness $readiness) {}

    public function status(Request $request)
    {
        $profile = $request->user()->profile()->first();
        $hasApprovedPrimaryPhoto = $profile ? $this->hasApprovedPrimaryPhoto($profile) : false;
        $missingRequiredFields = $profile ? $this->readiness->missingRequired($profile) : array_values([
            'Display name', 'Adult date of birth', 'Country', 'City', 'Your story',
        ]);

        return $this->success([
            'moderation_status' => $profile?->moderation_status ?? 'draft',
            'discovery_opt_in' => (bool) ($profile?->discovery_opt_in ?? false),
            'discoverable' => $profile ? $this->discoverable($profile, $request) : false,
            'required_fields_complete' => $profile ? $this->complete($profile) : false,
            'has_approved_primary_photo' => $hasApprovedPrimaryPhoto,
            'profile_completion' => $profile?->profile_completion ?? 0,
            'missing_required_fields' => $missingRequiredFields,
            'next_action' => $this->nextAction($profile, $hasApprovedPrimaryPhoto),
            'moderation_feedback' => $profile?->moderation_feedback,
            'submitted_at' => $profile?->submitted_at,
        ]);
    }

    public function submit(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $profile = $request->user()->profile()->lockForUpdate()->firstOrFail();

            abort_unless($this->complete($profile), 422, 'Complete your display name, biography, location and adult eligibility before submitting.');
            abort_if($profile->moderation_status === 'suspended', 403, 'This profile cannot be submitted.');
            if ($profile->moderation_status === 'pending') {
                return $this->success($profile, 'Profile is already pending review.');
            }
            if ($profile->moderation_status === 'approved') {
                return $this->success($profile, 'Profile is already approved.');
            }

            $profile->forceFill([
                'moderation_status' => 'pending',
                'moderation_feedback' => null,
                'moderated_by' => null,
                'moderated_at' => null,
                'discovery_opt_in' => false,
                'submitted_at' => now(),
                'approved_at' => null,
            ])->save();

            return $this->success($profile->refresh(), 'Profile submitted for review.');
        });
    }

    public function discovery(Request $request)
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);

        return DB::transaction(function () use ($request, $data) {
            $profile = $request->user()->profile()->lockForUpdate()->firstOrFail();

            if ($data['enabled']) {
                abort_unless($profile->moderation_status === 'approved' && $this->complete($profile)
                    && $request->user()->status === 'active' && $request->user()->hasVerifiedEmail()
                    && $profile->visibility === 'members' && $this->hasApprovedPrimaryPhoto($profile), 403, 'Profile is not eligible for discovery.');
            }

            $profile->forceFill(['discovery_opt_in' => $data['enabled']])->save();

            return $this->success([
                'discovery_opt_in' => (bool) $profile->discovery_opt_in,
                'discoverable' => $this->discoverable($profile, $request),
            ]);
        });
    }

    private function complete(Profile $profile): bool
    {
        return $this->readiness->complete($profile);
    }

    private function discoverable(Profile $profile, Request $request): bool
    {
        return $profile->moderation_status === 'approved'
            && (bool) $profile->discovery_opt_in
            && $profile->visibility === 'members'
            && $request->user()->status === 'active'
            && $request->user()->hasVerifiedEmail()
            && $this->hasApprovedPrimaryPhoto($profile)
            && $this->complete($profile);
    }

    private function hasApprovedPrimaryPhoto(Profile $profile): bool
    {
        return $profile->photos()
            ->where('is_primary', true)
            ->where('moderation_status', 'approved')
            ->exists();
    }

    private function nextAction(?Profile $profile, bool $hasApprovedPrimaryPhoto): array
    {
        if (! $profile || ! $this->complete($profile)) {
            return ['code' => 'complete_profile', 'title' => 'Complete your essentials', 'description' => 'Add the missing required details so your profile can be reviewed.'];
        }
        if ($profile->moderation_status === 'rejected') {
            return ['code' => 'update_profile', 'title' => 'Address reviewer feedback', 'description' => 'Update your profile using the feedback shown below, then submit it again.'];
        }
        if ($profile->moderation_status === 'draft') {
            return ['code' => 'submit_profile', 'title' => 'Submit your profile', 'description' => 'Your required details are complete and ready for moderation.'];
        }
        if ($profile->moderation_status === 'pending') {
            return ['code' => 'await_review', 'title' => 'Review in progress', 'description' => 'Your profile is waiting for the moderation team.'];
        }
        if (! $hasApprovedPrimaryPhoto) {
            return ['code' => 'upload_photo', 'title' => 'Add an approved primary photo', 'description' => 'Upload a clear primary photo and wait for photo approval before entering discovery.'];
        }
        if ($profile->visibility !== 'members') {
            return ['code' => 'enable_visibility', 'title' => 'Allow member visibility', 'description' => 'Change profile visibility to members before entering discovery.'];
        }
        if (! $profile->discovery_opt_in) {
            return ['code' => 'enter_discovery', 'title' => 'Enter discovery', 'description' => 'Your approved profile is ready. Choose when other eligible members can discover it.'];
        }

        return ['code' => 'discover_profiles', 'title' => 'Discover compatible profiles', 'description' => 'Your profile is live and ready to make meaningful connections.'];
    }
}
