<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PartnerPreferenceController extends Controller
{
    use RespondsWithJson;

    public function show(Request $request)
    {
        return $this->success($request->user()->partnerPreference()->first());
    }

    public function update(Request $request)
    {
        $existingPreferences = $request->user()->partnerPreference()->first();

        $data = $request->validate([
            'min_age' => ['sometimes', 'nullable', 'integer', 'between:18,100'],
            'max_age' => ['sometimes', 'nullable', 'integer', 'between:18,100'],
            'min_height_cm' => ['sometimes', 'nullable', 'integer', 'between:100,250'],
            'max_height_cm' => ['sometimes', 'nullable', 'integer', 'between:100,250'],
            'marital_statuses' => ['sometimes', 'nullable', 'array', 'max:8'],
            'marital_statuses.*' => ['string', 'distinct', 'max:40'],
            'religions' => ['sometimes', 'nullable', 'array', 'max:20'],
            'religions.*' => ['string', 'distinct', 'max:80'],
            'denominations' => ['sometimes', 'nullable', 'array', 'max:20'],
            'denominations.*' => ['string', 'distinct', 'max:100'],
            'communities' => ['sometimes', 'nullable', 'array', 'max:20'],
            'communities.*' => ['string', 'distinct', 'max:100'],
            'sub_communities' => ['sometimes', 'nullable', 'array', 'max:20'],
            'sub_communities.*' => ['string', 'distinct', 'max:120'],
            'ethnicities' => ['sometimes', 'nullable', 'array', 'max:20'],
            'ethnicities.*' => ['string', 'distinct', 'max:120'],
            'mother_tongues' => ['sometimes', 'nullable', 'array', 'max:20'],
            'mother_tongues.*' => ['string', 'distinct', 'max:80'],
            'countries' => ['sometimes', 'nullable', 'array', 'max:20'],
            'countries.*' => ['string', 'distinct', 'max:80'],
            'cities' => ['sometimes', 'nullable', 'array', 'max:30'],
            'cities.*' => ['string', 'distinct', 'max:80'],
            'education_preferences' => ['sometimes', 'nullable', 'array', 'max:20'],
            'education_preferences.*' => ['string', 'distinct', 'max:180'],
            'occupation_preferences' => ['sometimes', 'nullable', 'array', 'max:20'],
            'occupation_preferences.*' => ['string', 'distinct', 'max:180'],
            'open_to_relocation' => ['sometimes', 'nullable', 'boolean'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $minAge = array_key_exists('min_age', $data) ? $data['min_age'] : $existingPreferences?->min_age;
        $maxAge = array_key_exists('max_age', $data) ? $data['max_age'] : $existingPreferences?->max_age;
        $minHeight = array_key_exists('min_height_cm', $data) ? $data['min_height_cm'] : $existingPreferences?->min_height_cm;
        $maxHeight = array_key_exists('max_height_cm', $data) ? $data['max_height_cm'] : $existingPreferences?->max_height_cm;

        if ($minAge !== null && $maxAge !== null && $maxAge < $minAge) {
            throw ValidationException::withMessages([
                'max_age' => ['The maximum age must be greater than or equal to the minimum age.'],
            ]);
        }

        if ($minHeight !== null && $maxHeight !== null && $maxHeight < $minHeight) {
            throw ValidationException::withMessages([
                'max_height_cm' => ['The maximum height must be greater than or equal to the minimum height.'],
            ]);
        }

        $preferences = $request->user()->partnerPreference()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return $this->success($preferences->refresh(), 'Partner preferences updated successfully.');
    }
}
