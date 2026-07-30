<div class="modal fade" id="audioCallModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-call modal-dialog-centered">
    <div class="modal-content modal-content-call border-0 text-center p-4">
      <div class="modal-body">
        <div class="profile-image-call">
          <img src="{{ Helper::getFile(config('path.avatar').$user->avatar) }}" alt="Caller profile">
        </div>

        <div class="caller-name" id="callingAudioToFan"></div>
        <div class="calling-status" id="callingAudioStatus">{{ __('general.please_wait_answer') }}</div>

        <div class="call-actions">
          <button class="btn btn-reject rounded-3" data-audiocall data-id="{{ $user->id }}" id="cancelAudioCall">
            <i class="fas fa-phone-slash mr-2"></i> {{ __('admin.cancel') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>