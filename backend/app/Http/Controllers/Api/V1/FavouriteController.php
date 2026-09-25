<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Favourite;
use App\Models\Profile;
use App\Services\DiscoverableProfiles;
use App\Services\DiscoveryProfilePresenter;
use Illuminate\Http\Request;

class FavouriteController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request, DiscoverableProfiles $discoverable, DiscoveryProfilePresenter $presenter)
    {
        $viewer = $request->user()->loadMissing('partnerPreference');
        $profiles = $discoverable->query($viewer)
            ->whereHas('favouritedBy', fn ($query) => $query->where('user_id', $viewer->id))
            ->with(['user:id,name', 'photos' => fn ($photos) => $photos->where('is_primary', true)->where('moderation_status', 'approved')->where('visibility', 'members')])
            ->with(['favouritedBy' => fn ($query) => $query->where('user_id', $viewer->id)])
            ->orderByDesc('profiles.updated_at')
            ->paginate(24)
            ->through(fn (Profile $profile) => $presenter->payload($profile, $viewer));

        return $this->success($profiles);
    }

    public function store(Request $request, DiscoverableProfiles $discoverable)
    {
        $data = $request->validate(['profile_id' => ['required', 'integer']]);
        $profile = $discoverable->find($request->user(), $data['profile_id']);
        $favourite = Favourite::firstOrCreate(['user_id' => $request->user()->id, 'favourite_user_id' => $profile->user_id]);

        return $this->success($favourite, $favourite->wasRecentlyCreated ? 'Profile saved.' : 'Profile is already saved.', $favourite->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, Profile $profile)
    {
        Favourite::query()->where('user_id', $request->user()->id)->where('favourite_user_id', $profile->user_id)->delete();

        return $this->success(null, 'Profile removed from favourites.');
    }
}
