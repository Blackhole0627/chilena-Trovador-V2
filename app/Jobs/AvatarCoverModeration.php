<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\SightEngineService;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class AvatarCoverModeration implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user, public string $type) {}
    
    public function handle(SightEngineService $service): void
    {
        $service->checkAvatarCover($this->user, $this->type);
    }
}
