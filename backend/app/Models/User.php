<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['firebase_uid', 'name', 'email', 'phone', 'password', 'status', 'last_login_at'];

    protected $hidden = ['firebase_uid', 'password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function partnerPreference()
    {
        return $this->hasOne(PartnerPreference::class);
    }

    public function profilePhotos()
    {
        return $this->hasMany(ProfilePhoto::class)->orderBy('sort_order');
    }
}
