<?php

namespace App\Jobs;

use FFMpeg;
use Throwable;
use App\Helper;
use App\Models\Reel;
use App\Models\MediaReel;
use Illuminate\Http\File;
use App\Models\Notifications;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\AdminSettings as Setting;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EncodeVideoReel implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $video;

  public $tries = 2;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct(MediaReel $video)
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
    $reel = Reel::with(['user:id,username'])->find($this->video->reels_id);

    // Paths
    $disk = 'default';
    $path = 'temp/';
    $videoPathDisk = $path . $this->video->name;
    $videoPathDiskMp4 = $this->video->id . str_random(20) . uniqid() . now()->timestamp . '-converted.mp4';
    $urlWatermark = ucfirst(Helper::urlToDomain(url('/'))) . '/' . $reel->user->username;
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

    // Optimized video format configuration
    $format = $this->createOptimizedFormat();

    try {
      // Get video dimensions for optimization
      $videoInfo = $this->getVideoInfo($videoPathDisk, $disk);

      // open the uploaded video from the right disk...
      if (Setting::value('watermark_on_videos') == 'on') {
        $ffmpeg = FFMpeg::fromDisk($disk)
          ->open($videoPathDisk)
          ->addFilter(['-strict', -2])
          ->addFilter(['-preset', 'slower']) // Balance between speed and compression
          ->addFilter(['-crf', '20']) // Constant Rate Factor for better quality/size ratio
          ->addFilter(['-maxrate', '4M']) // Maximum bitrate
          ->addFilter(['-bufsize', '8M']) // Buffer size

          ->addFilter(['-profile:v', 'high']) // H.264 profile
          ->addFilter(['-level', '4.0']) // H.264 level
          ->addFilter(['-pix_fmt', 'yuv420p'])
          ->addFilter(['-movflags', '+faststart']) // Pixel format for compatibility

          ->addFilter(['-g', '60'])
          ->addFilter(['-keyint_min', '30']) // GOP size (keyframe interval)
          ->addFilter(['-sc_threshold', '40']) // Scene change threshold

          ->addFilter(['-me_method', 'hex'])
          ->addFilter(['-subq', '7'])
          ->addFilter(['-trellis', '1'])

          // Scale video if too large
          ->addFilter(function ($filters) use ($videoInfo) {
            if ($videoInfo['width'] > 1080 || $videoInfo['height'] > 1920) {
              $filters->custom('scale=min(1080\,iw):min(1920\,ih):force_original_aspect_ratio=decrease');
            }
          })
          // Audio optimization
          ->addFilter(['-c:a', 'aac'])
          ->addFilter(['-b:a', '96k']) // Reduced audio bitrate
          ->addFilter(['-ac', '2']) // Stereo audio
          ->addFilter(['-ar', '44100']) // Audio sample rate
          // Watermark
          ->addFilter(function ($filters) use ($urlWatermark, $font) {
            $filters->custom("drawtext=text=$urlWatermark:fontfile=$font:x=w-tw-50:y=h-th-30:fontsize=30:fontcolor=white");
          })
          ->export()
          ->toDisk($disk)
          ->inFormat($format);

        $ffmpeg->save($path . $videoPathDiskMp4);
      } else {
        $ffmpeg = FFMpeg::fromDisk($disk)
          ->open($videoPathDisk)
          ->addFilter(['-strict', -2])
          ->addFilter(['-preset', 'slower']) // Balance between speed and compression
          ->addFilter(['-crf', '20']) // Constant Rate Factor for better quality/size ratio
          ->addFilter(['-maxrate', '4M']) // Maximum bitrate
          ->addFilter(['-bufsize', '8M']) // Buffer size

          ->addFilter(['-profile:v', 'high']) // H.264 profile
          ->addFilter(['-level', '4.0']) // H.264 level
          ->addFilter(['-pix_fmt', 'yuv420p'])
          ->addFilter(['-movflags', '+faststart']) // Pixel format for compatibility

          ->addFilter(['-g', '60'])
          ->addFilter(['-keyint_min', '30']) // GOP size (keyframe interval)
          ->addFilter(['-sc_threshold', '40']) // Scene change threshold

          ->addFilter(['-me_method', 'hex'])
          ->addFilter(['-subq', '7'])
          ->addFilter(['-trellis', '1'])
          // Scale video if too large
          ->addFilter(function ($filters) use ($videoInfo) {
            if ($videoInfo['width'] > 1080 || $videoInfo['height'] > 1920) {
              $filters->custom('scale=min(1080\,iw):min(1920\,ih):force_original_aspect_ratio=decrease');
            }
          })
          // Audio optimization
          ->addFilter(['-c:a', 'aac'])
          ->addFilter(['-b:a', '96k'])
          ->addFilter(['-ac', '2'])
          ->addFilter(['-ar', '44100'])
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

      // Update name video on Media table
      MediaReel::whereId($this->video->id)->update([
        'name' => $videoPathDiskMp4,
        'video_poster' => $videoPoster ?? null,
        'duration_video' => $durationVideo ? Helper::getDurationInMinutes($durationVideo) : null,
        'status' => true
      ]);

      // Update date the reel and status
      Reel::whereId($this->video->reels_id)->update([
        'created_at' => now(),
        'status' => 'active'
      ]);

      // Move Video File to Storage
      $this->moveFileStorage($videoPathDiskMp4);

      // Move Video Poster to Storage
      if ($videoPoster) {
        $this->moveFileStorage($videoPoster);
      }

      // Notify to user - destination, author, type, target
      Notifications::send($reel->user->id, $reel->user->id, 27, $this->video->reels_id);
    } catch (\Exception $e) {

      if ($this->attempts() >= $this->tries) {
        // Delete files
        $this->deleteFile($path . $videoPathDisk);
        $this->deleteFile($path . $media->name);
        if ($videoPoster) {
          $this->deleteFile($path . $videoPoster);
        }
        $this->failed($e);
      }
      throw $e; // Re-throw to let the job system handle retries
    }
  } // End Handle

  /**
   * Create optimized video format
   *
   * @return X264
   */
  protected function createOptimizedFormat()
  {
    $format = new X264();
    $format->setAudioCodec('aac');
    $format->setVideoCodec('libx264');

    // Don't set a fixed bitrate, let CRF handle it
    $format->setKiloBitrate(0);

    return $format;
  }

  /**
   * Get video information for optimization decisions
   *
   * @param string $videoPath
   * @param string $disk
   * @return array
   */
  protected function getVideoInfo($videoPath, $disk)
  {
    try {
      $ffprobe = FFMpeg::fromDisk($disk)->open($videoPath);
      $videoStream = $ffprobe->getVideoStream();

      return [
        'width' => $videoStream->get('width'),
        'height' => $videoStream->get('height'),
        'duration' => $ffprobe->getFormat()->get('duration')
      ];
    } catch (\Exception $e) {
      // Return default values if probe fails
      return [
        'width' => 1080,
        'height' => 1920,
        'duration' => 0
      ];
    }
  }

  /**
   * Move file to Storage
   *
   * @return void
   */
  protected function moveFileStorage($file)
  {
    $disk = config('filesystems.default');
    $path = config('path.reels');
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
    \Log::error('Video encoding Messages failed', [
      'video_id' => $this->video->id,
      'error' => $exception->getMessage(),
      'file' => $exception->getFile(),
      'line' => $exception->getLine()
    ]);

    $media = MediaReel::whereId($this->video->id)->first();

    // Notify to user (ERROR) - destination, author, type, target
    Notifications::send($reel->user->id, $reel->user->id, 28, 0);

    Reel::whereId($this->video->reels_id)->delete();

    $media->delete();
  }

  /**
   * Delete file from temp folder (Error)
   *
   * @return void
   */
  protected function deleteFile($file)
  {
    $localFile = public_path($file);
    $tempFile  = public_path('temp/' . $this->video->name);

    if (file_exists($localFile)) {
      unlink($localFile);
    }

    if (file_exists($tempFile)) {
      unlink($tempFile);
    }
  }
}
