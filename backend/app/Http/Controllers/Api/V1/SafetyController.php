<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Favourite;
use App\Models\Interest;
use App\Models\Report;
use App\Models\User;
use App\Services\ActivityTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SafetyController extends Controller
{
    use RespondsWithJson;

    public function blocks(Request $request)
    {
        $blocks = Block::query()
            ->where('blocker_id', $request->user()->id)
            ->with('blockedUser.profile:id,user_id,profile_code,display_name')
            ->latest()
            ->paginate(30)
            ->through(fn (Block $block) => [
                'id' => $block->id,
                'user_id' => $block->blocked_user_id,
                'profile_code' => $block->blockedUser?->profile?->profile_code,
                'display_name' => $block->blockedUser?->profile?->display_name ?: $block->blockedUser?->name,
                'created_at' => $block->created_at,
            ]);

        return $this->success($blocks);
    }

    public function block(Request $request, ActivityTracker $tracker)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'member'), 'not_in:'.$request->user()->id],
            'reason' => ['nullable', 'string', 'max:120'],
        ]);

        return DB::transaction(function () use ($request, $tracker, $data) {
            $block = Block::firstOrCreate(
                ['blocker_id' => $request->user()->id, 'blocked_user_id' => $data['user_id']],
                ['reason' => $data['reason'] ?? null]
            );

            Interest::query()->where(function ($query) use ($request, $data) {
                $query->where(fn ($pair) => $pair->where('sender_id', $request->user()->id)->where('receiver_id', $data['user_id']))
                    ->orWhere(fn ($pair) => $pair->where('sender_id', $data['user_id'])->where('receiver_id', $request->user()->id));
            })->whereIn('status', ['pending', 'accepted'])->update(['status' => 'blocked', 'responded_at' => now()]);

            Favourite::query()->where(function ($query) use ($request, $data) {
                $query->where('user_id', $request->user()->id)->where('favourite_user_id', $data['user_id']);
            })->orWhere(function ($query) use ($request, $data) {
                $query->where('user_id', $data['user_id'])->where('favourite_user_id', $request->user()->id);
            })->delete();

            if ($block->wasRecentlyCreated) {
                $tracker->record($request, 'member.blocked', 'user', $data['user_id']);
            }

            return $this->success($block, $block->wasRecentlyCreated ? 'Member blocked.' : 'Member is already blocked.', $block->wasRecentlyCreated ? 201 : 200);
        });
    }

    public function unblock(Request $request, User $user, ActivityTracker $tracker)
    {
        $deleted = Block::query()
            ->where('blocker_id', $request->user()->id)
            ->where('blocked_user_id', $user->id)
            ->delete();

        abort_unless($deleted, 404, 'Block not found.');
        $tracker->record($request, 'member.unblocked', 'user', $user->id);

        return $this->success(null, 'Member unblocked.');
    }

    public function report(Request $request, ActivityTracker $tracker)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'member'), 'not_in:'.$request->user()->id],
            'reason' => ['required', Rule::in(['fake_identity', 'commercial_use', 'scam', 'harassment', 'inappropriate_content', 'underage_concern', 'other'])],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = Report::query()
            ->where('reporter_id', $request->user()->id)
            ->where('reported_user_id', $data['user_id'])
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return $this->success($existing, 'Your existing report is still under review.');
        }

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reported_user_id' => $data['user_id'],
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
        ]);
        $tracker->record($request, 'member.reported', 'report', $report->id, ['reported_user_id' => $data['user_id'], 'reason' => $data['reason']]);

        return $this->success($report, 'Report submitted confidentially.', 201);
    }
}
