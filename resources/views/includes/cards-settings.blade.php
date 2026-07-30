<div class="col-md-6 col-lg-3 mb-3">

	<button type="button" class="btn-menu-expand btn btn-primary btn-block mb-2 d-lg-none" type="button"
		data-toggle="collapse" data-target="#navbarUserHome" aria-controls="navbarCollapse" aria-expanded="false">
		<i class="fa fa-bars mr-2"></i> {{__('general.menu')}}
	</button>

	<div class="navbar-collapse collapse d-lg-block" id="navbarUserHome">

		<!-- Start Account -->
		<div class="card shadow-sm card-settings mb-3">
			<div class="list-group list-group-sm list-group-flush">

				<small class="text-muted px-4 pt-3 text-uppercase mb-1 font-weight-bold">{{ __('general.account') }}</small>

				@if (auth()->user()->verified_id == 'yes')
				<a href="{{url('dashboard')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('dashboard')) active @endif">
					<div>
						<i class="bi bi-speedometer2 mr-2"></i>
						<span>{{__('admin.dashboard')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				<a href="{{url(auth()->user()->username)}}"
					class="list-group-item list-group-item-action d-flex justify-content-between url-user">
					<div>
						<i class="feather icon-user mr-2"></i>
						<span>{{ auth()->user()->verified_id == 'yes' ? __('general.my_page') : __('users.my_profile') }}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>

				<a href="{{url('settings/page')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('settings/page')) active @endif">
					<div>
						<i class="bi bi-pencil mr-2"></i>
						<span>{{ auth()->user()->verified_id == 'yes' ? __('general.edit_my_page') :
							__('users.edit_profile')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>

				@if (auth()->user()->verified_id == 'yes')
				<a href="{{url('settings/conversations')}}"
					@class([ 'list-group-item list-group-item-action d-flex justify-content-between' , 'active'=>
					request()->is('settings/conversations')])>
					<div>
						<i class="feather icon-send mr-2"></i>
						<span>{{__('general.conversations')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				@if ($settings->disable_wallet == 'off')
				<a href="{{url('my/wallet')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/wallet')) active @endif">
					<div>
						<i class="iconmoon icon-Wallet mr-2"></i>
						<span>{{__('general.wallet')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				@if ($settings->referral_system == 'on' || auth()->user()->referrals()->count() != 0)
				<a href="{{url('my/referrals')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/referrals')) active @endif">
					<div>
						<i class="bi-person-plus mr-2"></i>
						<span>{{__('general.referrals')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				@if ($settings->story_status && auth()->user()->verified_id == 'yes')
				<a href="{{url('my/stories')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/stories')) active @endif">
					<div>
						<i class="bi-clock-history mr-2"></i>
						<span>{{__('general.my_stories')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				<a href="{{url('settings/verify/account')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('settings/verify/account')) active @endif">
					<div>
						<i class="@if (auth()->user()->verified_id == 'yes') feather icon-check-circle @else bi-star @endif mr-2"></i>
						<span>{{ auth()->user()->verified_id == 'yes' ? __('general.verified_account') :
							__('general.become_creator')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>

				@if ($settings->video_call_status && auth()->user()->verified_id == 'yes')
				<a href="{{url('settings/video-call')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('settings/video-call')) active @endif">
					<div>
						<i class="bi-camera-video mr-2"></i>
						<span>{{__('general.video_call_settings')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif
				
				@if ($settings->audio_call_status && auth()->user()->verified_id == 'yes')
				<a href="{{url('settings/audio-call')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('settings/audio-call')) active @endif">
					<div>
						<i class="bi-telephone mr-2"></i>
						<span>{{__('general.audio_call_settings')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				@if ($settings->allow_reels && auth()->user()->verified_id == 'yes')
				<a href="{{url('my/reels')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between">
					<div>
						<svg xmlns="http://www.w3.org/2000/svg" class="mr-2"  fill="currentColor" width="16" height="16" viewBox="0 0 50 50">
                    		<path d="M 15 4 C 8.9365932 4 4 8.9365932 4 15 L 4 35 C 4 41.063407 8.9365932 46 15 46 L 35 46 C 41.063407 46 46 41.063407 46 35 L 46 15 C 46 8.9365932 41.063407 4 35 4 L 15 4 z M 16.740234 6 L 27.425781 6 L 33.259766 16 L 22.574219 16 L 16.740234 6 z M 29.740234 6 L 35 6 C 39.982593 6 44 10.017407 44 15 L 44 16 L 35.574219 16 L 29.740234 6 z M 14.486328 6.1035156 L 20.259766 16 L 6 16 L 6 15 C 6 10.199833 9.7581921 6.3829803 14.486328 6.1035156 z M 6 18 L 44 18 L 44 35 C 44 39.982593 39.982593 44 35 44 L 15 44 C 10.017407 44 6 39.982593 6 35 L 6 18 z M 21.978516 23.013672 C 20.435152 23.049868 19 24.269284 19 25.957031 L 19 35.041016 C 19 37.291345 21.552344 38.713255 23.509766 37.597656 L 31.498047 33.056641 C 33.442844 31.951609 33.442844 29.044485 31.498047 27.939453 L 23.509766 23.398438 L 23.507812 23.398438 C 23.018445 23.120603 22.49297 23.001607 21.978516 23.013672 z M 21.982422 24.986328 C 22.158626 24.988232 22.342399 25.035052 22.521484 25.136719 L 30.511719 29.677734 C 31.220922 30.080703 31.220922 30.915391 30.511719 31.318359 L 22.519531 35.859375 C 21.802953 36.267773 21 35.808686 21 35.041016 L 21 25.957031 C 21 25.573196 21.201402 25.267385 21.492188 25.107422 C 21.63758 25.02744 21.806217 24.984424 21.982422 24.986328 z"></path>
                    	</svg>
						<span>{{__('general.my_reels')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

			</div>
		</div><!-- End Account -->

		@if ($settings->live_streaming_private == 'on')
		<!-- Start Live Streaming private -->
		<div class="card shadow-sm card-settings mb-3">
			<div class="list-group list-group-sm list-group-flush">

				<small class="text-muted px-4 pt-3 text-uppercase mb-1 font-weight-bold">{{
					__('general.live_streaming_private') }}</small>

				@if (auth()->user()->verified_id == 'yes')
				<a href="{{url('my/live/private/settings')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/live/private/settings')) active @endif">
					<div>
						<i class="bi-gear mr-2"></i>
						<span>{{__('general.settings')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>

				<a href="{{url('my/live/private/requests')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/live/private/requests')) active @endif">
					<div>
						<i class="bi-box-arrow-in-down mr-2"></i>
						<span>{{__('general.requests_received')}}</span>

						<span class="badge badge-warning">{{ auth()->user()->liveStreamingPrivateRequestPending() ?: null }}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				<a href="{{url('my/live/private/requests/sended')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/live/private/requests/sended')) active @endif">
					<div>
						<i class="bi-box-arrow-in-up mr-2"></i>
						<span>{{__('general.requests_sent')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>


			</div>
		</div><!-- End Live Streaming private -->
		@endif

		<!-- Start Subscription -->
		<div class="card shadow-sm card-settings mb-3">
			<div class="list-group list-group-sm list-group-flush">

				<small class="text-muted px-4 pt-3 text-uppercase mb-1 font-weight-bold">{{ __('general.subscription')
					}}</small>

				@if (auth()->user()->verified_id == 'yes')
				<a href="{{url('settings/subscription')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('settings/subscription')) active @endif">
					<div>
						<i class="bi bi-cash-stack mr-2"></i>
						<span>{{__('general.subscription_price')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				@if (auth()->user()->verified_id == 'yes')
				<a href="{{url('my/subscribers')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/subscribers')) active @endif">
					<div>
						<i class="feather icon-users mr-2"></i>
						<span>{{__('users.my_subscribers')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				<a href="{{url('my/subscriptions')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/subscriptions')) active @endif">
					<div>
						<i class="feather icon-user-check mr-2"></i>
						<span>{{__('users.my_subscriptions')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>

			</div>
		</div><!-- End Subscription -->

		<!-- Start Privacy and security -->
		<div class="card shadow-sm card-settings mb-3">
			<div class="list-group list-group-sm list-group-flush">

				<small class="text-muted px-4 pt-3 text-uppercase mb-1 font-weight-bold">{{
					__('general.privacy_security') }}</small>

				<a href="{{url('privacy/security')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('privacy/security')) active @endif">
					<div>
						<i class="bi bi-shield-check mr-2"></i>
						<span>{{__('general.privacy_security')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>

				<a href="{{url('settings/password')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('settings/password')) active @endif">
					<div>
						<i class="iconmoon icon-Key mr-2"></i>
						<span>{{__('auth.password')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>

				@if (auth()->user()->verified_id == 'yes')
				<a href="{{url('block/countries')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('block/countries')) active @endif">
					<div>
						<i class="bi bi-eye-slash mr-2"></i>
						<span>{{__('general.block_countries')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				<a href="{{url('settings/restrictions')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('settings/restrictions')) active @endif">
					<div>
						<i class="feather icon-slash mr-2"></i>
						<span>{{__('general.restricted_users')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>

			</div>
		</div><!-- End Privacy and security -->

		<!-- Start Payments -->
		<div class="card shadow-sm card-settings mb-3">
			<div class="list-group list-group-sm list-group-flush">

				<small class="text-muted px-4 pt-3 text-uppercase mb-1 font-weight-bold">{{ __('general.payments') }}</small>

				<a href="{{url('my/payments')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/payments')) active @endif">
					<div>
						<i class="bi bi-receipt mr-2"></i>
						<span>{{__('general.payments')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>

				@if (auth()->user()->verified_id == 'yes')
				<a href="{{url('my/payments/received')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/payments/received')) active @endif">
					<div>
						<i class="bi bi-receipt mr-2"></i>
						<span>{{__('general.payments_received')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				@if ($showSectionMyCards)
				<a href="{{url('my/cards')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/cards')) active @endif">
					<div>
						<i class="feather icon-credit-card mr-2"></i>
						<span>{{__('general.my_cards')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				@if (auth()->user()->verified_id == 'yes')
				<a href="{{url('settings/payout/method')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('settings/payout/method')) active @endif">
					<div>
						<i class="bi bi-credit-card mr-2"></i>
						<span>{{__('users.payout_method')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>

				<a href="{{url('settings/withdrawals')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('settings/withdrawals')) active @endif">
					<div>
						<i class="bi bi-arrow-left-right mr-2"></i>
						<span>{{__('general.withdrawals')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

			</div>
		</div><!-- End Payments -->

		@if ($settings->shop
		|| auth()->user()->sales()->count() != 0 && auth()->user()->verified_id == 'yes'
		|| auth()->user()->sales()->count() != 0 && auth()->user()->verified_id == 'yes'
		|| auth()->user()->purchasedItems()->count() != 0)
		<!-- Start Shop -->
		<div class="card shadow-sm card-settings">
			<div class="list-group list-group-sm list-group-flush">

				<small class="text-muted px-4 pt-3 text-uppercase mb-1 font-weight-bold">{{ __('general.shop')
					}}</small>

				@if ($settings->shop && auth()->user()->verified_id == 'yes' || auth()->user()->sales()->count() != 0 &&
				auth()->user()->verified_id == 'yes')
				<a href="{{url('my/sales')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/sales')) active @endif">
					<div>
						<i class="bi-cart2 mr-2"></i>
						<span class="mr-1">{{__('general.sales')}}</span>

						<span class="badge badge-warning">{{ auth()->user()->sales()->whereDeliveryStatus('pending')->count() ?: null }}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				@if ($settings->shop && auth()->user()->verified_id == 'yes' || auth()->user()->products()->count() != 0
				&& auth()->user()->verified_id == 'yes')
				<a href="{{url('my/products')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between">
					<div>
						<i class="bi-tag mr-2"></i>
						<span>{{__('general.products')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif

				@if ($settings->shop || auth()->user()->purchasedItems()->count() != 0)
				<a href="{{url('my/purchased/items')}}"
					class="list-group-item list-group-item-action d-flex justify-content-between @if (request()->is('my/purchased/items')) active @endif">
					<div>
						<i class="bi-bag-check mr-2"></i>
						<span>{{__('general.purchased_items')}}</span>
					</div>
					<div>
						<i class="feather icon-chevron-right"></i>
					</div>
				</a>
				@endif
			</div>
		</div><!-- End Shop -->
		@endif

	</div>
</div>