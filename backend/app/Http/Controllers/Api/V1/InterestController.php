<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Interest;
use App\Services\ActivityTracker;
use App\Services\DiscoverableProfiles;
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

    public function store(Request $r, ActivityTracker $tracker, DiscoverableProfiles $discoverable)
    {
        $data = $r->validate(['receiver_id' => ['required', 'integer', 'exists:users,id', 'not_in:'.$r->user()->id], 'message' => ['nullable', 'string', 'max:500']]);
        $receiverIsDiscoverable = $discoverable->query($r->user())->where('user_id', $data['receiver_id'])->exists();

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
