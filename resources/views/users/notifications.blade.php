@extends('layouts.app')

@section('title'){{__('general.notifications')}} -@endsection

@section('content')
<section class="section section-sm">
    <div class="container">
      <div class="row justify-content-center text-center mb-sm">
        <div class="col-lg-8 py-5">
          <h2 class="mb-0 font-montserrat">
            <i class="far fa-bell mr-2"></i> {{__('general.notifications')}}

            <small class="font-tiny">
              <a href="javascript:;" class="btn-notify" data-toggle="modal" data-target="#notifications"><i class="fa fa-cog mr-2"></i></a>

          @if (count($notifications) != 0)
              <form method="POST" action="{{ url('notifications/delete') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-lg align-baseline p-0 e-none btn-link actionDeleteNotify">
                    <i class="fa fa-trash-alt"></i>
                </button>
            </form>
            @endif
            </small>
          </h2>
          <p class="lead text-muted mt-0">{{__('general.notifications_subtitle')}}</p>
        </div>
      </div>
      <div class="row">

        @include('includes.cards-settings')

        <div class="col-md-6 col-lg-9 mb-5 mb-lg-0">

          @if ($notifications->total() != 0)
          <div class="btn-block mb-3 text-right">
            <span>
              <i class="bi-filter-right mr-1"></i>
              <select class="ml-2 custom-select w-auto" id="filter">
                  <option @if (! request()->get('sort')) selected @endif value="{{url('notifications')}}">{{__('general.all')}}</option>
                  <option @if (request()->get('sort') == 'subscriptions') selected @endif value="{{url('notifications?sort=subscriptions')}}">{{__('admin.subscriptions')}}</option>
                  <option @if (request()->get('sort') == 'likes') selected @endif value="{{url('notifications?sort=likes')}}">{{__('general.likes')}}</option>
                  <option @if (request()->get('sort') == 'tips') selected @endif value="{{url('notifications?sort=tips')}}">{{__('general.tips')}}</option>
                  <option @if (request()->get('sort') == 'live_streaming') selected @endif value="{{url('notifications?sort=live_streaming')}}">{{__('general.live_streaming')}}</option>
                  <option @if (request()->get('sort') == 'mentions') selected @endif value="{{url('notifications?sort=mentions')}}">{{__('general.mentions')}}</option>
                </select>
            </span>
          </div>
        @endif

        <?php

        	foreach ($notifications as $key) {

            $postUrl = $key->id ? url($key->usernameAuthor, ['post', $key->id ]) : null;
            $notyNormal = true;
            $isReel = false;

        		switch ($key->type) {
        			case 1:
        				$action          = __('users.has_subscribed');
        				$linkDestination = false;
        				break;
        			case 2:
        				$action          = __('users.like_you');
        				$linkDestination = $postUrl;
                $text_link       = Str::limit($key->description, 50, '...');
        				break;
        			case 3:
        				$action          = __('users.comment_you');
        				$linkDestination = $postUrl;
                $text_link       = Str::limit($key->description, 50, '...');
        				break;

              case 4:
        				$action          = __('general.liked_your_comment');
        				$linkDestination = $postUrl;
                $text_link       = Str::limit($key->description, 50, '...');
        				break;

              case 5:
        				$action          = __('general.he_sent_you_tip');
        				$linkDestination = url('my/payments/received');
                $text_link       = __('general.tip');
        				break;

            case 6:
              $action          = __('general.has_bought_your_message');
              $linkDestination = url('messages', $key->userId);
              $text_link       = Str::limit($key->message, 50, '...');
              break;

              case 7:
        				$action          = __('general.has_bought_your_content');
        				$linkDestination = $postUrl;
                $text_link       = Str::limit($key->description, 50, '...');
        				break;

              case 8:
        				$action          = __('general.has_approved_your_post');
        				$linkDestination = $postUrl;
                $text_link       = Str::limit($key->description, 50, '...');
                $iconNotify      = 'bi bi-check2-circle';
                $notyNormal      = false;
        				break;

              case 9:
                $action          = __('general.video_processed_successfully_post');
                $linkDestination = $postUrl;
                $text_link       = Str::limit($key->description, 50, '...');
                $iconNotify      = 'bi bi-play-circle';
                $notyNormal      = false;
                break;

              case 10:
                $action          = __('general.video_processed_successfully_message');
                $linkDestination = url('messages', $key->userDestination);
                $text_link       = Str::limit($key->message, 50, '...');
                $iconNotify       = 'bi bi-play-circle';
                $notyNormal      = false;
                break;

              case 11:
                $action          = __('general.referrals_made');
                $linkDestination = url('my/referrals');
                $text_link       = __('general.transaction');
                $iconNotify      = 'bi bi-person-plus';
                $notyNormal = false;
                break;

              case 12:
        				$action          = __('general.payment_received_subscription_renewal');
        				$linkDestination = url('my/payments/received');
                $text_link       = __('general.go_payments_received');
        				break;

              case 13:
        				$action          = __('general.has_changed_subscription_paid');
        				$linkDestination = url($key->username);
                $text_link       = __('general.subscribe_now');
        				break;

              case 14:
                $isLive          = Helper::liveStatus($key->target);
        				$action          = $isLive ? __('general.is_streaming_live') : __('general.streamed_live');
        				$linkDestination = url('live', $key->username);
                $text_link       = $isLive ? __('general.go_live_stream') : null;
        				break;

              case 15:
        				$action          = __('general.has_bought_your_item');
        				$linkDestination = url('my/sales');
                $text_link       = Str::limit($key->productName, 50, '...');
        				break;

              case 16:
        				$action          = __('general.has_mentioned_you');
        				$linkDestination = $postUrl;
                $text_link       = Str::limit($key->description, 50, '...');
        				break;

              case 17:
                $action          = __('general.story_successfully_posted');
                $linkDestination = url('/');
                $text_link       = __('general.see_story');
                $iconNotify      = 'bi-clock-history';
                $notyNormal      = false;
                break;

              case 18:
                $action          = __('general.body_account_verification_approved');
                $linkDestination = false;
                $iconNotify      = 'bi-star';
                $notyNormal      = false;
                break;

              case 19:
                $action          = __('general.body_account_verification_reject');
                $linkDestination = false;
                $iconNotify      = 'bi-exclamation-triangle';
                $notyNormal      = false;
                break;

              case 20:
                $action          = __('general.error_video_encoding_post');
                $linkDestination = false;
                $iconNotify      = 'bi-bug';
                $notyNormal      = false;
                break;

              case 21:
                $action          = __('general.error_video_encoding_message');
                $linkDestination = false;
                $iconNotify      = 'bi-bug';
                $notyNormal      = false;
                break;

              case 22:
                $action          = __('general.error_video_encoding_story');
                $linkDestination = false;
                $iconNotify      = 'bi-bug';
                $notyNormal      = false;
                break;

              case 23:
        				$action          = __('general.has_sent_private_live_stream_request');
        				$linkDestination = url('my/live/private/requests');
                $text_link       = __('general.go_received_requests');
        				break;

              case 24:
                $action          = __('general.video_processed_successfully_welcome_message');
                $linkDestination = url('settings/conversations');
                $text_link       = __('general.go_to_conversations');
                $iconNotify       = 'bi bi-play-circle';
                $notyNormal      = false;
                break;

              case 25:
                $action          = __('general.error_video_encoding_welcome_msg');
                $linkDestination = false;
                $iconNotify      = 'bi-bug';
                $notyNormal      = false;
                break;

              case 26:
        				$action          = __('general.he_sent_you_tip');
        				$linkDestination = url('my/payments/received');
                $text_link       = __('general.gift');
        				break;

              case 27:
                $action          = __('general.reel_successfully_posted');
                $linkDestination = route('reels.section.show', $key->target);
                $text_link       = __('general.go_to_reel');
                $isReel          = true;
                $notyNormal      = false;
                break;

              case 28:
                $action          = __('general.error_video_encoding_reel');
                $linkDestination = false;
                $iconNotify      = 'bi-bug';
                $notyNormal      = false;
                break;

              case 29:
        				$action          = __('general.liked_your_reel');
        				$linkDestination = route('reels.section.show', $key->target);
                $text_link       = __('general.go_to_reel');
        				break;

                case 30:
        				$action          = __('general.commented_your_reel');
        				$linkDestination = route('reels.section.show', $key->target);
                $text_link       = __('general.go_to_reel');
        				break;

                case 31:
        				$action          = __('general.has_mentioned_you_reel');
        				$linkDestination = route('reels.section.show', $key->target);
                $text_link       = __('general.go_to_reel');
        				break;

                case 32:
        				$action          = __('general.liked_your_comment_reel');
        				$linkDestination = route('reels.section.show', $key->target);
                $text_link       = __('general.go_to_reel');
        				break;

                case 33:
        				$action          = __('general.content_blocked_moderation', ['name' => '<span class="context-noty">' . $key->context . '</span>']);
                $linkDestination = false;
                $iconNotify      = 'bi-shield-x';
                $notyNormal      = false;
                break;

                case 34:
                $action          = __('general.error_moderation_file', ['name' => '<span class="context-noty">' . $key->context . '</span>']);
                $linkDestination = false;
                $iconNotify      = 'bi-shield-exclamation';
                $notyNormal      = false;
                break;

                case 35:
                $action          = __('general.video_processed_successfully_vault', ['name' => '<span class="context-noty">' . $key->context . '</span>']);
                $linkDestination = false;
                $iconNotify      = 'bi-play-circle';
                $notyNormal      = false;
                break;

                case 36:
                $action          = __('general.error_video_encoding_vault', ['name' => '<span class="context-noty">' . $key->context . '</span>']);
                $linkDestination = false;
                $iconNotify      = 'bi-bug';
                $notyNormal      = false;
                break;

                case 37:
                $action          = __('general.success_age_verification');
                $linkDestination = false;
                $iconNotify      = 'bi-person-bounding-box';
                $notyNormal      = false;
                break;

                case 38:
                $action          = __('general.failed_age_verification');
                $linkDestination = false;
                $iconNotify      = 'bi-person-bounding-box';
                $notyNormal      = false;
                break;

                case 39:
        				$action          = __('donations.has_contributed_to_your');
        				$linkDestination = $postUrl;
                $text_link       = __('donations.campaign');
        				break;

                case 40:
                $action          = __('general.failed_verification_avatar');
                $linkDestination = false;
                $iconNotify      = 'bi-person-bounding-box';
                $notyNormal      = false;
                break;

                case 41:
                $action          = __('general.failed_verification_cover');
                $linkDestination = false;
                $iconNotify      = 'bi-person-bounding-box';
                $notyNormal      = false;
                break;
        		}
        ?>

        <div class="card mb-3 card-updates">
        	<div class="card-body">
        	<div class="media">

            

            @if ($notyNormal)
              <span class="rounded-circle mr-3">
          			<a href="{{url($key->username)}}">
          				<img src="{{Helper::getFile(config('path.avatar').$key->avatar)}}" class="rounded-circle" width="60" height="60">
          				</a>
          		</span>

            @else

              <span class="rounded-circle mr-3">
                <span class="icon-notify">
                    @if ($isReel)
                    <svg xmlns="http://www.w3.org/2000/svg" class="align-top" fill="currentColor" width="60" height="60" viewBox="0 0 50 50">
                    <path d="M 15 4 C 8.9365932 4 4 8.9365932 4 15 L 4 35 C 4 41.063407 8.9365932 46 15 46 L 35 46 C 41.063407 46 46 41.063407 46 35 L 46 15 C 46 8.9365932 41.063407 4 35 4 L 15 4 z M 16.740234 6 L 27.425781 6 L 33.259766 16 L 22.574219 16 L 16.740234 6 z M 29.740234 6 L 35 6 C 39.982593 6 44 10.017407 44 15 L 44 16 L 35.574219 16 L 29.740234 6 z M 14.486328 6.1035156 L 20.259766 16 L 6 16 L 6 15 C 6 10.199833 9.7581921 6.3829803 14.486328 6.1035156 z M 6 18 L 44 18 L 44 35 C 44 39.982593 39.982593 44 35 44 L 15 44 C 10.017407 44 6 39.982593 6 35 L 6 18 z M 21.978516 23.013672 C 20.435152 23.049868 19 24.269284 19 25.957031 L 19 35.041016 C 19 37.291345 21.552344 38.713255 23.509766 37.597656 L 31.498047 33.056641 C 33.442844 31.951609 33.442844 29.044485 31.498047 27.939453 L 23.509766 23.398438 L 23.507812 23.398438 C 23.018445 23.120603 22.49297 23.001607 21.978516 23.013672 z M 21.982422 24.986328 C 22.158626 24.988232 22.342399 25.035052 22.521484 25.136719 L 30.511719 29.677734 C 31.220922 30.080703 31.220922 30.915391 30.511719 31.318359 L 22.519531 35.859375 C 21.802953 36.267773 21 35.808686 21 35.041016 L 21 25.957031 C 21 25.573196 21.201402 25.267385 21.492188 25.107422 C 21.63758 25.02744 21.806217 24.984424 21.982422 24.986328 z"></path>
                    </svg>
                    @else
                    <i class="{{ $iconNotify }}"></i>
                    @endif
                </span>
            </span>
            @endif

        		<div class="media-body">
        				<h6 class="mb-0 font-montserrat text-break text-notify">

                @if ($notyNormal)
        				<a href="{{url($key->username)}}">
        					{{$key->hide_name == 'yes' ? $key->username : $key->name}}
        				</a>
              @endif

                {!! $action !!}

                @if ($linkDestination != false)
                  <a href="{{url($linkDestination)}}">{{$text_link}}</a>
                @endif
              </h6>

        				<small class="timeAgo text-muted" data="{{date('c', strtotime($key->created_at))}}"></small>
        		</div><!-- media body -->
        	</div><!-- media -->
        </div><!-- card body -->
        </div>

    <?php } //foreach ?>

    @if ($notifications->isEmpty())
      <div class="my-5 text-center">
        <span class="btn-block mb-3">
          <i class="far fa-bell-slash ico-no-result"></i>
        </span>
      <h4 class="font-weight-light">{{__('general.no_notifications')}}</h4>
      </div>
    @endif

@if ($notifications->hasPages())
    {{ $notifications->onEachSide(0)->appends(['sort' => request('sort')])->links() }}
  @endif

    </div><!-- end col-md-6 -->

      </div>
    </div>
  </section>

  <div class="modal fade" id="notifications" tabindex="-1" role="dialog" aria-labelledby="modal-form" aria-hidden="true">
    <div class="modal-dialog modal- modal-dialog-centered modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-body p-0">
          <div class="card bg-white shadow border-0">

            <div class="card-body px-lg-5 py-lg-5">

              <div class="mb-3">
                <h6 class="position-relative">{{__('general.receive_notifications_when')}}
                  <small data-dismiss="modal" class="btn-cancel-msg"><i class="bi bi-x-lg"></i></small>
                </h6>
              </div>

              <form method="POST" action="{{ url('notifications/settings') }}" id="form">

                @csrf

                @if (auth()->user()->verified_id == 'yes')
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" name="notify_new_subscriber" value="yes" @if (auth()->user()->notify_new_subscriber == 'yes') checked @endif id="customSwitch1">
                  <label class="custom-control-label switch" for="customSwitch1">{{ __('general.someone_subscribed_content') }}</label>
                </div>

                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" name="notify_liked_post" value="yes" @if (auth()->user()->notify_liked_post == 'yes') checked @endif id="customSwitch2">
                  <label class="custom-control-label switch" for="customSwitch2">{{ __('general.someone_liked_post') }}</label>
                </div>

                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" name="notify_commented_post" value="yes" @if (auth()->user()->notify_commented_post == 'yes') checked @endif id="customSwitch3">
                  <label class="custom-control-label switch" for="customSwitch3">{{ __('general.someone_commented_post') }}</label>
                </div>

                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" name="notify_new_tip" value="yes" @if (auth()->user()->notify_new_tip == 'yes') checked @endif id="customSwitch5">
                  <label class="custom-control-label switch" for="customSwitch5">{{ __('general.someone_sent_tip') }}</label>
                </div>

                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" name="notify_new_ppv" value="yes" @if (auth()->user()->notify_new_ppv == 'yes') checked @endif id="customSwitch9">
                  <label class="custom-control-label switch" for="customSwitch9">{{ __('general.someone_bought_my_content') }}</label>
                </div>

                <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" name="notify_commented_reel" value="1" @checked(auth()->user()->notify_commented_reel) id="notify_commented_reel">
                <label class="custom-control-label switch" for="notify_commented_reel">{{ __('general.someone_commented_reel') }}</label>
              </div>

              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" name="notify_liked_reel" value="1" @checked(auth()->user()->notify_liked_reel) id="notify_liked_reel">
                <label class="custom-control-label switch" for="notify_liked_reel">{{ __('general.someone_liked_reel') }}</label>
              </div>
              @endif

              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" name="notify_liked_comment" value="yes" @if (auth()->user()->notify_liked_comment == 'yes') checked @endif id="customSwitch10">
                <label class="custom-control-label switch" for="customSwitch10">{{ __('general.someone_liked_comment') }}</label>
              </div>

              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" name="notify_live_streaming" value="yes" @if (auth()->user()->notify_live_streaming == 'yes') checked @endif id="notify_live_streaming">
                <label class="custom-control-label switch" for="notify_live_streaming">{{ __('general.someone_live_streaming') }}</label>
              </div>

              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" name="notify_mentions" value="yes" @if (auth()->user()->notify_mentions == 'yes') checked @endif id="notify_mentions">
                <label class="custom-control-label switch" for="notify_mentions">{{ __('general.someone_mentioned_me') }}</label>
              </div>

              @if ($settings->push_notification_status)
              <small class="w-100 d-block mt-2 font-weight-bold">
                <i class="bi-info-circle mr-1"></i> {{__('general.push_notification_warning')}}
              </small>
              @endif
              
                <div class="mt-3">
                  <h6 class="position-relative">{{__('general.email_notification')}}
                  </h6>
                </div>

                @if (auth()->user()->verified_id == 'yes')
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="email_new_subscriber" value="yes" @if (auth()->user()->email_new_subscriber == 'yes') checked @endif id="customSwitch4">
                    <label class="custom-control-label switch" for="customSwitch4">{{ __('general.someone_subscribed_content') }}</label>
                  </div>

                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="email_new_tip" value="yes" @if (auth()->user()->email_new_tip == 'yes') checked @endif id="customSwitch7">
                    <label class="custom-control-label switch" for="customSwitch7">{{ __('general.someone_sent_tip') }}</label>
                  </div>

                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" name="email_new_ppv" value="yes" @if (auth()->user()->email_new_ppv == 'yes') checked @endif id="customSwitch8">
                    <label class="custom-control-label switch" for="customSwitch8">{{ __('general.someone_bought_my_content') }}</label>
                  </div>
                @endif

                @if (! $settings->disable_new_post_notification)
                <div class="custom-control custom-switch">
                  <input type="checkbox" class="custom-control-input" name="notify_email_new_post" value="yes" @if (auth()->user()->notify_email_new_post == 'yes') checked @endif id="customSwitch6">
                  <label class="custom-control-label switch" for="customSwitch6">{{ __('general.new_post_creators_subscribed') }}</label>
                </div>
              @endif

              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" name="email_new_message" value="1" @checked(auth()->user()->email_new_message) id="customNewMessageEmail">
                <label class="custom-control-label switch" for="customNewMessageEmail">{{ __('general.someone_sent_message') }}</label>
              </div>

              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" name="email_upcoming_renewals" value="1" @checked(auth()->user()->email_upcoming_renewals) id="customUpcomingRenewalsEmail">
                <label class="custom-control-label switch" for="customUpcomingRenewalsEmail">{{ __('general.upcoming_renewals') }}</label>
              </div>

                <button type="submit" id="save" data-msg-success="{{ __('admin.success_update') }}" class="btn btn-primary btn-sm mt-3 w-100" data-msg="{{__('admin.save')}}">
                  {{__('admin.save')}}
                </button>

            </form>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div><!-- End Modal new Message -->
@endsection
