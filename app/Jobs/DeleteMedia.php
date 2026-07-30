<?php

namespace App\Jobs;

use App\Models\Media;
use App\Models\Messages;
use App\Models\MediaReel;
use App\Models\VideoCall;
use App\Models\MediaStories;
use App\Models\MediaMessages;
use Illuminate\Bus\Queueable;
use App\Enums\VideoCallStatus;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\AdminSettings as Setting;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteMedia implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function handle()
  {
    $path      = config('path.images');
    $pathVideo = config('path.videos');
    $pathMusic = config('path.music');
    $pathFile  = config('path.files');
    $pathMessages = config('path.messages');
    $pathStories = config('path.stories');
    $pathReels = config('path.reels');

    // Files Media Post
    $files = Media::whereUpdatesId(0)->get();

    foreach ($files as $media) {
      $dateOriginalPlusMinutes = $media->created_at->addHours(3);

      if (now() > $dateOriginalPlusMinutes) {
        if ($media->image) {
          Storage::delete($path . $media->image);
          $media->delete();
        }

        if ($media->video) {
          Storage::delete($pathVideo . $media->video);
          Storage::delete($pathVideo . $media->video_poster);
          $media->delete();
        }

        if ($media->music) {
          Storage::delete($pathMusic . $media->music);
          $media->delete();
        }

        if ($media->file) {
          Storage::delete($pathFile . $media->file);
          $media->delete();
        }
      } // dateOriginalPlusMinutes
    }

    // File Media Messages
    $filesMessages = MediaMessages::whereMessagesId(0)->get();

    foreach ($filesMessages as $media) {
      $dateOriginalPlusMinutes = $media->created_at->addHours(3);
      if (now() > $dateOriginalPlusMinutes) {
        Storage::delete($pathMessages . $media->file);
        Storage::delete($pathMessages . $media->video_poster);
        $media->delete();
      }
    }

    // File Media Stories
    $filesStories = MediaStories::whereStoriesId(0)->get();

    foreach ($filesStories as $media) {
      $dateOriginalPlusMinutes = $media->created_at->addHours(3);
      if (now() > $dateOriginalPlusMinutes) {
        Storage::delete($pathStories . $media->name);
        Storage::delete($pathStories . $media->video_poster);
        $media->delete();
      }
    }

    // File Media Reels
    $filesReels = MediaReel::whereReelsId(0)->get();

    foreach ($filesReels as $media) {
      $dateOriginalPlusMinutes = $media->created_at->addHours(3);
      if (now() > $dateOriginalPlusMinutes) {
        Storage::delete($pathReels . $media->name);
        Storage::delete($pathReels . $media->thumbnail);
        $media->delete();
      }
    }

    // Delete files on Local folder 'temp'
    try {
      collect(Storage::disk('default')->listContents('temp', true))
        ->each(function ($file) {
          if ($file['type'] == 'file' && $file['lastModified'] < now()->subHours(3)->getTimestamp()) {
            Storage::disk('default')->delete($file['path']);
          }
        });
    } catch (\Exception $e) {
    }

    // Delete Messages last 6 months
    if (Setting::value('delete_old_messages')) {
      $getLastMessages = Messages::where('created_at', '<', now()->subMonths(6)->format('Y-m-d'))->get();

      if ($getLastMessages->isNotEmpty()) {
        foreach ($getLastMessages as $message) {

          foreach ($message->media as $media) {
            Storage::delete(config('path.messages') . $media->file);
            Storage::delete(config('path.messages') . $media->video_poster);

            $media->delete();
          }

          $message->delete();
        }
      }
    }

    // Delete Video Calls Rejected or Canceled
     $videoCalls = VideoCall::whereIn('status', [
       VideoCallStatus::REJECTED,
       VideoCallStatus::CANCELED,
       VideoCallStatus::UNANSWERED
     ])->get();

     if ($videoCalls->isNotEmpty()) {
       foreach ($videoCalls as $videoCall) {
         $videoCall->delete();
       }
     }
  }
}
