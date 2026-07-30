<?php

namespace App\Http\Controllers;

use App\Helper;
use App\Models\Reel;
use App\Models\MediaReel;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use App\Jobs\EncodeVideoReel;
use App\Models\Notifications;
use App\Models\Reports;
use App\Services\CoconutVideoService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReelController extends Controller
{
    public function create()
    {
        abort_if(auth()->user()->verified_id != 'yes' || !config('settings.allow_reels'), 404);

        return view('users.create-reel');
    }

    public function store(Request $request)
    {
        $fileuploader = $request->input('fileuploader-list-media');
        $fileuploader = json_decode($fileuploader, TRUE);

        if (!$fileuploader) {
            return response()->json([
                'success' => false,
                'errors' => ['error' => __('general.please_select_video')],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray(),
            ]);
        } //<-- Validator

        $reel = Reel::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'type' => $request->type,
        ]);

        // Update status Media (Image/Video)
        if ($fileuploader) {
            MediaReel::whereName($fileuploader[0]['file'])
                ->update([
                    'reels_id' => $reel->id,
                    'status' => true,
                    'duration_video' => $request->duration ? Helper::getDurationInMinutes($request->duration) : null,
                ]);
        }

        $video = MediaReel::whereReelsId($reel->id)->first();

        if ($video && config('settings.video_encoding') == 'on') {
            try {
                if (config('settings.encoding_method') == 'ffmpeg') {
                    $this->dispatch(new EncodeVideoReel($video));
                } else {
                    CoconutVideoService::handle($video, 'reel');
                }

                // Change status Pending to Encode
                Reel::whereId($reel->id)->update([
                    'status' => 'encode'
                ]);

                return response()->json([
                    'success' => true,
                    'encode' => true
                ]);
            } catch (\Exception $e) {
                info('Error creating Reel: ' . $e->getMessage());

                $this->deleteReelError($reel->id);

                return response()->json([
                    'success' => false,
                    'failed' => true,
                    'errors' => ['error' => $e->getMessage()],
                ]);
            }
        } else {
            $reel->update([
                'status' => 'active'
            ]);

            $this->moveFileStorage($video->name, config('path.reels'));

            if ($request->video_thumbnail) {
                $this->moveFileStorage($request->video_thumbnail, config('path.reels'));

                $video->update([
                    'video_poster' => $request->video_thumbnail
                ]);
            }

            return response()->json([
                'success' => true,
                'url' => route('reels.section.show', $reel->id)
            ]);
        }
    }

    protected function deleteReelError($id): void
    {
        $reel = Reel::with(['media'])->whereId($id)->first();

        if ($reel->media) {
            $localFile = public_path('temp/' . $reel->media->name);

            unlink($localFile);

            $reel->media->delete();
        }

        $reel->delete();
    }

    protected function moveFileStorage($file, $path): void
    {
        $localFile = public_path('temp/' . $file);

        // Move the file...
        Storage::putFileAs($path, new File($localFile), $file);

        // Delete temp file
        unlink($localFile);
    }

    public function destroy($id)
    {
        $pathReels = config('path.reels');
        $reel = Reel::with(['media'])->whereUserId(auth()->id())->whereId($id)->firstOrFail();

        if ($reel->media) {
            Storage::delete($pathReels . $reel->media->name);
            Storage::delete($pathReels . $reel->media->video_poster);

            $reel->media->delete();
        }

        // Delete Notifications
        Notifications::whereIn('type', [27, 29, 30, 31, 32])
            ->where('target', $reel->id)
            ->delete();

        $reel->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return back()->withSuccessMessage(__('general.successfully_removed'));
    }

    public function incrementView(Request $request, $id)
    {
        if (!$request->ajax()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $reel = Reel::find($id);

        if ($reel && $reel->user_id != auth()->id() && !auth()->user()->isSuperAdmin()) {
            $reel->increment('views');
        }
    }

    public function getAllPublicReels($reelId = false)
    {
        $reels = Reel::getRandomRecords(25, 100, function ($query) use ($reelId) {
            $query->where('reels.status', 'active')
                ->where('reels.type', 'public')
                ->when($reelId, function ($query) use ($reelId) {
                    $query->where('reels.id', '<>', $reelId);
                })
                ->select('reels.*');
        }, [
            'user:id,name,username,avatar,cover,hide_name',
            'media'
        ]);

        return $reels;
    }

    /*
     * Show a single reel
    */
    public function show($id)
    {
        abort_if(auth()->guest() && config('settings.age_verification_status'), 404);

        $singleReel = auth()->check()
            ? auth()->user()->singleReel($id)
            : Reel::with(['user:id,name,username,avatar,cover,hide_name', 'media'])
            ->whereId($id)
            ->where('status', 'active')
            ->where('type', 'public')
            ->firstOrFail();

        abort_unless($singleReel->user, 404);

        $reels = auth()->check() ? auth()->user()->reels($id) : $this->getAllPublicReels($id);

        return view('reels.reels', [
            'reels' => $reels,
            'reelSingle' => $singleReel
        ]);
    }

    /*
     * Show all reels
    */
    public function showAll()
    {
        $reels = auth()->check() ? auth()->user()->reels() : $this->getAllPublicReels();

        if ($reels->count() == 0) {
            return redirect()->route('home');
        }

        return view('reels.reels', compact('reels'));
    }

    /**
     *  Load more reels via AJAX
     */
    public function loadMore(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('reels.index');
        }

        $reels = auth()->user()->reels();

        // Check if there are more reels available
        $hasMore = $reels->hasMorePages();

        // Transform the data to send in JSON format
        $formattedReels = [
            'reels' => $reels->map(function ($reel) {
                return [
                    'id' => $reel->id,
                    'media' => [
                        'video' => Helper::getFile(config('path.reels') . $reel->media->name),
                        'video_poster' => Helper::getFile(config('path.reels') . $reel->media->video_poster),
                        'duration_video' => $reel->media->duration_video
                    ],
                    'user' => [
                        'name' => $reel->user->name,
                        'avatar' => Helper::getFile(config('path.avatar') . $reel->user->avatar)
                    ]
                ];
            })->toArray()
        ];

        return response()->json([
            'reels' => $formattedReels,
            'has_more' => $hasMore
        ]);
    }

    public function update(Request $request, $id)
    {
        $reel = Reel::whereUserId(auth()->id())->whereId($id)->firstOrFail();

        $request->validate([
            'title' => 'max:100'
        ]);

        $reel->update([
            'title' => $request->title
        ]);

        return back()->withSuccessMessage(__('admin.success_update'));
    }

    public function report(Request $request)
    {
        $data = Reports::firstOrNew([
            'user_id' => auth()->id(),
            'report_id' => $request->id,
            'type' => 'reels'
        ]);

        $validator = Validator::make($request->all(), [
            'reason' => 'required|in:spoofing,copyright,privacy_issue,violent_sexual,spam,fraud',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray(),
            ]);
        }

        if ($data->exists) {
            return response()->json([
                'success' => false,
                'errors' => ['error' => __('general.already_sent_report')],
            ]);
        } else {
            $data->reason = $request->reason;
            $data->save();

            return response()->json([
                'success' => true,
                'text' => __('general.reported_success'),
            ]);
        }
    }
}
