<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LiveStreamings extends Model
{
  use HasFactory;

  protected $fillable = [
    'type',
    'user_id',
    'buyer_id',
    'name',
    'channel',
    'minutes',
    'price',
    'status',
    'joined_at',
    'availability',
    'token',
  ];

  protected $casts = [
    'joined_at' => 'datetime',
  ];

  public function user()
  {
    return $this->belongsTo(User::class)->first();
  }

  public function comments()
  {
    return $this->hasMany(LiveComments::class);
  }

  public function likes()
  {
    return $this->hasMany(LiveLikes::class);
  }

  public function onlineUsers()
  {
    return $this->hasMany(LiveOnlineUsers::class)
      ->where('updated_at', '>', now()->subSeconds(10));
  }

  public function getTimeElapsedAttribute()
  {
    return round($this->created_at->diffInMinutes(now()));
  }

  public function getTimeElapsedLivePrivateAttribute()
  {
    if (!$this->joined_at) {
      return 0;
    }
    return floor($this->joined_at->diffInMinutes(now()));
  }
}
