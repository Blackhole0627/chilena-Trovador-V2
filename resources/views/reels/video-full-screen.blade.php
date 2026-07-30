<!-- Full-screen video reel (hidden by default) -->
<div class="video-reel" id="videoReel">
    <!-- Close button -->
    <button class="close-button" id="closeButton" aria-label="Close video">✕</button>

    <div class="container-arrows">
          <!-- Navigation arrows -->
        <button class="nav-button prev-button" id="prevButton" aria-label="Previous video">
          <i class="bi bi-chevron-up"></i>
        </button>
        
        <button class="nav-button next-button" id="nextButton" aria-label="Next video">
          <i class="bi bi-chevron-down"></i>
        </button>
      </div>
    
    <!-- Video container -->
    <div class="video-container">
      @include('reels.comments-reel')
      
      <video id="videoPlayer" @if (request()->routeIs('reels.section.show')) muted preload="metadata" webkit-playsinline @endif loop playsinline oncontextmenu="return false;"></video>
      
      <!-- Video overlay - shows when paused -->
      <div class="video-overlay" id="videoOverlay">
        <div class="play-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="5 3 19 12 5 21 5 3"></polygon>
          </svg>
        </div>
      </div>

      <!-- Video Loader -->
      <div id="videoLoader"></div>

      <!-- Overlay -->
      <div class="user-overlay"></div>
      
      <!-- User info -->
      <div class="user-info" id="userInfo">
        <div class="user-avatar">
          <img class="user-avatar-link" id="userAvatar" src="" alt="" onclick="openProfile()">
        </div>
        <span class="user-link" id="userName" onclick="openProfile()"></span>
        <i id="iconTypeReel"></i>
      </div>

      <p class="text-reel" id="textReel"></p>
      
      <!-- Action buttons -->
      <div class="action-buttons">

        <div class="d-block text-center">
        <button class="action-button" @guest data-toggle="modal" data-target="#loginFormModal" @endguest id="likeReel" aria-label="Like">
          <i class="bi-heart" id="iconLikeReel"></i>
        </button>
        <span id="counterLikes">0</span>
        </div>

        <div class="d-block text-center">
          <button @class(['action-button', 'toggleCommentsReel' => auth()->check()]) id="CommentReel" @guest data-toggle="modal" data-target="#loginFormModal" @endguest aria-label="Comment">
          <i class="bi-chat"></i>
          </button>
          <span id="counterComments">0</span>
        </div>
        

        @if ($settings->disable_tips == 'off')
        <button class="action-button d-none" id="sendTip" @guest data-toggle="modal" data-target="#loginFormModal" @else data-toggle="modal" data-target="#tipForm" aria-label="Tip" @endguest>
            <i class="bi-coin"></i>
          </button>
          <i></i>
          @else
          <i class="d-none position-absolute" id="sendTip"></i>
        @endif

        <div class="btn-group dropup">
          <button class="action-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="bi-three-dots"></i>
          </button>

          <div class="dropdown-menu dropdown-emoji dropdown-menu-right custom-scrollbar" aria-labelledby="dropdownMenuButton" style="width: auto;">
            <a class="dropdown-item mb-1" id="shareButton" href="javascript:void(0);"><i class="bi-share mr-2"></i> {{ __('general.share') }}</a>
            <a class="dropdown-item mb-1" href="javascript:void(0);" data-toggle="modal" data-target="#reportModalReel" id="reportBtnReel" ><i class="bi-flag mr-2"></i> {{ __('admin.report') }}</a>

            <a class="dropdown-item" href="javascript:void(0);" id="deleteReel"><i class="feather icon-trash-2 mr-2"></i> {{ __('general.delete') }}</a>
          </div>
        </div>
      </div>
      
      <!-- Video controls -->
      <div class="video-controls">
        <button class="control-button" id="playPauseButton" aria-label="Play">
          <svg id="playIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="5 3 19 12 5 21 5 3"></polygon>
          </svg>
          <svg id="pauseIcon" class="hidden" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="6" y="4" width="4" height="16"></rect>
            <rect x="14" y="4" width="4" height="16"></rect>
          </svg>
        </button>
        
        <!-- Progress bar -->
        <div class="progress-container-reels" id="progressContainer">
          <div class="progress-bar-reels" id="progressBar"></div>
        </div>
        
        <div class="time-display">
          <span id="currentTime">0:00</span>
          <span>/</span>
          <span id="totalDuration">0:00</span>
        </div>
        
        <button class="control-button" id="muteButton" aria-label="Mute">
          <svg id="volumeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M15.54 8.46a5 5 0 0 1 0 7.07"></path>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14"></path>
          </svg>
          <svg id="muteIcon" class="hidden" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <line x1="23" y1="9" x2="17" y2="15"></line>
            <line x1="17" y1="9" x2="23" y2="15"></line>
          </svg>
        </button>
      </div>
    </div>
  </div><!-- End Video reel -->

  @include('includes.modal-report-reel')