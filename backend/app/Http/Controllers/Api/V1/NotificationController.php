<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\MemberNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $query = MemberNotification::query()->where('user_id', $request->user()->id);

        return $this->success([
            'unread_count' => (clone $query)->whereNull('read_at')->count(),
            'notifications' => $query->latest()->paginate(30),
        ]);
    }

    public function read(Request $request, MemberNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 404);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return $this->success($notification->fresh(), 'Notification marked as read.');
    }

    public function readAll(Request $request)
    {
        MemberNotification::query()->where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return $this->success(null, 'All notifications marked as read.');
    }
}
