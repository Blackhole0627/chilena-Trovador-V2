<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\AdminSettings as Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\Traits\UserDelete;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteInactiveUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UserDelete;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (Setting::value('delete_old_users_inactive')) {
            $usersInactive = User::where('last_seen', '<', now()->subYear())
                ->where('verified_id', 'no')
                ->where('wallet', '0.00')
                ->take(10)
                ->get();

            foreach ($usersInactive as $user) {
                $this->deleteUser($user->id);
            }
        }
    }
}
