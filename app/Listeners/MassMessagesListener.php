<?php

namespace App\Listeners;

use App\Helper;
use App\Models\Vault;
use App\Models\Messages;
use Illuminate\Http\File;
use Illuminate\Support\Str;
use App\Models\MediaMessages;
use App\Events\MassMessagesEvent;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;

class MassMessagesListener implements ShouldQueue
{
  public function __construct() {}

  /**
   * Handle the event.
   *
   * @param  MassMessagesEvent  $event
   * @return void
   */
  public function handle(MassMessagesEvent $event)
  {
    // Get data
    $user = $event->user;
    $fileuploader = $event->fileuploader;
    $messageData = $event->messageData;
    $price = $event->priceMessage;
    $hasFileZip = $event->hasFileZip;
    $file = $event->file;
    $originalName = $event->originalName;
    $size = $event->size;

    $hasFileEpub = $event->hasFileEpub;
    $fileEpub = $event->fileEpub;
    $originalNameEpub = $event->originalNameEpub;
    $sizeEpub = $event->sizeEpub;

    // Get Subscriptions Active
    $subscriptionsActive = $user->mySubscriptions()
      ->where('stripe_id', '')
      ->where('ends_at', '>=', now())
      ->orWhere('stripe_status', 'active')
      ->where('stripe_id', '<>', '')
      ->where('creator_id', $user->id)
      ->orWhere('stripe_id', '')
      ->where('creator_id', $user->id)
      ->where('free', 'yes')
      ->get();

    // Send an email notification to all subscribers when there is a new post
    foreach ($subscriptionsActive as $subscriber) {
      $message = new Messages();
      $message->conversations_id = 0;
      $message->from_user_id = $user->id;
      $message->to_user_id = $subscriber->user()->id;
      $message->message = trim(Helper::checkTextDb($messageData));
      $message->updated_at = now();
      $message->price = $price;
      $message->save();

      if ($fileuploader) {
        foreach ($fileuploader as $key => $media) {
          $filename = $media['file'];

          // Parse URL to check if it's from vault
          $parsedUrl = parse_url($filename);
          $isVaultFile = false;

          // Check if it has vault=1 parameter
          if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            $isVaultFile = isset($queryParams['vault']) && $queryParams['vault'] == '1';
          }

          if ($isVaultFile) {
            // It's a vault file
            $cleanFilename = $parsedUrl['path']; // Get only the path without query params
            if (strpos($cleanFilename, '/') === 0) {
              $cleanFilename = substr($cleanFilename, 1); // Remove initial slash if exists
            }

            // Search for the file in vault table
            $vaultFile = Vault::whereFile($cleanFilename)->first();

            if ($vaultFile) {
              // Create new record in media_messages for vault file
              MediaMessages::create([
                'messages_id' => $message->id,
                'type' => $vaultFile->type,
                'file' => $vaultFile->file,
                'width' => $vaultFile->width,
                'height' => $vaultFile->height,
                'video_poster' => $vaultFile->video_poster,
                'duration_video' => $vaultFile->duration_video,
                'quality_video' => $vaultFile->quality_video,
                'vault_id' => $vaultFile->id,
                'encoded' => 'yes',
                'status' => 'active'
              ]);
            } else {
              \Log::warning("Vault file not found from Mass Messages: {$cleanFilename}");
            }
          } else {
            $files = MediaMessages::whereFile($filename)
              ->where('messages_id', '<>', $message->id)
              ->groupBy('file')
              ->get();

            foreach ($files as $key) {
              $mediaMessages = new MediaMessages();
              $mediaMessages->messages_id = $message->id;
              $mediaMessages->type = $key->type;
              $mediaMessages->file = $key->file;
              $mediaMessages->video_poster = $key->video_poster;
              $mediaMessages->width = $key->width;
              $mediaMessages->height = $key->height;
              $mediaMessages->file_name = $key->file_name;
              $mediaMessages->file_size = $key->file_size;
              $mediaMessages->token = $key->token;
              $mediaMessages->status = 'active';
              $mediaMessages->created_at = now();
              $mediaMessages->save();

              if ($key->type == 'video') {
                $this->moveFileStorage($key->file);
              }
            }

            // Delete Old files
            MediaMessages::whereFile($media['file'])->whereMessagesId(0)->delete();
          }
        }
      } // Fileuploader

      if ($hasFileZip) {
        // We insert the file into the database
        MediaMessages::create([
          'messages_id' => $message->id,
          'type' => 'zip',
          'file' => $file,
          'file_name' => $originalName,
          'file_size' => $size,
          'token' => Str::random(150) . uniqid() . now()->timestamp,
          'status' => 'active',
          'created_at' => now()
        ]);
      }

      if ($hasFileEpub) {
        // We insert the file into the database
        MediaMessages::create([
          'messages_id' => $message->id,
          'type' => 'epub',
          'file' => $fileEpub,
          'file_name' => $originalNameEpub,
          'file_size' => $sizeEpub,
          'token' => Str::random(150) . uniqid() . now()->timestamp,
          'status' => 'active',
          'created_at' => now()
        ]);
      }
    }
  }

  protected function moveFileStorage($file)
  {
    try {
      $localFile = public_path('temp/' . $file);
      $path = config('path.messages');

      // Move the file...
      Storage::putFileAs($path, new File($localFile), $file);

      // Delete temp file
      unlink($localFile);
    } catch (\Exception $e) {
      info('Error moving file storage mass messages - ' . $e->getMessage());
    }
  }
}
