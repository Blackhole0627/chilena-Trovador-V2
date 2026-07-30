<?php
 
namespace App\Http\Controllers;
 
use App\Helper;
use Ramsey\Uuid\Uuid;
use App\Models\VideoCall;
use Illuminate\Http\Request;
use App\Enums\VideoCallStatus;
use Illuminate\Support\Facades\DB;
use Peterujah\Agora\Agora as AgoraClient;
use Peterujah\Agora\User as AgoraUser;
use Peterujah\Agora\Roles as AgoraRoles;
use Peterujah\Agora\Builders\RtcToken as AgoraRtcToken;
 
final class VideoCallController extends Controller
{
    use Traits\Functions;
 
    public function store(Request $request)
    {
        $user = auth()->user();
 
        $videoCall = VideoCall::create([
            'seller_id' => $user->id,
            'buyer_id' => $request->user,
            'price' => $user->price_video_call,
            'status' => VideoCallStatus::CALLING,
            'minutes' => $user->video_call_duration,
            'token' => Uuid::uuid4()->toString()
        ]);
 
        return response()->json([
            'status' => true,
            'buyer' => $videoCall->buyer_id,
            'videoCallId' => $videoCall->id,
        ]);
    }
 
    public function accept($id)
    {
        return DB::transaction(function () use ($id) {
            $videoCall = VideoCall::with(['seller:id,balance'])
                ->whereId($id)
                ->whereStatus(VideoCallStatus::CALLING)
                ->lockForUpdate()
                ->first();
 
            if (!$videoCall) {
                return response()->json([
                    'success' => false,
                    'message' => __('general.video_call_not_found')
                ]);
            }
 
            if (auth()->user()->wallet < Helper::amountGross($videoCall->price)) {
                $videoCall->update([
                    'status' => VideoCallStatus::REJECTED
                ]);
                return response()->json([
                    'success' => false,
                    'message' => __('general.not_enough_funds')
                ]);
            }
 
            if ($videoCall->paid === 0) {
                $this->insertTransaction($videoCall->seller, $videoCall->price);
                $videoCall->buyer->decrement('wallet', Helper::amountGross($videoCall->price));
 
                $videoCall->update([
                    'status' => VideoCallStatus::ACCEPTED,
                    'paid' => 1
                ]);
 
                return response()->json([
                    'status' => true,
                    'seller' => $videoCall->seller->id
                ]);
            }
        });
    }
 
    public function reject($id)
    {
        $videoCall = VideoCall::find($id);
 
        if ($videoCall) {
            $videoCall->update([
                'status' => VideoCallStatus::REJECTED
            ]);
        }
 
        return response()->json([
            'status' => true
        ]);
    }
 
    public function cancel($id)
    {
        $videoCall = VideoCall::whereId($id)
            ->where('buyer_id', request()->user)
            ->orWhere('id', $id)
            ->whereSellerId(auth()->id())
            ->first();
 
        if ($videoCall && $videoCall->status == VideoCallStatus::CALLING) {
            $videoCall->update([
                'status' => VideoCallStatus::CANCELED
            ]);
        }
 
        return response()->json([
            'status' => true
        ]);
    }
 
    public function delete($id)
    {
        VideoCall::whereId($id)->delete();
 
        return response()->json([
            'status' => true
        ]);
    }
 
    public function videoCallUrl($token)
    {
        $videoCall = VideoCall::with([
            'seller:id,name,avatar,username',
            'buyer:id,name,avatar',
        ])
            ->where(function ($query) use ($token) {
                $query->where('token', $token)
                    ->whereStatus(VideoCallStatus::ACCEPTED)
                    ->where('seller_id', auth()->id())
                    ->whereNull('ended_at');
            })
            ->orWhere(function ($query) use ($token) {
                $query->where('token', $token)
                    ->whereStatus(VideoCallStatus::ACCEPTED)
                    ->where('buyer_id', auth()->id())
                    ->whereNull('ended_at');
            })
            ->firstOrFail();
 
        if (is_null($videoCall->started_at) && $videoCall->seller_id == auth()->id()) {
            $videoCall->update([
                'started_at' => now()
            ]);
        }
 
        if (is_null($videoCall->joined_at) && $videoCall->buyer_id == auth()->id()) {
            $videoCall->update([
                'joined_at' => now()
            ]);
        }
 
        $agoraToken = null;
 
        if (config('settings.agora_app_id') && config('settings.primary_certificate_agora')) {
            try {
                $role = AgoraRoles::RTC_PUBLISHER;
                $expireTimeInSeconds = 3600 * 2;
 
                $agoraClient = new AgoraClient(
                    config('settings.agora_app_id'),
                    config('settings.primary_certificate_agora')
                );
                $agoraClient->setExpiration($expireTimeInSeconds);
 
                $agoraUser = (new AgoraUser(0))
                    ->setPrivilegeExpire($expireTimeInSeconds)
                    ->setChannel("video-call-channel-{$videoCall->id}")
                    ->setRole($role);
 
                $agoraToken = AgoraRtcToken::buildTokenWithUid($agoraClient, $agoraUser);
            } catch (\Exception $e) {
                \Log::error('Video Call Agora Token generation failed: ' . $e->getMessage());
            }
        }
 
        return view('users.video-call-live')->with([
            'videoCall' => $videoCall,
            'agoraToken' => $agoraToken
        ]);
    }

    public function videoCallFinish(VideoCall $videoCall)
    {
        abort_if(
            $videoCall->seller->id != auth()->id()
                && $videoCall->buyer_id != auth()->id(),
            404
        );

        $videoCall->update([
            'ended_at' => now()
        ]);

        return redirect('/');
    }

    protected function insertTransaction($seller, $price)
    {
        // Admin and user earnings calculation
        $earnings = $this->earningsAdminUser(auth()->user()->custom_fee, $price, null, null);

        // Insert Transaction
        $this->transaction(
            'videocall_' . str_random(25),
            auth()->id(),
            0,
            $seller->id,
            $price,
            $earnings['user'],
            $earnings['admin'],
            'Wallet',
            'video_calls',
            $earnings['percentageApplied'],
            auth()->user()->taxesPayable()
        );

        $seller->increment('balance', $earnings['user']);
    }
}
