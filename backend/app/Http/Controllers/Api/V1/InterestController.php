<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Interest;
use App\Models\Profile;
use App\Services\ActivityTracker;
use Illuminate\Http\Request;

class InterestController extends Controller
{
    use RespondsWithJson;

    public function index(Request $r)
    {
        $interests = Interest::where('sender_id', $r->user()->id)
            ->orWhere('receiver_id', $r->user()->id)
            ->latest()
            ->paginate(30);

        return $this->success($interests);
    }

    public function store(Request $r, ActivityTracker $tracker)
    {
        $data = $r->validate(['receiver_id' => ['required', 'integer', 'exists:users,id', 'not_in:'.$r->user()->id], 'message' => ['nullable', 'string', 'max:500']]);
        $receiverIsDiscoverable = Profile::query()
            ->where('user_id', $data['receiver_id'])
            ->where('visibility', 'members')
            ->where('moderation_status', 'approved')
            ->where('discovery_opt_in', true)
            ->whereNotNull('date_of_birth')
            ->whereDate('date_of_birth', '<=', now()->subYears(18)->toDateString())
            ->whereHas('photos', fn ($photos) => $photos->where('is_primary', true)->where('moderation_status', 'approved'))
            ->whereHas('user', fn ($query) => $query->where('status', 'active')->whereNotNull('email_verified_at'))
            ->exists();

        abort_unless($receiverIsDiscoverable, 404, 'Profile not found.');

        $interest = Interest::firstOrCreate(
            ['sender_id' => $r->user()->id, 'receiver_id' => $data['receiver_id']],
            ['message' => $data['message'] ?? null, 'status' => 'pending']
        );

        if ($interest->wasRecentlyCreated) {
            $tracker->record($r, 'interest.sent', 'interest', $interest->id, ['receiver_id' => $data['receiver_id']]);
        }

        return $this->success(
            $interest,
            $interest->wasRecentlyCreated ? 'Interest sent.' : 'Interest already exists.',
            $interest->wasRecentlyCreated ? 201 : 200
        );
    }

    public function respond(Request $r, Interest $interest, ActivityTracker $tracker)
    {
        abort_unless($interest->receiver_id === $r->user()->id, 403);
        $data = $r->validate(['status' => ['required', 'in:accepted,declined']]);
        $interest->update(['status' => $data['status'], 'responded_at' => now()]);
        $tracker->record($r, 'interest.'.$data['status'], 'interest', $interest->id);

        return $this->success($interest, 'Interest '.$data['status'].'.');
    }
}
