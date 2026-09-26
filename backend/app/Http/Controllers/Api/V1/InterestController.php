<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Interest;
use App\Services\ActivityTracker;
use App\Services\DiscoverableProfiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InterestController extends Controller
{
    use RespondsWithJson;

    public function index(Request $r)
    {
        $data = $r->validate(['direction' => ['sometimes', 'in:sent,received'], 'status' => ['sometimes', 'in:pending,accepted,declined,cancelled,blocked']]);
        $query = Interest::query()->with([
            'sender.profile:id,user_id,profile_code,display_name,city,country,occupation',
            'receiver.profile:id,user_id,profile_code,display_name,city,country,occupation',
        ]);

        if (($data['direction'] ?? null) === 'sent') {
            $query->where('sender_id', $r->user()->id);
        } elseif (($data['direction'] ?? null) === 'received') {
            $query->where('receiver_id', $r->user()->id);
        } else {
            $query->where(fn ($items) => $items->where('sender_id', $r->user()->id)->orWhere('receiver_id', $r->user()->id));
        }

        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        $interests = $query
            ->latest()
            ->paginate(30)
            ->through(function (Interest $interest) use ($r) {
                $sent = $interest->sender_id === $r->user()->id;
                $member = $sent ? $interest->receiver : $interest->sender;

                return [
                    'id' => $interest->id,
                    'direction' => $sent ? 'sent' : 'received',
                    'status' => $interest->status,
                    'message' => $interest->message,
                    'responded_at' => $interest->responded_at,
                    'created_at' => $interest->created_at,
                    'member' => [
                        'user_id' => $member?->id,
                        'profile_code' => $member?->profile?->profile_code,
                        'display_name' => $member?->profile?->display_name ?: $member?->name,
                        'city' => $member?->profile?->city,
                        'country' => $member?->profile?->country,
                        'occupation' => $member?->profile?->occupation,
                    ],
                ];
            });

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

        $blocked = Block::query()->where(function ($query) use ($interest) {
            $query->where(fn ($pair) => $pair->where('blocker_id', $interest->sender_id)->where('blocked_user_id', $interest->receiver_id))
                ->orWhere(fn ($pair) => $pair->where('blocker_id', $interest->receiver_id)->where('blocked_user_id', $interest->sender_id));
        })->exists();
        abort_if($blocked, 409, 'This interest is no longer available.');

        $interest = DB::transaction(function () use ($interest, $data) {
            $locked = Interest::query()->lockForUpdate()->findOrFail($interest->id);
            abort_unless($locked->status === 'pending', 409, 'This interest has already been answered.');
            $locked->update(['status' => $data['status'], 'responded_at' => now()]);

            return $locked;
        });
        $tracker->record($r, 'interest.'.$data['status'], 'interest', $interest->id);

        return $this->success($interest, 'Interest '.$data['status'].'.');
    }

    public function destroy(Request $request, Interest $interest, ActivityTracker $tracker)
    {
        abort_unless($interest->sender_id === $request->user()->id, 403);
        abort_unless($interest->status === 'pending', 409, 'Only pending interests can be cancelled.');

        $interest->update(['status' => 'cancelled', 'responded_at' => now()]);
        $tracker->record($request, 'interest.cancelled', 'interest', $interest->id);

        return $this->success($interest, 'Interest cancelled.');
    }
}
