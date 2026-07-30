<?php

namespace App\Http\Controllers;

use Pusher\Pusher;
use App\Models\VideoCall;
use Illuminate\Http\Request;
use App\Enums\VideoCallStatus;

class TimerVideoCallController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'channel' => 'required|string',
            'event' => 'required|string',
            'id' => 'required|integer'
        ]);

        try {
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                config('broadcasting.connections.pusher.options')
            );

            $videoCall = VideoCall::whereId($request->id)
                ->where('status', VideoCallStatus::ACCEPTED)
                ->first();

            $pusher->trigger(
                $request->channel,
                $request->event,
                $videoCall->timeElapsed
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error sending Pusher event VideoCall: ' . $e->getMessage()], 500);
        }
    }
}
