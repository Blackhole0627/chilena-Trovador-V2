<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionUpcomingRenewals extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $user, public string $type) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $detailUpcomingRenewal = $this->type == 'monthly'
            ? __('general.detail_upcoming_renewal_three_days_before', ['creator' => $this->user->username])
            : __('general.detail_upcoming_renewal_six_months_before', ['creator' => $this->user->username]);

        return (new MailMessage)
            ->subject(__('general.upcoming_renewal_for', ['creator' => $this->user->username]))
            ->greeting(__('emails.hello') . ' ' . $notifiable->name)
            ->line($detailUpcomingRenewal)
            ->action(__('general.manage_subscriptions'), url('my/subscriptions'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
