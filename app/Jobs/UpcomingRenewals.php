<?php

namespace App\Jobs;

use App\Models\Subscriptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\SubscriptionUpcomingRenewals;

class UpcomingRenewals implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::lock('upcoming-renewals', 10)->get(function () {
            $upcomingRenewals = Subscriptions::with('subscriber:id,username,name,email_upcoming_renewals', 'creator:id,username')
                ->whereRaw('HOUR(TIMEDIFF(ends_at, NOW())) <= 72')
                ->whereRaw('ends_at < NOW() + INTERVAL 3 DAY')
                ->where('interval', '<>', 'yearly')
                ->whereCancelled('no')
                ->latest()
                ->whereIn('id', function ($q) {
                    $q->selectRaw('MAX(id) FROM subscriptions GROUP BY creator_id, user_id');
                })
                ->get();

            if ($upcomingRenewals) {
                foreach ($upcomingRenewals as $subscription) {
                    if ($subscription->subscriber->email_upcoming_renewals) {
                        $subscription->subscriber->notify(new SubscriptionUpcomingRenewals(user: $subscription->creator, type: 'monthly'));
                    }
                }
            }

            // Upcoming Renewals for Yearly
            $upcomingRenewalsYearly = Subscriptions::with('subscriber:id,username,name,email_upcoming_renewals', 'creator:id,username')
                ->whereRaw('TIMESTAMPDIFF(MONTH, NOW(), ends_at) <= 6')
                ->whereRaw('ends_at >= NOW()')
                ->whereRaw('ends_at < NOW() + INTERVAL 6 MONTH')
                ->where('interval', 'yearly')
                ->whereCancelled('no')
                ->latest()
                ->whereIn('id', function ($q) {
                    $q->selectRaw('MAX(id) FROM subscriptions GROUP BY creator_id, user_id');
                })
                ->get();

            if ($upcomingRenewalsYearly) {
                foreach ($upcomingRenewalsYearly as $subscriptionYearly) {
                    if ($subscriptionYearly->subscriber->email_upcoming_renewals) {
                        $subscriptionYearly->subscriber->notify(new SubscriptionUpcomingRenewals(user: $subscriptionYearly->creator, type: 'yearly'));
                    }
                }
            }
        });
    }
}
