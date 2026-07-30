<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReelReply extends Model
{
    protected $fillable = ['user_id', 'reels_id', 'comment_reels_id', 'reply'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reels()
    {
        return $this->belongsTo(Reel::class);
    }

    public function commentReels()
    {
        return $this->belongsTo(CommentReel::class);
    }

    public function likes()
    {
        return $this->hasMany(CommentLikeReel::class, 'reel_replies_id');
    }
}
