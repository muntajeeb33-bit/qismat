<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;

class AdminDiscoveryController extends Controller
{
    use RespondsWithJson;

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'q' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);

        $query = Profile::query()
            ->with(['user:id,name,email,status,email_verified_at', 'photos' => fn ($photos) => $photos->where('is_primary', true)]);
        if ($term = $data['q'] ?? null) {
            $query->where(function ($builder) use ($term) {
                $builder->where('profile_code', 'like', '%'.$term.'%')
                    ->orWhere('display_name', 'like', '%'.$term.'%')
                    ->orWhereHas('user', fn ($user) => $user->where('email', 'like', '%'.$term.'%'));
            });
        }

        $profiles = $query->latest('updated_at')->paginate($data['per_page'] ?? 30)
            ->through(function (Profile $profile) {
                $issues = $this->issues($profile);

                return [
                    'id' => $profile->id,
                    'profile_code' => $profile->profile_code,
                    'display_name' => $profile->display_name ?: $profile->user?->name,
                    'email' => $profile->user?->email,
                    'moderation_status' => $profile->moderation_status,
                    'discovery_opt_in' => (bool) $profile->discovery_opt_in,
                    'eligible' => $issues === [],
                    'issues' => $issues,
                    'updated_at' => $profile->updated_at,
                ];
            });

        $summary = [
            'approved_and_opted_in' => Profile::query()->where('moderation_status', 'approved')->where('discovery_opt_in', true)->count(),
            'visible_with_approved_photo' => Profile::query()->where('visibility', 'members')->where('moderation_status', 'approved')->where('discovery_opt_in', true)
                ->whereHas('photos', fn ($photos) => $photos->where('is_primary', true)->where('moderation_status', 'approved')->where('visibility', 'members'))->count(),
            'missing_approved_primary_photo' => Profile::query()->where('moderation_status', 'approved')->where('discovery_opt_in', true)
                ->whereDoesntHave('photos', fn ($photos) => $photos->where('is_primary', true)->where('moderation_status', 'approved')->where('visibility', 'members'))->count(),
        ];

        return $this->success(['summary' => $summary, 'profiles' => $profiles]);
    }

    private function issues(Profile $profile): array
    {
        $issues = [];
        if ($profile->moderation_status !== 'approved') {
            $issues[] = 'Profile is not approved';
        }
        if (! $profile->discovery_opt_in) {
            $issues[] = 'Member has not opted in';
        }
        if ($profile->visibility !== 'members') {
            $issues[] = 'Profile visibility is restricted';
        }
        if (! $profile->date_of_birth || $profile->date_of_birth->age < 18 || $profile->date_of_birth->age > 100) {
            $issues[] = 'Date of birth must represent an age from 18 to 100';
        }
        if (! $profile->user || $profile->user->status !== 'active') {
            $issues[] = 'Member account is inactive';
        }
        if (! $profile->user?->email_verified_at) {
            $issues[] = 'Email is not verified';
        }
        $photo = $profile->photos->first();
        if (! $photo || $photo->moderation_status !== 'approved' || $photo->visibility !== 'members') {
            $issues[] = 'Approved member-visible primary photo is missing';
        }

        return $issues;
    }
}
