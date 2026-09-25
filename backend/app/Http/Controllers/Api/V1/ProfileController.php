<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\ProfileReadiness;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use RespondsWithJson;

    public function __construct(private readonly ProfileReadiness $readiness) {}

    public function show(Request $request)
    {
        return $this->success($request->user()->profile()->first());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'display_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'gender' => ['sometimes', 'nullable', 'in:male,female,other'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'height_cm' => ['sometimes', 'nullable', 'integer', 'between:100,250'],
            'marital_status' => ['sometimes', 'nullable', 'string', 'max:40'],
            'religion' => ['sometimes', 'nullable', 'string', 'max:80'],
            'community' => ['sometimes', 'nullable', 'string', 'max:100'],
            'mother_tongue' => ['sometimes', 'nullable', 'string', 'max:80'],
            'country' => ['sometimes', 'nullable', 'string', 'max:80'],
            'state' => ['sometimes', 'nullable', 'string', 'max:80'],
            'city' => ['sometimes', 'nullable', 'string', 'max:80'],
            'education' => ['sometimes', 'nullable', 'string', 'max:180'],
            'occupation' => ['sometimes', 'nullable', 'string', 'max:180'],
            'about_me' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'partner_expectations' => ['sometimes', 'array'],
        ]);

        $existingProfile = $request->user()->profile()->first();
        $profile = $request->user()->profile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $data + ['profile_code' => $existingProfile?->profile_code ?? $this->newProfileCode()]
        );

        $reviewedFieldsChanged = $profile->wasChanged(['display_name', 'gender', 'date_of_birth', 'marital_status', 'religion', 'community', 'mother_tongue', 'country', 'state', 'city', 'education', 'occupation', 'about_me', 'partner_expectations']);
        $profile->forceFill(['profile_completion' => $this->readiness->percentage($profile)])->save();

        // Any change to reviewed public information requires a fresh moderation decision.
        if ($reviewedFieldsChanged && in_array($profile->moderation_status, ['approved', 'pending', 'rejected'], true)) {
            $profile->forceFill([
                'moderation_status' => 'draft',
                'moderation_feedback' => null,
                'moderated_by' => null,
                'moderated_at' => null,
                'discovery_opt_in' => false,
                'submitted_at' => null,
                'approved_at' => null,
            ])->save();
        }

        return $this->success($profile->refresh(), 'Profile updated successfully.');
    }

    private function newProfileCode(): string
    {
        do {
            $code = 'QSM'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (Profile::where('profile_code', $code)->exists());

        return $code;
    }
}
