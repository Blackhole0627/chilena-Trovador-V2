<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentReel extends Model
{
    protected $fillable = ['user_id', 'reels_id', 'reply'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reels()
    {
        return $this->belongsTo(Reel::class);
    }

    public function replies()
    {
        return $this->hasMany(ReelReply::class, 'comment_reels_id');
    }

    public function likes()
    {
        return $this->hasMany(CommentLikeReel::class, 'comment_reels_id');
    }
}
