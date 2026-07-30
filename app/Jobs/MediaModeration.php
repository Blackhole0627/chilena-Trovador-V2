<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\SightEngineService;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class MediaModeration implements ShouldQueue
{
    use Queueable;

    public function __construct(public Media $media) {}

    /**
     * Execute the job.
     */
    public function handle(SightEngineService $service): void
    {
        if ($this->media->type == 'image') {
            $service->checkImage($this->media);
        }

        if ($this->media->type == 'video') {
            $service->checkVideo($this->media);
        }
    }
}
