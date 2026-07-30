@include('includes.alert-payment-disabled')

<div class="progress-wrapper px-3 px-lg-0 display-none mb-3" id="progress">
    <div class="progress-info">
      <div class="progress-percentage">
        <span class="percent">0%</span>
      </div>
    </div>
    <div class="progress progress-xs">
      <div class="progress-bar bg-primary" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
  </div>

  <div class="w-100 mb-1 display-none pl-3" id="dateScheduleContainer">
    <small class="font-weight-bold">
     <i class="bi-calendar-event mr-1"></i> {{ __('general.date_schedule') }} <span id="dateSchedule"></span>
    </small>
    <a href="javascript:void(0)" id="deleteSchedule" class="text-danger p-1 px-2 btn-tooltip-form" data-toggle="tooltip" data-placement="top" title="{{__('general.delete')}}"><i class="fa fa-times-circle"></i></a>
    </div>

    <div class="w-100 mb-1 display-none pl-3" id="crowdfundContainer">
      <small class="font-weight-bold">
      <i class="bi-coin mr-1"></i> 
      {{ __('donations.crowdfunding_campaign') }}:

      {{ __('donations.target') }}
      {{ Helper::symbolPositionLeft() }}<span id="targetCampaign"></span>{{ Helper::symbolPositionRight() }}
      </small>
      <a href="javascript:void(0)" id="deleteCrowdfund" class="text-danger p-1 px-2 btn-tooltip-form" data-toggle="tooltip" data-placement="top" title="{{__('general.delete')}}">
        <i class="fa fa-times-circle"></i>
      </a>
    </div>

      <form method="POST" action="{{url('update/create')}}" enctype="multipart/form-data" id="formUpdateCreate">
        @csrf
      <div class="card mb-4 card-border-0 rounded-large shadow-large">
        <div class="blocked display-none"></div>
        <div class="card-body pb-0">

          <div class="media">
          <span class="rounded-circle mr-3">
      				<img src="{{ Helper::getFile(config('path.avatar').auth()->user()->avatar) }}" class="rounded-circle avatarUser" width="60" height="60">
      		</span>

          <div class="media-body position-relative">

            <textarea  class="form-control textareaAutoSize border-0 emojiArea mentions" name="description" id="updateDescription" data-post-length="{{$settings->update_length}}" rows="4" cols="40" placeholder="{{__('general.write_something')}}"></textarea>
          </div>
        </div><!-- media -->

            <input class="custom-control-input d-none" id="customCheckLocked" type="checkbox" {{auth()->user()->post_locked == 'yes' ? 'checked' : ''}} name="locked" value="yes">

          <!-- Alert -->
          <div class="alert alert-danger my-3 display-none" id="errorUdpate">
           <ul class="list-unstyled m-0" id="showErrorsUdpate"></ul>
         </div><!-- Alert -->

        </div>
        <div class="card-footer bg-white border-0 pt-0 rounded-large">

            <div class="form-group display-none" id="price" >
              <div class="input-group mb-2">
              <div class="input-group-prepend">
                <span class="input-group-text">{{$settings->currency_symbol}}</span>
              </div>
                  <input class="form-control isNumber" autocomplete="off" name="price" placeholder="{{__('general.price')}}" type="text">
              </div>
            </div><!-- End form-group -->

            <div class="form-group display-none" id="titlePost" >
              <div class="input-group mb-2">
              <div class="input-group-prepend">
                <span class="input-group-text"><i class="bi-type"></i></span>
              </div>
                  <input class="form-control" autocomplete="off" name="title" maxlength="100" placeholder="{{__('admin.title')}}" type="text">
              </div>
              <small class="form-text text-muted mb-4">
                {{ __('general.title_post_info', ['numbers' => 100]) }}
              </small>
            </div><!-- End form-group -->

          <div class="d-flex justify-content-between flex-wrap">
            <div class="w-100 mb-1">
              <small id="previewImage"></small>
              <a href="javascript:void(0)" id="removePhoto" class="text-danger p-1 small display-none btn-tooltip-form" data-toggle="tooltip" data-placement="top" title="{{__('general.delete')}}"><i class="fa fa-times-circle"></i></a>
            </div>

            <div class="w-100 mb-1">
              <small id="previewEpub"></small>
              <a href="javascript:void(0)" id="removeEpub" class="text-danger p-1 small display-none btn-tooltip-form" data-toggle="tooltip" data-placement="top" title="{{__('general.delete')}}"><i class="fa fa-times-circle"></i></a>
            </div>

            <input type="file" name="photo[]" id="filePhoto" accept="image/*,video/mp4,video/x-m4v,video/quicktime,audio/mp3" multiple class="visibility-hidden filepond">

            <button type="button" class="btn btn-post btnMultipleUpload btn-tooltip-form e-none @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill" data-toggle="tooltip" data-placement="top" title="{{__('general.upload_media')}} ({{ $settings->disable_audio ? __('general.photo_video') : __('general.media_type_upload') }})">
              <i class="feather icon-image f-size-20 align-bottom"></i>
            </button>

            @if ($settings->allow_zip_files)
            <input type="file" name="zip" id="fileZip" accept="application/x-zip-compressed" class="visibility-hidden">
            @endif
          
          @if ($settings->allow_reels)
          <button onclick="window.location.href='{{url('create/reel')}}'" type="button" data-toggle="tooltip" data-placement="top" title="{{__('general.create_reel')}}" class="btn btn-post p-bottom-8 btn-tooltip-form e-none @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" x="0px" y="0px" width="22" height="22" viewBox="0 0 50 50">
              <path d="M 15 4 C 8.9365932 4 4 8.9365932 4 15 L 4 35 C 4 41.063407 8.9365932 46 15 46 L 35 46 C 41.063407 46 46 41.063407 46 35 L 46 15 C 46 8.9365932 41.063407 4 35 4 L 15 4 z M 16.740234 6 L 27.425781 6 L 33.259766 16 L 22.574219 16 L 16.740234 6 z M 29.740234 6 L 35 6 C 39.982593 6 44 10.017407 44 15 L 44 16 L 35.574219 16 L 29.740234 6 z M 14.486328 6.1035156 L 20.259766 16 L 6 16 L 6 15 C 6 10.199833 9.7581921 6.3829803 14.486328 6.1035156 z M 6 18 L 44 18 L 44 35 C 44 39.982593 39.982593 44 35 44 L 15 44 C 10.017407 44 6 39.982593 6 35 L 6 18 z M 21.978516 23.013672 C 20.435152 23.049868 19 24.269284 19 25.957031 L 19 35.041016 C 19 37.291345 21.552344 38.713255 23.509766 37.597656 L 31.498047 33.056641 C 33.442844 31.951609 33.442844 29.044485 31.498047 27.939453 L 23.509766 23.398438 L 23.507812 23.398438 C 23.018445 23.120603 22.49297 23.001607 21.978516 23.013672 z M 21.982422 24.986328 C 22.158626 24.988232 22.342399 25.035052 22.521484 25.136719 L 30.511719 29.677734 C 31.220922 30.080703 31.220922 30.915391 30.511719 31.318359 L 22.519531 35.859375 C 21.802953 36.267773 21 35.808686 21 35.041016 L 21 25.957031 C 21 25.573196 21.201402 25.267385 21.492188 25.107422 C 21.63758 25.02744 21.806217 24.984424 21.982422 24.986328 z" stroke="currentColor" stroke-width="2" fill="none"></path>
              </svg>
          </button>
          @endif

          @if ($settings->allow_epub_files)
            <input type="file" name="epub" id="ePubFile" accept="application/epub+zip" class="visibility-hidden">
          @endif

           @if (auth()->user()->free_subscription == 'yes' && $settings->ppv_only_free_accounts || !$settings->ppv_only_free_accounts)
            <button type="button" id="setPrice" class="btn btn-post btn-tooltip-form e-none @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill" data-toggle="tooltip" data-placement="top" title="{{__('general.price_post_ppv')}}">
              <i class="feather icon-tag f-size-20 align-bottom"></i>
            </button>
            @endif

            @if (!$settings->disable_free_post)
            <button type="button" id="contentLocked" class="btn btn-post btn-tooltip-form e-none @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill {{auth()->user()->post_locked == 'yes' ? '' : 'unlock'}}" data-toggle="tooltip" data-placement="top" title="{{__('users.locked_content')}}">
              <i class="feather icon-{{auth()->user()->post_locked == 'yes' ? '' : 'un'}}lock f-size-20 align-bottom"></i>
            </button>
            @endif

            @if ($settings->live_streaming_status == 'on')
              <button type="button" data-toggle="tooltip" data-placement="top" title="{{__('general.stream_live')}}" class="btn btn-post p-bottom-8 btn-tooltip-form e-none btnCreateLive @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill">
                  <i class="bi-broadcast f-size-20 align-middle"></i>
              </button>
            @endif

            @if ($settings->allow_crowdfund)
              <button type="button" data-toggle="tooltip" data-placement="top" title="{{__('donations.create_campaign_crowdfund')}}" class="btn btn-post p-bottom-8 btn-tooltip-form e-none btnCreateCampaignCrowdfund @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill">
                  <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-dollar-sign-icon lucide-badge-dollar-sign"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
              </button>

              <input type="hidden" name="crowdfund_amount" id="inputCrowdfundAmount" value="">
            @endif

            @if ($settings->allow_scheduled_posts)
              <button type="button" data-toggle="tooltip" data-placement="top" title="{{__('general.schedule')}}" class="btn btn-post p-bottom-8 btn-tooltip-form e-none btnSchedulePost @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill">
                  <i class="bi-calendar-event f-size-20 align-middle"></i>
              </button>

              <input type="hidden" name="scheduled_date" id="inputScheduled" value="">
            @endif

            <button type="button" id="setTitle" class="btn btn-tooltip-form e-none btn-post @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill" data-toggle="tooltip" data-placement="top" title="{{__('general.title_post_block')}}">
              <i class="bi-type f-size-20 align-middle"></i>
            </button>

            <button type="button" data-toggle="dropdown" id="dropdownEmoji" aria-haspopup="true" aria-expanded="false" class="btn btn-post p-bottom-8 btn-tooltip-form e-none @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill">
                <i class="bi-emoji-smile f-size-20 align-middle"></i>
            </button>

            <div class="dropdown-menu dropdown-menu-right dropdown-emoji custom-scrollbar" aria-labelledby="dropdownEmoji">
              @include('includes.emojis')
            </div>

            @if ($settings->allow_zip_files || $settings->allow_epub_files)
            <div class="d-inline-block position-relative">
              <button type="button" data-toggle="dropdown" aria-haspopup="true" id="dropdownMoreOptions" aria-expanded="false" class="btn btn-post p-bottom-8 e-none @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill">
                  <i class="bi-three-dots f-size-20 align-middle"></i>
              </button>

              <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMoreOptions">
                @if ($settings->allow_zip_files)
                <button type="button" class="dropdown-item mb-1" onclick="$('#fileZip').trigger('click')"><i class="bi-file-earmark-zip mr-1"></i> {{__('general.upload_file_zip')}}</button>
                @endif

                @if ($settings->allow_epub_files)
                <button type="button" class="dropdown-item mb-1" onclick="$('#ePubFile').trigger('click')"><i class="bi-book mr-1"></i> {{__('general.upload_epub_file')}}</button>
                @endif
                
              </div>
            </div>
            @endif
            </div><!-- justify-content-between -->

            <div class="d-inline-block float-right mt-2 position-relative w-100-mobile">
              <span class="d-inline-block float-right position-relative rounded-pill w-100-mobile">
                <span class="btn-blocked display-none"></span>

                <button type="submit" disabled class="btn btn-sm btn-primary rounded-pill float-right e-none w-100-mobile" data-empty="{{__('general.empty_post')}}" data-error="{{__('general.error')}}" data-msg-error="{{__('general.error_internet_disconnected')}}" id="btnCreateUpdate">
                  <i></i> <span id="textPostPublish">{{__('general.publish')}}</span>
                </button>
              </span>

              <div id="the-count" class="float-right my-2 mr-2">
                <small id="maximum">{{$settings->update_length}}</small>
              </div>
          </div>

        </div><!-- card footer -->
      </div><!-- card -->
    </form>

    <!-- Post Pending -->
    <div class="alert alert-primary display-none card-border-0" role="alert" id="alertPostPending">
      <button type="button" class="close mt-1" id="btnAlertPostPending">
        <span aria-hidden="true">
          <i class="bi bi-x-lg"></i>
        </span>
      </button>

        <i class="bi-info-circle mr-1"></i> {{ __('general.alert_post_pending_review') }}
        <a href="{{ url('my/posts') }}" class="link-border text-white">{{ __('general.my_posts') }}</a>
    </div>

    <!-- Post Schedule -->
    <div class="alert alert-primary display-none card-border-0" role="alert" id="alertPostSchedule">
      <button type="button" class="close mt-1" id="btnAlertPostSchedule">
        <span aria-hidden="true">
          <i class="bi bi-x-lg"></i>
        </span>
      </button>

        <i class="bi-info-circle mr-1"></i> {{ __('general.alert_post_schedule') }}
        <a href="{{ url('my/posts') }}" class="link-border text-white">{{ __('general.my_posts') }}</a>
    </div>
