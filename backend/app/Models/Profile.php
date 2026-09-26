<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'profile_code', 'created_by', 'display_name', 'moderation_status', 'discovery_opt_in', 'submitted_at', 'approved_at', 'moderation_feedback', 'moderated_by', 'moderated_at', 'gender', 'date_of_birth', 'height_cm', 'marital_status',
        'religion', 'community', 'mother_tongue', 'country', 'state', 'city', 'education', 'occupation',
        'company', 'annual_income', 'about_me', 'family_details', 'partner_expectations', 'profile_completion',
        'verification_status', 'visibility', 'last_active_at',
    ];

    protected $hidden = ['moderation_feedback', 'moderated_by', 'moderated_at'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'family_details' => 'array', 'partner_expectations' => 'array', 'last_active_at' => 'datetime', 'discovery_opt_in' => 'boolean', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'moderated_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function photos()
    {
        return $this->hasMany(ProfilePhoto::class, 'user_id', 'user_id')->orderBy('sort_order');
    }

    public function favouritedBy()
    {
        return $this->hasMany(Favourite::class, 'favourite_user_id', 'user_id');
    }
}
