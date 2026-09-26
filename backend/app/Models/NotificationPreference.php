<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id', 'email_new_interest', 'email_interest_accepted', 'email_new_message',
        'email_moderation_updates', 'email_product_updates', 'push_new_interest',
        'push_interest_accepted', 'push_new_message', 'push_moderation_updates',
    ];

    protected function casts(): array
    {
        return [
            'email_new_interest' => 'boolean',
            'email_interest_accepted' => 'boolean',
            'email_new_message' => 'boolean',
            'email_moderation_updates' => 'boolean',
            'email_product_updates' => 'boolean',
            'push_new_interest' => 'boolean',
            'push_interest_accepted' => 'boolean',
            'push_new_message' => 'boolean',
            'push_moderation_updates' => 'boolean',
        ];
    }
}
