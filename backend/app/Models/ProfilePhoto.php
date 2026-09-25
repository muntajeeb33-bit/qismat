<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilePhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'disk', 'path', 'mime_type', 'width', 'height', 'size_bytes', 'is_primary',
        'visibility', 'moderation_status', 'moderation_feedback', 'moderated_by', 'moderated_at',
        'sort_order',
    ];

    protected $hidden = ['disk', 'path', 'moderated_by'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }
}
