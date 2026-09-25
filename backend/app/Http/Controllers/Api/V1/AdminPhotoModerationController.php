<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\ProfilePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPhotoModerationController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:pending,approved,rejected'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);

        $photos = ProfilePhoto::query()
            ->with(['user:id,name,email,status', 'user.profile:id,user_id,profile_code,display_name'])
            ->where('moderation_status', $data['status'] ?? 'pending')
            ->orderBy('created_at')
            ->paginate($data['per_page'] ?? 20)
            ->through(fn (ProfilePhoto $photo) => $this->payload($photo));

        return $this->success($photos);
    }

    public function review(Request $request, ProfilePhoto $photo)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:decision,rejected'],
        ]);

        return DB::transaction(function () use ($request, $photo, $data) {
            $photo = ProfilePhoto::query()->lockForUpdate()->findOrFail($photo->id);
            abort_unless($photo->moderation_status === 'pending', 409, 'Only pending photos can be reviewed.');

            $before = $photo->only(['moderation_status', 'moderation_feedback', 'moderated_by', 'moderated_at']);
            $approved = $data['decision'] === 'approved';
            $photo->forceFill([
                'moderation_status' => $data['decision'],
                'moderation_feedback' => $approved ? null : $data['reason'],
                'moderated_by' => $request->user()->id,
                'moderated_at' => now(),
            ])->save();

            AdminAuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => 'profile_photo.'.$data['decision'],
                'target_type' => 'profile_photo',
                'target_id' => $photo->id,
                'old_values' => $before,
                'new_values' => $photo->only(['moderation_status', 'moderation_feedback', 'moderated_by', 'moderated_at']),
                'ip_address' => $request->ip(),
            ]);

            return $this->success(
                $this->payload($photo->load(['user:id,name,email,status', 'user.profile:id,user_id,profile_code,display_name'])),
                $approved ? 'Photo approved.' : 'Photo rejected.'
            );
        });
    }

    private function payload(ProfilePhoto $photo): array
    {
        return [
            'id' => $photo->id,
            'content_url' => url('/api/v1/profile/photos/'.$photo->id.'/content'),
            'mime_type' => $photo->mime_type,
            'width' => $photo->width,
            'height' => $photo->height,
            'size_bytes' => $photo->size_bytes,
            'is_primary' => (bool) $photo->is_primary,
            'visibility' => $photo->visibility,
            'moderation_status' => $photo->moderation_status,
            'moderation_feedback' => $photo->moderation_feedback,
            'sort_order' => $photo->sort_order,
            'created_at' => $photo->created_at,
            'user' => $photo->user,
        ];
    }
}
