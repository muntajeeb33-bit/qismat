<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Interest extends Model { protected $fillable=['sender_id','receiver_id','status','message','responded_at']; protected function casts():array{return ['responded_at'=>'datetime'];} public function sender(){return $this->belongsTo(User::class,'sender_id');} public function receiver(){return $this->belongsTo(User::class,'receiver_id');} }
