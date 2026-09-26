<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminMemberController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', Rule::in(['active', 'suspended', 'deleted'])],
            'verification_status' => ['sometimes', Rule::in(['unverified', 'reviewed', 'identity_verified'])],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);

        $query = User::query()
            ->where('role', 'member')
            ->with(['profile:id,user_id,profile_code,display_name,moderation_status,verification_status,discovery_opt_in,city,country'])
            ->withCount(['reportsReceived']);

        if ($term = trim((string) ($data['q'] ?? ''))) {
            $query->where(function ($member) use ($term) {
                $member->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhereHas('profile', fn ($profile) => $profile
                        ->where('profile_code', 'like', "%{$term}%")
                        ->orWhere('display_name', 'like', "%{$term}%"));
            });
        }

        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (isset($data['verification_status'])) {
            $query->whereHas('profile', fn ($profile) => $profile->where('verification_status', $data['verification_status']));
        }

        return $this->success($query->latest()->paginate($data['per_page'] ?? 20));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role === 'member', 404, 'Member not found.');
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['active', 'suspended'])],
            'verification_status' => ['sometimes', Rule::in(['unverified', 'reviewed', 'identity_verified'])],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        abort_unless(isset($data['status']) || isset($data['verification_status']), 422, 'Choose an account or verification change.');
        abort_if($user->status === 'deleted', 409, 'Deleted accounts cannot be changed.');

        return DB::transaction(function () use ($request, $user, $data) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $profile = $locked->profile;
            $before = [
                'status' => $locked->status,
                'verification_status' => $profile?->verification_status,
                'moderation_status' => $profile?->moderation_status,
            ];

            if (isset($data['status'])) {
                $locked->update(['status' => $data['status']]);
                if ($data['status'] === 'suspended') {
                    $locked->tokens()->delete();
                    $profile?->update(['moderation_status' => 'suspended', 'discovery_opt_in' => false]);
                } elseif ($profile?->moderation_status === 'suspended') {
                    $profile->update(['moderation_status' => 'draft', 'discovery_opt_in' => false]);
                }
            }

            if (isset($data['verification_status'])) {
                abort_unless($profile, 409, 'The member must create a profile before verification can be changed.');
                $profile->update(['verification_status' => $data['verification_status']]);
            }

            $locked->refresh();
            $profile?->refresh();
            AdminAuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => isset($data['status']) ? 'member.status_changed' : 'member.verification_changed',
                'target_type' => 'user',
                'target_id' => $locked->id,
                'old_values' => $before,
                'new_values' => [
                    'status' => $locked->status,
                    'verification_status' => $profile?->verification_status,
                    'moderation_status' => $profile?->moderation_status,
                    'reason' => $data['reason'],
                ],
                'ip_address' => $request->ip(),
            ]);

            return $this->success($locked->load('profile'), 'Member account updated.');
        });
    }
}
