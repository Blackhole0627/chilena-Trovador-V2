<?php

namespace App\Http\Controllers;

use App\Models\Reel;
use App\Models\LikeReel;
use Illuminate\Http\Request;
use App\Models\Notifications;

class LikeReelController extends Controller
{
    public function like(Request $request)
    {
        $reel = Reel::with(['user:id,notify_liked_reel'])->findOrFail($request->id);

        $likeReel = LikeReel::firstOrNew([
            'user_id' => auth()->id(),
            'reels_id' => $reel->id
        ]);

        if (!$likeReel->exists) {
            $reel->increment('likes');

            $likeReel->save();

            if (auth()->id() != $reel->user_id && $reel->user->notify_liked_reel) {
                // destination, author, type, target
                Notifications::send($reel->user_id, auth()->id(), 29, $reel->id);
            }
            
        } else {
            $likeReel->delete();
            $reel->decrement('likes');
        }

        return response()->json([
            'success' => true
        ], 200);
    }
}
