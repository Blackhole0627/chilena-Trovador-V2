<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationRequests extends Model
{
  protected $guarded = [];
  const UPDATED_AT = null;

  public function user()
  {
    return $this->belongsTo(User::class)->first();
  }

  public function creator()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
