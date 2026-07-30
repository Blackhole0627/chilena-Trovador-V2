<?php

namespace App\Http\Controllers;

use Pusher\Pusher;
use App\Models\AudioCall;
use Illuminate\Http\Request;
use App\Enums\AudioCallStatus;

class TimerAudioCallController extends Controller
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

            $videoCall = AudioCall::whereId($request->id)
                ->where('status', AudioCallStatus::ACCEPTED)
                ->first();

            $pusher->trigger(
                $request->channel,
                $request->event,
                $videoCall->timeElapsed
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error sending Pusher event AudioCall: ' . $e->getMessage()], 500);
        }
    }
}
