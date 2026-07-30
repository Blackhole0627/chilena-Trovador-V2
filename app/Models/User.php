<?php

namespace App\Models;

use App\Models\Notifications;
use Laravel\Cashier\Billable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Translation\HasLocalePreference;
use App\Notifications\ResetPassword as ResetPasswordNotification;

class User extends Authenticatable implements HasLocalePreference
{
  use Notifiable, Billable;

  const CREATED_AT = 'date';
  const UPDATED_AT = null;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'username',
    'countries_id',
    'name',
    'email',
    'password',
    'avatar',
    'cover',
    'status',
    'role',
    'permission',
    'confirmation_code',
    'oauth_uid',
    'oauth_provider',
    'token',
    'story',
    'verified_id',
    'ip',
    'language',
    'free_subscription',
    'stripe_connect_id',
    'completed_stripe_onboarding',
    'device_token',
    'document_id',
    'payment_gateway',
    'hide_name',
    'allow_live_streaming_private',
    'price_live_streaming_private',
    'allow_dm',
    'welcome_message_new_subs',
    'send_welcome_message',
    'price_welcome_message',
    'alias_mp',
    'cvu',
    'dark_mode',
    'custom_profit_referral',
    'price_video_call',
    'video_call_duration',
    'price_audio_call',
    'audio_call_duration',
    'phone',
  ];

  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  protected $withCount = [
    'newNotifications',
    'newInbox'
  ];

  protected $casts = [
    'last_seen' => 'datetime:Y-m-d',
  ];

  /**
   * The tax rates that should apply to the customer's subscriptions.
   *
   * @return array
   */
  public function taxRates()
  {
    $taxRates = [];
    $payment = PaymentGateways::whereName('Stripe')
      ->whereEnabled('1')
      ->where('key_secret', '<>', '')
      ->first();

    if ($payment) {
      $stripe = new \Stripe\StripeClient($payment->key_secret);
      $taxes = $stripe->taxRates->all();

      foreach ($taxes->data as $tax) {
        if (
          $tax->active && $tax->state == $this->getRegion()
          && $tax->country == $this->getCountry()
          || $tax->active
          && $tax->country == $this->getCountry()
          && $tax->state == null
        ) {
          $taxRates[] = $tax->id;
        }
      }
    }

    return $taxRates;
  }

  public function isTaxable()
  {
    return TaxRates::whereStatus('1')
      ->whereIsoState($this->getRegion())
      ->whereCountry($this->getCountry())
      ->orWhere('country', $this->getCountry())
      ->whereNull('iso_state')
      ->whereStatus('1')
      ->get();
  }

  public function taxesPayable()
  {
    return $this->isTaxable()
      ->pluck('id')
      ->implode('_');
  }

  public function getCountry()
  {
    $ip = request()->ip();
    return cache('userCountry-' . $ip) ?? ($this->country()->country_code ?? null);
  }

  public function getRegion()
  {
    $ip = request()->ip();
    return cache('userRegion-' . $ip);
  }


  public function sendPasswordResetNotification($token)
  {
    $this->notify(new ResetPasswordNotification($token));
  }

  public function userSubscriptions()
  {
    return $this->hasMany(Subscriptions::class);
  }

  public function mySubscriptions()
  {
    return $this->hasMany(Subscriptions::class, 'creator_id', 'id');
  }

  public function myPayments()
  {
    return $this->hasMany(Transactions::class);
  }

  public function myPaymentsReceived()
  {
    return $this->hasMany(Transactions::class, 'subscribed')->where('approved', '1');
  }

  public function updates()
  {
    return $this->hasMany(Updates::class)->where('status', 'active');
  }

  public function updatesPostDetail()
  {
    return $this->hasMany(Updates::class)->where('status', '<>', 'encode');
  }

  public function media()
  {
    return $this->belongsToMany(
      Updates::class,
      'media',
      'user_id',
      'updates_id'
    )
      ->where('updates.status', 'active')
      ->where('media.status', 'active');
  }

  // Get all ID's of Creators User Subscriber
  protected function fetchCretorsByIdSubscriptions()
  {
    $subscriptions = $this->userSubscriptionsActive();

    foreach ($subscriptions as $key) {
      $feedSubscriptions[] = $key->creator_id;
    }

    // Get current user's content (if creator)
    $feedSubscriptions[] = $this->id;

    return $feedSubscriptions;
  }

  public function feed($skip = null)
  {
    $fetchSubscriptions = $this->fetchCretorsByIdSubscriptions();

    $posts = Updates::getSelectRelations()
      ->whereIntegerInRaw('user_id', $fetchSubscriptions)
      ->where('status', 'active')
      ->groupBy('id')
      ->orderBy('id', 'desc');

    if (isset($skip)) {
      $posts = $posts->skip($skip)
        ->take(config('settings.number_posts_show'))
        ->get();
    } else {
      $posts = $posts->simplePaginate(config('settings.number_posts_show'));
    }

    return $posts;
  }

  public function stories()
  {
    $fetchSubscriptions = $this->fetchCretorsByIdSubscriptions();

    $stories = Stories::select([
      'id',
      'user_id',
      'title',
      'status',
      'created_at'
    ])->whereIntegerInRaw('user_id', $fetchSubscriptions)
      ->where('created_at', '>', date('Y-m-d H:i:s', strtotime('- 1 day')))
      ->whereStatus('active')
      ->with(['user:id,name,username,avatar,hide_name', 'media'])
      ->groupBy('id')
      ->orderBy('id', 'desc')
      ->get();

    return $stories;
  }

  public function reels($reelId = false)
  {
    $fetchSubscriptions = $this->fetchCretorsByIdSubscriptions();

    $reels = Reel::getRandomRecords(25, 100, function ($query) use ($fetchSubscriptions, $reelId) {
      $query->join('users', 'reels.user_id', '=', 'users.id')
        ->where('reels.status', 'active')
        ->where(function ($q) use ($fetchSubscriptions) {
          $q->where('reels.type', 'public')
            ->orWhere(function ($q) use ($fetchSubscriptions) {
              $q->where('reels.type', 'private')
                ->whereIn('reels.user_id', $fetchSubscriptions);
            });
        })
        ->when($reelId, function ($query) use ($reelId) {
          $query->where('reels.id', '<>', $reelId);
        })
        ->select('reels.*');
    }, [
      'user:id,name,username,avatar,cover,hide_name',
      'media'
    ]);

    return $reels;
  }

  public function getReelsActive()
  {
    $fetchSubscriptions = $this->fetchCretorsByIdSubscriptions();

    return Reel::where('status', 'active')
      ->where('type', 'public')
      ->orWhere('type', 'private')
      ->whereIn('user_id', $fetchSubscriptions)
      ->count();
  }

  public function singleReel($id)
  {
    $fetchSubscriptions = $this->fetchCretorsByIdSubscriptions();
    $isAdmin = $this->isSuperAdmin() ?: false;

    $reel = Reel::with(['user:id,name,username,avatar,cover,hide_name', 'media'])
      ->where('id', $id)
      ->where('status', 'active')
      ->where(function ($query) use ($fetchSubscriptions, $isAdmin) {
        $query->where('type', 'public')
          ->orWhere(function ($q) use ($fetchSubscriptions, $isAdmin) {
            $q->where('type', 'private')
              ->where(function ($q) use ($fetchSubscriptions, $isAdmin) {
                $q->when($isAdmin, function ($q) {
                  return $q;
                }, function ($q) use ($fetchSubscriptions) {
                  return $q->whereIn('user_id', $fetchSubscriptions);
                });
              });
          });
      })
      ->firstOrFail();

    return $reel;
  }

  public function allReels()
  {
    return $this->hasMany(Reel::class)->where('status', 'active');
  }

  public function checkLikeOnReel($id)
  {
    return LikeReel::whereReelsId($id)->whereUserId($this->id)->count();
  }

  public function withdrawals()
  {
    return $this->hasMany(Withdrawals::class);
  }

  public function country()
  {
    return $this->belongsTo(Countries::class, 'countries_id')->first();
  }

  public function notifications()
  {
    return $this->hasMany(Notifications::class, 'destination');
  }

  public function newNotifications()
  {
    return $this->notifications()->whereStatus('0');
  }

  public function newInbox()
  {
    return $this->hasMany(Messages::class, 'to_user_id')->where('status', 'new');
  }

  public function unseenNotifications()
  {
    return $this->new_notifications_count;
  }

  public function messagesInbox()
  {
    return $this->new_inbox_count;
  }

  public function comments()
  {
    return $this->hasMany(Comments::class);
  }

  public function likes()
  {
    return $this->hasMany(Like::class);
  }

  public function myLikes()
  {
    return $this->belongsToMany(Updates::class, 'likes', 'user_id', 'updates_id')->where('likes.status', '1');
  }

  public function category()
  {
    return $this->belongsTo(Categories::class, 'categories_id');
  }

  public function verificationRequests()
  {
    return $this->hasMany(VerificationRequests::class)->whereStatus('pending')->count();
  }

  public static function notificationsCount()
  {
    // Notifications Count
    $notifications_count = auth()->user()->unseenNotifications();
    // Messages
    $messages_count = auth()->user()->messagesInbox();

    if ($messages_count != 0 &&  $notifications_count != 0) {
      $totalNotifications = ($messages_count + $notifications_count);
    } elseif ($messages_count == 0 && $notifications_count != 0) {
      $totalNotifications = $notifications_count;
    } elseif ($messages_count != 0 && $notifications_count == 0) {
      $totalNotifications = $messages_count;
    } else {
      $totalNotifications = null;
    }

    return $totalNotifications;
  }

  function getFirstNameAttribute()
  {
    $name = explode(' ', $this->name);
    return $name[0] ?? null;
  }

  function getLastNameAttribute()
  {
    $name = explode(' ', $this->name);
    return $name[1] ?? null;
  }

  public function bookmarks()
  {
    return $this->belongsToMany(Updates::class, 'bookmarks', 'user_id', 'updates_id');
  }

  public function likesCount()
  {
    $regularLikes = $this->hasManyThrough(Like::class, Updates::class, 'user_id', 'updates_id')
      ->where('likes.status', '=', '1')
      ->count();

    $extraLikes = $this->hasMany(Updates::class, 'user_id')
      ->sum('likes_extras');

    return $regularLikes + $extraLikes;
  }

  public function checkSubscription($creator)
  {
    return $this->userSubscriptions()
      ->where('creator_id', $creator->id)
      ->where('ends_at', '>=', now())

      ->orWhere('stripe_status', 'active')
      ->where('creator_id', $creator->id)
      ->whereUserId($this->id)

      ->orWhere('free', 'yes')
      ->where('creator_id', $creator->id)
      ->whereUserId($this->id)
      ->first();
  }

  public function checkPayPerViewMsg($msgId)
  {
    return $this->payPerViewMessages()->where('messages_id', $msgId)->first();
  }

  public function userSubscriptionsActive()
  {
    return $this->userSubscriptions()
      ->select(['stripe_price', 'creator_id', 'ends_at', 'stripe_status', 'free'])
      ->where('ends_at', '>=', now())
      ->orWhere('stripe_status', 'active')
      ->whereUserId($this->id)
      ->orWhere('free', 'yes')
      ->whereUserId($this->id)
      ->get();
  }

  public function subscriptionsActive()
  {
    return $this->mySubscriptions()
      ->where('stripe_id', '=', '')
      ->where('ends_at', '>=', now())
      ->orWhere('stripe_status', 'active')
      ->where('stripe_id', '<>', '')
      ->where('creator_id', $this->id)
      ->orWhere('stripe_id', '=', '')
      ->where('creator_id', $this->id)
      ->where('free', '=', 'yes')
      ->first();
  }

  public function totalSubscriptionsActive()
  {
    return $this->mySubscriptions()
      ->where('ends_at', '>=', now())
      ->where('creator_id', $this->id)
      ->orWhere('stripe_status', 'active')
      ->where('stripe_id', '<>', '')
      ->where('creator_id', $this->id)
      ->orWhere('free', '=', 'yes')
      ->where('creator_id', $this->id)
      ->count();
  }

  public function payPerView()
  {
    return $this->belongsToMany(Updates::class, 'pay_per_views', 'user_id', 'updates_id');
  }


  public function payPerViewMessages()
  {
    return $this->belongsToMany(Messages::class, 'pay_per_views', 'user_id', 'messages_id');
  }

  /**
   * Get the user's preferred locale.
   */
  public function preferredLocale()
  {
    return $this->language;
  }

  /**
   * Get the user's is Super Admin.
   */
  public function isSuperAdmin()
  {
    return $this->permissions == 'full_access' ? $this->id : false;
  }

  /**
   * Get the user's permissions.
   */
  public function hasPermission($section)
  {
    $permissions = explode(',', $this->permissions);

    return in_array($section, $permissions)
      || $this->permissions == 'full_access'
      || $this->permissions == 'limited_access'
      ? true
      : false;
  }

  /**
   * Get the user's blocked countries.
   */
  public function blockedCountries()
  {
    return explode(',', $this->blocked_countries);
  }

  /**
   * Get Referrals.
   */
  public function referrals()
  {
    return $this->hasMany(Referrals::class, 'referred_by');
  }

  public function referralTransactions()
  {
    return $this->hasMany(ReferralTransactions::class, 'referred_by');
  }

  /**
   * Broadcasting Live
   */
  public function isLive()
  {
    return $this->hasMany(LiveStreamings::class)
      ->where('updated_at', '>', now()->subMinutes(5))
      ->whereStatus('0')
      ->orderBy('id', 'desc')
      ->first();
  }

  /**
   * User plans
   */
  public function plans()
  {
    return $this->hasMany(Plans::class);
  }

  // Get details plan
  public function getPlan($interval, $field)
  {
    return $this->plans()
      ->whereInterval($interval)
      ->pluck($field)
      ->first();
  }

  // Set interval subscriptions
  public function planInterval($interval)
  {
    switch ($interval) {
      case 'weekly':
        return now()->add(7, 'days');
        break;

      case 'monthly':
        return now()->add(1, 'month');
        break;

      case 'quarterly':
        return now()->add(3, 'months');
        break;

      case 'biannually':
        return now()->add(6, 'months');
        break;

      case 'yearly':
        return now()->add(12, 'months');
        break;
    }
  }

  // Get Plan Active
  public function planActive()
  {
    return $this->plans()->whereStatus('1')->first();
  }

  public function purchasedItems()
  {
    return $this->hasMany(Purchases::class);
  }

  public function products()
  {
    return $this->hasMany(Products::class);
  }

  public function sales()
  {
    return $this->belongsToMany(
      Purchases::class,
      Products::class,
      'user_id',
      'id',
      'id',
      'products_id'
    );
  }

  public function restrictions()
  {
    return $this->hasMany(Restrictions::class);
  }

  public function isRestricted($user)
  {
    return Restrictions::whereUserId($this->id)
      ->whereUserRestricted($user)
      ->first();
  }

  public function checkRestriction($user)
  {
    return Restrictions::whereUserId($this->id)
      ->whereUserRestricted($user)
      ->orWhere('user_id', $user)
      ->whereUserRestricted($this->id)
      ->first();
  }

  public function oneSignalDevices()
  {
    return $this->hasMany(UserDevices::class);
  }

  public function replies()
  {
    return $this->hasMany(Replies::class);
  }

  public function scopeSelectFieldsUserExplorer($query)
  {
    return $query->select(
      'id',
      'name',
      'username',
      'avatar',
      'cover',
      'free_subscription',
      'hide_name',
      'featured'
    );
  }

  public function liveStreamingPrivateRequestPending()
  {
    return $this->hasMany(LiveStreamingPrivateRequest::class, 'creator_id')->whereStatus(0)->count();
  }

  public function videoCallIncoming()
  {
    return $this->hasMany(VideoCall::class, 'buyer_id')
      ->whereNull('joined_at')
      ->latest();
  }

  public function videoCallOutgoing()
  {
    return $this->hasMany(VideoCall::class, 'seller_id')
      ->whereNull('started_at')
      ->latest();
  }

  public function audioCallIncoming()
  {
    return $this->hasMany(AudioCall::class, 'buyer_id')
      ->whereNull('joined_at')
      ->latest();
  }

  public function audioCallOutgoing()
  {
    return $this->hasMany(AudioCall::class, 'seller_id')
      ->whereNull('started_at')
      ->latest();
  }

  public function vault()
  {
    return $this->hasMany(Vault::class);
  }
}
