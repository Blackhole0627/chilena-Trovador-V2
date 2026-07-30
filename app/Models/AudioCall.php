<?php

namespace App\Models;

use App\Enums\AudioCallStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioCall extends Model
{
    protected $fillable = [
        'seller_id',
        'buyer_id',
        'price',
        'status',
        'minutes',
        'token',
        'started_at',
        'joined_at',
        'ended_at',
        'paid',
        'created_at'
    ];

    protected $casts = [
        'status' => AudioCallStatus::class,
        'video_call_date' => 'datetime',
        'started_at' => 'datetime',
        'joined_at' => 'datetime',
        'ended_at' => 'datetime'
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTimeElapsedAttribute()
    {
        $diff = (int) $this->minutes - $this->joined_at->diffInMinutes(now());
        return $diff < 0 ? 0 : number_format($diff, 0);
    }

    public function canBeCanceledAsUnanswered(): bool
    {
        return $this->created_at->diffInSeconds(now()) >= 30
            && $this->status === AudioCallStatus::CALLING
            && $this->joined_at === null;
    }
}
