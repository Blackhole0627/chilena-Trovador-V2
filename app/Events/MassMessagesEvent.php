<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MassMessagesEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $fileuploader;
    public $messageData;
    public $priceMessage;
    public $hasFileZip;
    public $file;
    public $originalName;
    public $size;
    public $hasFileEpub;
    public $fileEpub;
    public $originalNameEpub;
    public $sizeEpub;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
          $authUser,
          $fileuploader,
          $messageData,
          $priceMessage,
          $hasFileZip,
          $file,
          $originalName,
          $size,
          $hasFileEpub,
          $fileEpub,
          $originalNameEpub,
          $sizeEpub,
      ) {
        $this->user = $authUser;
        $this->fileuploader = $fileuploader;
        $this->messageData = $messageData;
        $this->priceMessage = $priceMessage;
        $this->hasFileZip = $hasFileZip;
        $this->file = $file;
        $this->originalName = $originalName;
        $this->size = $size;
        $this->hasFileEpub = $hasFileEpub;
        $this->fileEpub = $fileEpub;
        $this->originalNameEpub = $originalNameEpub;
        $this->sizeEpub = $sizeEpub;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
