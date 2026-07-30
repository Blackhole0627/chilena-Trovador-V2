<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\Traits\HasRandomSelection;

class Reel extends Model
{
    use HasRandomSelection;

    protected $fillable = [
        'user_id',
        'title',
        'views',
        'likes',
        'comments_count',
        'type',
        'status',
        'created_at'
    ];

    public function media()
    {
        return $this->hasOne(MediaReel::class, 'reels_id')->where('status', 1);
    }

    public function mediaReel()
    {
        return $this->hasMany(MediaReel::class, 'reels_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(CommentReel::class, 'reels_id');
    }
}
