<div class="modal fade" id="videoCallModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-call modal-dialog-centered">
    <div class="modal-content modal-content-call border-0 text-center p-4">
      <div class="modal-body">
        <div class="profile-image-call">
          <img src="{{ Helper::getFile(config('path.avatar').$user->avatar) }}" alt="Caller profile">
        </div>

        <div class="caller-name" id="callingToFan"></div>
        <div class="calling-status" id="callingStatus">{{ __('general.please_wait_answer') }}</div>

        <div class="call-actions">
          <button class="btn btn-reject rounded-3" data-videocall data-id="{{ $user->id }}" id="cancelCall">
            <i class="fas fa-phone-slash mr-2"></i> {{ __('admin.cancel') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>