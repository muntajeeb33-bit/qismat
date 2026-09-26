<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_id', 'body', 'read_at', 'deleted_at'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'deleted_at' => 'datetime'];
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
