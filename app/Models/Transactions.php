<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['approved', 'gift_id'];

    public function user()
    {
      return $this->belongsTo(User::class)->first();
    }

		public function subscribed()
    {
      return $this->belongsTo(User::class, 'subscribed')->first();
    }

    public function subscription()
    {
      return $this->belongsTo(Subscriptions::class, 'subscriptions_id')->first();
    }

    public function gift()
    {
      return $this->belongsTo(Gift::class)->where('status', true);
    }

    public function creator()
    {
      return $this->belongsTo(User::class, 'subscribed');
    }

    public function buyer()
    {
      return $this->belongsTo(User::class, 'user_id');
    }
}
