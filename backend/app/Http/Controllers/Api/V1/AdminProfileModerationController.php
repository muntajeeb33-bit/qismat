<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminProfileModerationController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:draft,pending,approved,rejected,suspended'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);

        $profiles = Profile::query()
            ->with(['user:id,name,email,status'])
            ->where('moderation_status', $data['status'] ?? 'pending')
            ->orderByRaw('submitted_at IS NULL')
            ->orderBy('submitted_at')
            ->paginate($data['per_page'] ?? 20)
            ->through(fn (Profile $profile) => $profile->makeVisible(['moderation_feedback', 'moderated_by', 'moderated_at']));

        return $this->success($profiles);
    }

    public function review(Request $request, Profile $profile)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:decision,rejected'],
        ]);

        return DB::transaction(function () use ($request, $profile, $data) {
            $profile = Profile::query()->lockForUpdate()->findOrFail($profile->id);
            abort_unless($profile->moderation_status === 'pending', 409, 'Only pending profiles can be reviewed.');

            $before = $profile->only(['moderation_status', 'moderation_feedback', 'discovery_opt_in', 'approved_at', 'moderated_by', 'moderated_at']);
            $approved = $data['decision'] === 'approved';
            $profile->forceFill([
                'moderation_status' => $data['decision'],
                'moderation_feedback' => $approved ? null : $data['reason'],
                'moderated_by' => $request->user()->id,
                'moderated_at' => now(),
                'discovery_opt_in' => false,
                'approved_at' => $approved ? now() : null,
            ])->save();

            AdminAuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => 'profile.'.$data['decision'],
                'target_type' => 'profile',
                'target_id' => $profile->id,
                'old_values' => $before,
                'new_values' => $profile->only(['moderation_status', 'moderation_feedback', 'discovery_opt_in', 'approved_at', 'moderated_by', 'moderated_at']),
                'ip_address' => $request->ip(),
            ]);

            return $this->success(
                $profile->load('user:id,name,email,status')->makeVisible(['moderation_feedback', 'moderated_by', 'moderated_at']),
                $approved ? 'Profile approved.' : 'Profile rejected.'
            );
        });
    }
}
