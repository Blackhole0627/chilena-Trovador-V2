<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AdminSettings as Setting;
use App\Http\Controllers\Traits\PushNotificationTrait;

class Notifications extends Model
{
	use PushNotificationTrait;

	protected $guarded = [];
	const UPDATED_AT = null;

	public function user()
	{
		return $this->belongsTo(User::class)->first();
	}

	public static function send($userDestination, $userAuthor, $type, $target, $context = null)
	{
		$user   = User::find($userDestination);
		$author = User::find($userAuthor);
		$getPushNotificationDevices = $user->oneSignalDevices->pluck('player_id')->all();

		if (
			$type == 5 && $user->notify_new_tip == 'no'
			|| $type == 6 && $user->notify_new_ppv == 'no'
			|| $type == 30 && !$user->notify_commented_reel
			|| $type == 32 && !$user->notify_liked_reel
		) {
			return false;
		}

		self::create([
			'destination' => $userDestination,
			'author' => $userAuthor,
			'type' => $type,
			'target' => $target,
			'context' => $context
		]);

		// Send push notification
		if (Setting::value('push_notification_status') && $getPushNotificationDevices) {
			$authorName = $author->hide_name == 'yes' ? $author->username : $author->name;
			$post       = Updates::find($target);
			$postUrl    = $post ? url($post->user()->username . '/' . 'post', $post->id) : null;

			app()->setLocale($user->language);

			switch ($type) {
				case 1:
					$msg             = $authorName . ' ' . __('users.has_subscribed');
					$linkDestination = url('notifications');
					break;
				case 2:
					$msg             = $authorName . ' ' . __('users.like_you');
					$linkDestination = $postUrl;
					break;
				case 3:
					$msg             = $authorName . ' ' . __('users.comment_you');
					$linkDestination = $postUrl;
					break;
				case 4:
					$msg             = $authorName . ' ' . __('general.like_your_comment');
					$linkDestination = $postUrl;
					break;

				case 5:
					$msg             = $authorName . ' ' . __('general.has_sent_you_tip');
					$linkDestination = url('my/payments/received');
					break;

				case 6:
					$msg         	 = $authorName . ' ' . __('general.has_bought_your_message');
					$linkDestination = url('messages', $user->id);
					break;

				case 7:
					$msg         	 = $authorName . ' ' . __('general.has_bought_your_content');
					$linkDestination = $postUrl;
					break;

				case 8:
					$msg          	 = __('general.has_approved_your_post');
					$linkDestination = $postUrl;
					break;

				case 9:
					$msg          	 = __('general.video_processed_successfully_post');
					$linkDestination = $postUrl;
					break;

				case 10:
					$msg             = __('general.video_processed_successfully_message');
					$linkDestination = url('notifications');
					break;

				case 11:
					$msg             = __('general.referrals_made_transaction');
					$linkDestination = url('my/referrals');
					break;

				case 12:
					$msg         	 = __('general.payment_received_subscription_renewal');
					$linkDestination = url('my/payments/received');
					break;

				case 13:
					$msg          	 = $authorName . ' ' . __('general.has_changed_subscription_paid');
					$linkDestination = url($author->username);
					break;

				case 14:
					$msg             = $authorName . ' ' . __('general.is_streaming_live');
					$linkDestination = url('live', $author->username);
					break;

				case 15:
					$msg         	 = $authorName . ' ' . __('general.has_bought_your_item');
					$linkDestination = url('my/sales');
					break;

				case 16:
					$msg             = $authorName . ' ' . __('general.has_mentioned_you_post');
					$linkDestination = $postUrl;
					break;

				case 17:
					$msg             = __('general.story_successfully_posted');
					$linkDestination = url('/');
					break;

				case 18:
					$msg             = __('general.body_account_verification_approved');
					$linkDestination = url('/');
					break;

				case 19:
					$msg             = __('general.body_account_verification_reject');
					$linkDestination = url('/');
					break;

				case 20:
					$msg             = __('general.error_video_encoding_post');
					$linkDestination = url('my/posts');
					break;

				case 21:
					$msg             = __('general.error_video_encoding_message');
					$linkDestination = url('messages');
					break;
				case 22:
					$msg             = __('general.error_video_encoding_story');
					$linkDestination = url('my/stories');
					break;

				case 23:
					$msg             = $authorName . ' ' . __('general.has_sent_private_live_stream_request');
					$linkDestination = url('my/live/private/requests');
					break;

				case 24:
					$msg             = __('general.video_processed_successfully_welcome_message');
					$linkDestination = url('settings/conversations');
					break;

				case 25:
					$msg             = __('general.error_video_encoding_welcome_msg');
					$linkDestination = url('settings/conversations');
					break;

				case 26:
					$msg             = $authorName . ' ' . __('general.has_sent_you_gift');
					$linkDestination = url('my/payments/received');
					break;

				case 27:
					$msg             = __('general.reel_successfully_posted');
					$linkDestination = url('my/reels');
					break;

				case 28:
					$msg             = __('general.error_video_encoding_reel');
					$linkDestination = url('my/reels');
					break;

				case 29:
					$msg             = __('general.liked_your_reel');
					$linkDestination = route('reels.section.show', $target);
					break;

				case 30:
					$msg             = __('general.commented_your_reel');
					$linkDestination = route('reels.section.show', $target);
					break;

				case 31:
					$msg             = __('general.has_mentioned_you_reel');
					$linkDestination = route('reels.section.show', $target);
					break;

				case 32:
					$msg             = __('general.liked_your_comment_reel');
					$linkDestination = route('reels.section.show', $target);
					break;

				case 33:
					$msg             = __('general.content_blocked_moderation', ['name' => $context]);
					$linkDestination = url('notifications');
					break;

				case 34:
					$msg             = __('general.error_moderation_file', ['name' => $context]);
					$linkDestination = url('notifications');
					break;

				case 35:
					$msg             = __('general.video_processed_successfully_vault', ['name' => $context]);
					$linkDestination = url('notifications');
					break;

				case 36:
					$msg             = __('general.error_video_encoding_vault', ['name' => $context]);
					$linkDestination = url('notifications');
					break;

				case 37:
					$msg             = __('general.success_age_verification');
					$linkDestination = url('notifications');
					break;

				case 38:
					$msg             = __('general.failed_age_verification');
					$linkDestination = url('notifications');
					break;

				case 39:
					$msg             = $authorName . ' ' . __('donations.has_contributed_to_your_campaign');
					$linkDestination = $postUrl;
					break;

				case 40:
					$msg             = __('general.failed_verification_avatar');
					$linkDestination = url('notifications');
					break;

				case 41:
					$msg             = __('general.failed_verification_cover');
					$linkDestination = url('notifications');
					break;
			}

			try {
				// Send push notification
				PushNotificationTrait::sendPushNotification($msg, $linkDestination, $getPushNotificationDevices);
			} catch (\Exception $e) {
				info('Push Notification Error - ' . $e->getMessage());
			}
		}
	}

	public static function sendPushNotificationAdmin($user)
	{
		try {
			$user = User::findOrFail($user);
			$admin = User::wherePermissions('full_access')->first();
			$getPushNotificationDevices = $admin->oneSignalDevices->pluck('player_id')->all();
			$msg = __('general.new_transaction_from', ['user' => $user->username]);
			$linkDestination = url('panel/admin/transactions');

			if (Setting::value('push_notification_status') && $getPushNotificationDevices) {
				PushNotificationTrait::sendPushNotification($msg, $linkDestination, $getPushNotificationDevices);
			}
		} catch (\Exception $e) {
			info('Push Notification Error (From sendPushNotificationAdmin) - ' . $e->getMessage());
		}
	}
}
