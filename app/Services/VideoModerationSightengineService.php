<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use App\Models\AdminSettings as Setting;

class VideoModerationSightengineService
{
  private string $apiUser;
  private string|int $apiSecret;
  private string $baseUrl;

  public function __construct()
  {
    $this->apiUser = Setting::value('sightengine_api_user');
    $this->apiSecret = Setting::value('sightengine_api_api_secret');
    $this->baseUrl = 'https://api.sightengine.com/1.0/';
  }

  public function processVideo($videoPath, $videoId)
  {
    try {
      // Step 1: Create upload
      $uploadData = $this->createVideoUpload();

      if ($uploadData['status'] !== 'success') {
        throw new Exception('Failed to create video upload');
      }

      // Step 2: Upload video
      $uploaded = $this->uploadVideo($uploadData['upload']['url'], $videoPath);

      if (!$uploaded) {
        throw new Exception('Failed to upload video');
      }

      // Step 3: Moderate video
      $moderationResult = $this->moderateVideo($uploadData['media']['id'], $videoId);

      return [
        'upload_data' => $uploadData,
        'moderation_result' => $moderationResult,
      ];
    } catch (Exception $e) {
      throw new Exception('Video processing failed: ' . $e->getMessage());
    }
  }

  public function createVideoUpload()
  {
    try {
      $response = Http::get($this->baseUrl . '/upload/create-video.json', [
        'api_user' => $this->apiUser,
        'api_secret' => $this->apiSecret,
      ]);

      if ($response->successful()) {
        return $response->json();
      }

      throw new \Exception('Error creating video upload: ' . $response->body());
    } catch (\Exception $e) {
      throw new \Exception('Failed to create video upload: ' . $e->getMessage());
    }
  }

  private function getContentFromUrl($url)
  {
    try {
      $response = Http::timeout(360)->get($url);

      if ($response->successful()) {
        return $response->body();
      }

      throw new Exception('Failed to download video from URL: ' . $response->status());
    } catch (Exception $e) {
      throw new Exception('Error downloading video from URL: ' . $e->getMessage());
    }
  }

  public function uploadVideo($presignedUrl, $videoPath)
  {
    try {
      $videoContent = $this->getContentFromUrl($videoPath);
      $fileSize = strlen($videoContent);

      $response = Http::withBody($videoContent, 'video/*')
        ->withHeaders([
          'Content-Length' => $fileSize,
        ])
        ->timeout(120)
        ->put($presignedUrl);

      if ($response->successful()) {
        return true;
      }

      throw new Exception('Error uploading video: ' . $response->body());
    } catch (Exception $e) {
      throw new Exception('Failed to upload video: ' . $e->getMessage());
    }
  }

  public function moderateVideo($mediaId, $videoId)
  {
    try {
      $response = Http::asForm()->post($this->baseUrl . '/video/check.json', [
        'media_id' => $mediaId,
        'models' => 'nudity-2.1,violence',
        'api_user' => $this->apiUser,
        'api_secret' => $this->apiSecret,
        'callback_url' => route('webhook.sightengine', $videoId),
      ]);

      if ($response->successful()) {
        return $response->json();
      }

      throw new Exception('Error moderating video: ' . $response->body());
    } catch (Exception $e) {
      throw new Exception('Failed to moderate video: ' . $e->getMessage());
    }
  }
}
