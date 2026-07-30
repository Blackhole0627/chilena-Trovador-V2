<div class="modal fade" id="videoCallModalIncoming" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-call modal-dialog-centered">
    <div class="modal-content modal-content-call border-0 text-center p-4">
      <div class="modal-body">
        <div class="profile-image-call">
          <img src="{{ Helper::getFile(config('path.avatar').$settings->avatar) }}" id="avatarSeller" alt="Caller profile">
        </div>

        <div class="caller-name">{{ __('general.new_video_call') }}</div>
        <div class="calling-status"><span id="callerUsername"></span> {{ __('general.is_calling') }}</div>

        <div class="w-100 d-block h5" id="callerAmount"></div>

        <small class="w-100 d-block">
          {{ __('general.video_call_notice') }}
        </small>

        <div class="call-actions">
          <button class="btn btn-reject rounded-3" data-id id="rejectCall">
            <i class="fas fa-phone-slash mr-2"></i> {{ __('general.reject') }}
          </button>
          <button class="btn btn-accept rounded-3" data-url id="acceptCall">
            <i class="fas fa-phone mr-2"></i> {{ __('general.accept') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>