<?php

namespace App\Services;

use App\Models\MemberNotification;

class MemberNotifier
{
    public function send(int $userId, string $type, string $title, string $body, ?string $action = null, array $data = []): MemberNotification
    {
        return MemberNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action' => $action,
            'data' => $data ?: null,
        ]);
    }
}
