<?php

namespace App\Jobs;

use FFMpeg;
use Throwable;
use App\Helper;
use App\Models\Media;
use App\Models\Updates;
use Illuminate\Http\File;
use App\Events\NewPostEvent;
use App\Models\AdminSettings;
use App\Models\Notifications;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ProtoneMedia\LaravelFFMpeg\Filters\WatermarkFactory;

class EncodeVideo implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(public Media $video)
  {
    $this->video = $video;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    // Admin Settings
    $settings = AdminSettings::select(
      'watermark_on_videos',
      'auto_approve_post',
      'disable_new_post_notification',
      'moderation_status'
    )->first();

    // Get post
    $getPost = Updates::whereId($this->video->updates_id)->first();

    if (!$getPost) {
      return;
    }

    $date = $getPost->editing ? $getPost->date : now();

    // Status final post
    $statusPost = $getPost->schedule ? 'schedule' : 'active';
    $statusFinalPost = $settings->auto_approve_post == 'on' ? $statusPost : 'pending';

    // Paths
    $disk = 'default';
    $path = 'temp/';
    $videoPathDisk = $path . $this->video->video;
    $videoPathDiskMp4 = $this->video->id . str_random(20) . uniqid() . now()->timestamp . '-converted.mp4';
    $urlWatermark = ucfirst(Helper::urlToDomain(url('/'))) . '/' . $this->video->user()->username;
    $font = public_path('webfonts/arial.TTF');

    // Create Thumbnail Video
    try {
      $videoPoster = str_random(20) . uniqid() . now()->timestamp . '-poster.jpg';

      $ffmpeg = FFMpeg::fromDisk($disk)
        ->open($videoPathDisk)
        ->getFrameFromSeconds(1)
        ->export()
        ->toDisk($disk);

      $ffmpeg->save($path . $videoPoster);

      // Clean
      FFMpeg::cleanupTemporaryFiles();
    } catch (\Exception $e) {
      $videoPoster = null;
    }

    // Create a video format...
    $format = new X264();
    $format->setAudioCodec('aac');
    $format->setVideoCodec('libx264');
    $format->setKiloBitrate(0);

    try {
      // open the uploaded video from the right disk...
      if ($settings->watermark_on_videos == 'on') {
        $ffmpeg = FFMpeg::fromDisk($disk)
          ->open($videoPathDisk)
          ->addFilter(['-strict', -2])
          ->addFilter(function ($filters) use ($urlWatermark, $font) {
            $filters->custom("drawtext=text=$urlWatermark:fontfile=$font:x=W-tw-15:y=H-th-15:fontsize=30:fontcolor=white");
          })
          ->export()
          ->toDisk($disk)
          ->inFormat($format);


        $ffmpeg->save($path . $videoPathDiskMp4);
      } else {
        $ffmpeg = FFMpeg::fromDisk($disk)
          ->open($videoPathDisk)
          ->addFilter(['-strict', -2])
          ->export()
          ->toDisk($disk)
          ->inFormat($format);

        $ffmpeg->save($path . $videoPathDiskMp4);
      }

      // Clean
      FFMpeg::cleanupTemporaryFiles();

      // Delete old video
      Storage::disk('default')->delete($videoPathDisk);

      // Get Duration Video
      $durationInSeconds = $ffmpeg->getFormat()->get('duration');
      $durationVideo = explode('.', $durationInSeconds);
      $durationVideo = (int)$durationVideo[0];

      // Get Dimensions video
      $dimensions = $ffmpeg->getVideoStream()->getDimensions();

      // Update name video on Media table
      Media::whereId($this->video->id)->update([
        'video' => $videoPathDiskMp4,
        'encoded' => 'yes',
        'video_poster' => $videoPoster ?? null,
        'duration_video' => $durationVideo ? Helper::getDurationInMinutes($durationVideo) : null,
        'quality_video' => $dimensions->getWidth() ? Helper::getResolutionVideo($dimensions->getWidth()) : null
      ]);

      // Check if there are other videos that have not been encoded
      $videos = Media::whereUpdatesId($this->video->updates_id)
        ->where('video', '<>', '')
        ->whereEncoded('no')
        ->get();

      // Move Video File to Storage
      $this->moveFileStorage($videoPathDiskMp4);

      // Move Video Poster to Storage
      if ($videoPoster) {
        $this->moveFileStorage($videoPoster);
      }

      if ($videos->count() == 0) {
        // Update date the post and status
        $getPost->update([
          'date' => $date,
          'status' => $statusFinalPost
        ]);

        // Notify to user - destination, author, type, target
        Notifications::send($this->video->user_id, $this->video->user_id, 9, $this->video->updates_id);

        if (!$settings->disable_new_post_notification) {
          // Send notification via Email
          event(new NewPostEvent($getPost));
        }
      }

      // Dispatch Media Moderation Videos
      if ($settings->moderation_status) {
        $getVideoEncoded = Media::whereId($this->video->id)
          ->where('encoded', 'yes')
          ->first();

        if ($getVideoEncoded) {
          dispatch(new MediaModeration($getVideoEncoded));
        } else {
          info('No encoded video found for updates_id: ' . $this->video->updates_id);
        }
      }
    } catch (\Exception $e) {
      // Delete file
      $this->deleteFile($this->video->video);

      $this->deleteFile($videoPathDiskMp4);

      if ($videoPoster) {
        $this->deleteFile($videoPoster);
      }

      $this->failed($e);
    }
  } // End Handle

  /**
   * Move file to Storage
   *
   * @return void
   */
  protected function moveFileStorage($file)
  {
    $disk = config('filesystems.default');
    $path = config('path.videos');
    $localFile = public_path('temp/' . $file);

    // Move the file...
    Storage::disk($disk)->putFileAs($path, new File($localFile), $file);

    // Delete temp file
    unlink($localFile);
  }
  /**
   * Handle a job failure.
   */
  public function failed(?Throwable $exception): void
  {
    // Log the error
    \Log::error('Video encoding failed', [
      'video_id' => $this->video->id,
      'error' => $exception->getMessage(),
      'file' => $exception->getFile(),
      'line' => $exception->getLine()
    ]);

    // Handle the failure using properties already available in the class
    $settings = AdminSettings::first();
    $post = Updates::whereId($this->video->updates_id)->first();
    $statusPost = $post->schedule ? 'schedule' : 'active';
    $statusFinalPost = $settings->auto_approve_post == 'on' ? $statusPost : 'pending';
    $date = $post->editing ? $post->date : now();

    $post->whereStatus('encode')
      ->update([
        'date' => $date,
        'status' => $statusFinalPost
      ]);

    if ($post) {
      // Notify to user (ERROR) - destination, author, type, target
      Notifications::send($post->user_id, $post->user_id, 20, $post->id);

      // Delete Media
      Media::whereUpdatesId($post->id)->delete();
    }
  }

  /**
   * Delete file from temp folder (Error)
   *
   * @return void
   */
  protected function deleteFile($file)
  {
    $localFile = 'temp/' . $file;

    Storage::disk('default')->delete($localFile);
  }
}
