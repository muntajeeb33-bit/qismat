<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use RespondsWithJson;

    public function show(Request $request)
    {
        return $this->success($request->user()->profile()->first());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'gender' => ['sometimes', 'in:male,female,other'],
            'date_of_birth' => ['sometimes', 'date', 'before:-18 years'],
            'height_cm' => ['sometimes', 'integer', 'between:100,250'],
            'marital_status' => ['sometimes', 'string', 'max:40'],
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

        return $this->success($profile, 'Profile updated successfully.');
    }

    private function newProfileCode(): string
    {
        do {
            $code = 'QSM'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (Profile::where('profile_code', $code)->exists());

        return $code;
    }
}
