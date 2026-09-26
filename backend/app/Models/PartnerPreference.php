<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'min_age', 'max_age', 'min_height_cm', 'max_height_cm', 'marital_statuses',
        'religions', 'denominations', 'communities', 'sub_communities', 'ethnicities', 'mother_tongues', 'countries', 'cities', 'education_preferences',
        'occupation_preferences', 'open_to_relocation', 'summary',
    ];

    protected function casts(): array
    {
        return [
            'marital_statuses' => 'array',
            'religions' => 'array',
            'denominations' => 'array',
            'communities' => 'array',
            'sub_communities' => 'array',
            'ethnicities' => 'array',
            'mother_tongues' => 'array',
            'countries' => 'array',
            'cities' => 'array',
            'education_preferences' => 'array',
            'occupation_preferences' => 'array',
            'open_to_relocation' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
