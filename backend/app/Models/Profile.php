<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'profile_code', 'created_by', 'display_name', 'moderation_status', 'discovery_opt_in', 'submitted_at', 'approved_at', 'gender', 'date_of_birth', 'height_cm', 'marital_status',
        'religion', 'community', 'mother_tongue', 'country', 'state', 'city', 'education', 'occupation',
        'company', 'annual_income', 'about_me', 'family_details', 'partner_expectations', 'profile_completion',
        'verification_status', 'visibility', 'last_active_at',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'family_details' => 'array', 'partner_expectations' => 'array', 'last_active_at' => 'datetime', 'discovery_opt_in' => 'boolean', 'submitted_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
