// DOM Elements
const videoGrid = document.getElementById('videoGrid');
const videoReel = document.getElementById('videoReel');
const videoPlayer = document.getElementById('videoPlayer');
const videoOverlay = document.getElementById('videoOverlay');
const videoLoader = document.getElementById('videoLoader');
const closeButton = document.getElementById('closeButton');
const prevButton = document.getElementById('prevButton');
const nextButton = document.getElementById('nextButton');
const playPauseButton = document.getElementById('playPauseButton');
const playIcon = document.getElementById('playIcon');
const pauseIcon = document.getElementById('pauseIcon');
const muteButton = document.getElementById('muteButton');
const volumeIcon = document.getElementById('volumeIcon');
const muteIcon = document.getElementById('muteIcon');
const progressContainer = document.getElementById('progressContainer');
const progressBar = document.getElementById('progressBar');
const currentTimeEl = document.getElementById('currentTime');
const totalDurationEl = document.getElementById('totalDuration');
const userAvatar = document.getElementById('userAvatar');
const userName = document.getElementById('userName');
const shareButton = document.getElementById('shareButton');
const iconLikeReel = document.getElementById('iconLikeReel');
const likeReel = document.getElementById('likeReel');
const textReel = document.getElementById('textReel');
const counterLikes = document.getElementById('counterLikes');
const counterComments = document.getElementById('counterComments');
const CommentReel = document.getElementById('CommentReel');
const iconTypeReel = document.getElementById('iconTypeReel');
const reportReel = document.getElementById('reportReel');
const reportBtnReel = document.getElementById('reportBtnReel');
const deleteReel = document.getElementById('deleteReel');
const sendTip = document.getElementById('sendTip');

// State variables
let playedVideos = new Set(JSON.parse(localStorage.getItem('playedReels') || '[]'));
let activeIndex = null;
let isPlaying = false;
let isMuted = false;
let touchStartY = null;
let isLoading = false;
let commentsLoaded = false;

// Initialize the application
function init() {
    renderVideoGrid();
    setupEventListeners();

    if (isReelPage) {
        setTimeout(() => {
            openReelMuted(0);
        }, 500);

        initReelNavigation(videoData);
    }

    setupCommentContainerEvents();
}

function openReelMuted(index) {
    activeIndex = index;
    loadVideo(activeIndex);

    videoReel.classList.add('active');
    document.body.classList.add('no-scroll');

    updateNavigationButtons();

    // Muted and prepare the video
    videoPlayer.muted = true;
    videoPlayer.playsInline = true;
    videoPlayer.setAttribute('webkit-playsinline', 'true');
    isMuted = true;

    // Display tap-to-play overlay
    videoOverlay.classList.add('visible');
    playIcon.classList.remove('hidden');
    pauseIcon.classList.add('hidden');

    // Update mute UI
    if (volumeIcon && muteIcon) {
        volumeIcon.classList.add('hidden');
        muteIcon.classList.remove('hidden');
    }

    // Don't autoplay, wait for interaction
    pauseVideo();

    hideLoader();
}


// Render video thumbnails grid.
function renderVideoGrid() {
    videoGrid.innerHTML = '';

    videoData.forEach((video, index) => {
        const thumbnail = document.createElement('div');
        thumbnail.className = 'video-thumbnail';
        const canSeeUser = video.canSeeUser;
        const svg = canSeeUser ? `<polygon points="5 3 19 12 5 21 5 3"></polygon>` : `<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>`;
        const svgLock = canSeeUser ? '' : `show`;

        thumbnail.innerHTML = `
      <img src="${video.thumbnail}" class="${canSeeUser ? '' : 'img-blurred'}" alt="Video thumbnail">
      <div class="thumbnail-overlay ${svgLock}">
        <div class="thumbnail-play">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            ${svg}
          </svg>
        </div>
      </div>
      <div class="duration-badge">${video.duration}</div>
      <div class="thumbnail-user"><i class="bi-eye mr-1"></i> ${video.views}</div>
    `;

        if (canSeeUser) {
            thumbnail.addEventListener('click', () => openReel(index));
        } else {
            thumbnail.addEventListener('click', () => openModalSubscribe());
        }

        videoGrid.appendChild(thumbnail);
    });
}

// Setup event listeners
function setupEventListeners() {
    // Close button
    closeButton.addEventListener('click', closeReel);

    // Navigation buttons
    prevButton.addEventListener('click', prevVideo);
    nextButton.addEventListener('click', nextVideo);

    // Like reel
    likeReel.addEventListener('click', handleLikeReel);

    // Comment reel
    CommentReel.addEventListener('click', handleCommentLoadReel);

    // Video player events
    videoPlayer.addEventListener('click', togglePlay);
    videoPlayer.addEventListener('timeupdate', updateProgress);
    videoPlayer.addEventListener('ended', handleVideoEnd);
    videoPlayer.addEventListener('loadstart', handleLoadStart);
    videoPlayer.addEventListener('canplay', handleCanPlay);
    videoPlayer.addEventListener('waiting', handleWaiting);
    videoPlayer.addEventListener('error', handleVideoError);

    // Control buttons
    playPauseButton.addEventListener('click', togglePlay);
    muteButton.addEventListener('click', toggleMute);
    progressContainer.addEventListener('click', handleProgressClick);
    shareButton.addEventListener('click', handleShare);
    reportReel.addEventListener('click', handleReport);
    deleteReel.addEventListener('click', handleDelete);

    // Add click event listener to videoOverlay
    videoOverlay.addEventListener('click', handleOverlayClick);

    // Touch events for swipe
    videoReel.addEventListener('touchstart', handleTouchStart, { passive: true });
    videoReel.addEventListener('touchmove', handleTouchMove, { passive: true });
    videoReel.addEventListener('touchend', handleTouchEnd, { passive: true });

    // Keyboard events
    document.addEventListener('keydown', handleKeyDown);

    document.querySelector('.video-reel').addEventListener('click', function (e) {
        if (!e.target.closest('.video-container') &&
            !e.target.closest('.container-arrows') &&
            !e.target.closest('.comment-container') &&
            !isReelPage) {
            closeReel();
        }
    });
}

// Handler for videoOverlay click
function handleOverlayClick() {
    if (videoOverlay.classList.contains('visible')) {
        playVideo();
    }
}

// Open video reel
function openReel(index) {
    activeIndex = index;
    loadVideo(activeIndex);

    videoReel.classList.add('active');
    document.body.classList.add('no-scroll');

    updateNavigationButtons();

    // Auto play video
    playVideo();
}

function openModalSubscribe() {
    let modal = new bootstrap.Modal(document.querySelector('.modal-subscribe'));
    modal.show();
}

// Close video reel
function closeReel() {

    if (isReelPage) {
        window.location.href = urlPreviousPage;
    } else {
        // First pause all video playback
        pauseVideo();

        // Hide loader if it's showing
        hideLoader();

        // Reset src to stop any background loading/playing
        videoPlayer.src = "";

        videoReel.classList.remove('active');
        document.body.classList.remove('no-scroll');

        document.querySelector('.comment-container').classList.remove('show');

        activeIndex = null;
    }
}

// Load video at specified index
function loadVideo(index) {
    const video = videoData[index];

    commentsLoaded = false;

    // Show loader before loading the new video
    showLoader();

    if (sessionActive && video.user.id != userAuthId) {
        sendTip.classList.remove('d-none');
    } else if (sessionActive && video.user.id == userAuthId) {
        sendTip.classList.add('d-none');
    } else if (!sessionActive) {
        sendTip.classList.remove('d-none');
    }

    $('#tipForm').modal('hide');

    sendTip.dataset.id = video.id;
    sendTip.dataset.cover = video.user.cover;
    sendTip.dataset.avatar = video.user.avatar;
    sendTip.dataset.name = video.user.name;
    sendTip.dataset.userid = video.user.id;

    videoPlayer.src = video.src;
    videoPlayer.poster = video.thumbnail;
    userAvatar.src = video.user.avatar;
    userName.textContent = video.user.name;
    totalDurationEl.textContent = video.duration;

    // Reset progress
    progressBar.style.width = '0%';
    currentTimeEl.textContent = '0:00';

    if (sessionActive && video.user.id == userAuthId) {
        reportBtnReel.classList.add('d-none');
    } else if (sessionActive && video.user.id != userAuthId) {
        reportBtnReel.classList.remove('d-none');
    }

    if (sessionActive && video.user.id != userAuthId) {
        deleteReel.classList.add('d-none');
    } else if (sessionActive && video.user.id == userAuthId) {
        deleteReel.classList.remove('d-none');
    } else if (!sessionActive) {
        reportBtnReel.classList.add('d-none');
        deleteReel.classList.add('d-none');
    }

    // Like reel
    if (video.isLikedUser) {
        iconLikeReel.classList.remove('bi-heart'), iconLikeReel.classList.add('bi-heart-fill', 'text-danger');
    } else {
        iconLikeReel.classList.remove('bi-heart-fill', 'text-danger'), iconLikeReel.classList.add('bi-heart');
    }

    counterLikes.textContent = video.likes;
    counterComments.textContent = video.comments_count;

    if (video.reelIsPublic) {
        iconTypeReel.classList.remove('feather', 'icon-lock', 'ml-1', 'type-reel');
        iconTypeReel.classList.add('iconmoon', 'icon-WorldWide', 'ml-1', 'type-reel');
    } else {
        iconTypeReel.classList.remove('iconmoon', 'icon-WorldWide', 'ml-1', 'type-reel');
        iconTypeReel.classList.add('feather', 'icon-lock', 'ml-1', 'type-reel');
    }

    // Text reel
    textReel.textContent = video.title ? video.title : '';

    updateNavigationButtons();

    incrementReelViews();
}

function openProfile() {
    const currentVideo = videoData[activeIndex];
    const username = currentVideo.user.username;

    window.open(`${URL_BASE}/${username}/reels`, '_blank');
}

// Show loader
function showLoader() {
    isLoading = true;
    if (videoLoader) {
        videoLoader.classList.add('active');
    }
}

// Hide loader
function hideLoader() {
    isLoading = false;
    if (videoLoader) {
        videoLoader.classList.remove('active');
    }
}

// Handle video load start
function handleLoadStart() {
    if (!isReelPage) {
        showLoader();
    }
}

// Handle video can play
function handleCanPlay() {
    videoPlayer.addEventListener('playing', () => {
        hideLoader();
    }, { once: true });

    playVideo();
}

// Handle video waiting/buffering
function handleWaiting() {
    showLoader();
}

// Handle video error
function handleVideoError() {
    hideLoader();
}

// Update navigation buttons state
function updateNavigationButtons() {
    prevButton.classList.toggle('disabled', activeIndex === 0);
    nextButton.classList.toggle('disabled', activeIndex === videoData.length - 1);
}

// Navigate to previous video
function prevVideo() {
    if (activeIndex > 0) {
        activeIndex--;
        loadVideo(activeIndex);
        playVideo();

        handleCommentLoadReel();

        if (isReelPage) {
            const currentVideo = videoData[activeIndex];

            updateURL(currentVideo.id);
        }

    }
}

// Navigate to next video
function nextVideo() {
    if (activeIndex < videoData.length - 1) {
        activeIndex++;
        loadVideo(activeIndex);
        playVideo();

        handleCommentLoadReel();

        if (isReelPage) {
            const currentVideo = videoData[activeIndex];

            updateURL(currentVideo.id);
        }
    }
}

function updateURL(reelId) {
    if (!reelId) return;

    try {
        const url = new URL(window.location);
        const pathSegments = url.pathname.split('/').filter(segment => segment !== '');

        const reelIndex = pathSegments.indexOf('reel');
        const reelsIndex = pathSegments.indexOf('reels');

        let targetIndex = -1;

        if (reelIndex !== -1) {
            targetIndex = reelIndex;
            if (targetIndex + 1 < pathSegments.length) {
                pathSegments[targetIndex + 1] = reelId;
            } else {
                pathSegments.push(reelId);
            }
        } else if (reelsIndex !== -1) {
            targetIndex = reelsIndex;
            pathSegments[targetIndex] = 'reel';
            if (targetIndex + 1 < pathSegments.length) {
                pathSegments[targetIndex + 1] = reelId;
            } else {
                pathSegments.push(reelId);
            }
        } else {
            pathSegments.push('reel', reelId);
        }

        url.pathname = '/' + pathSegments.join('/');

        window.history.pushState(
            { reelId: reelId, index: activeIndex },
            "",
            url.pathname + url.search + url.hash
        );

    } catch (error) {
        console.error('Error updating URL:', error);
        window.history.pushState(
            { reelId: reelId, index: activeIndex },
            "",
            "reel/" + reelId
        );
    }
}

window.addEventListener('popstate', function (event) {
    if (event.state && typeof event.state.index === 'number') {
        activeIndex = event.state.index;
        loadVideo(activeIndex);
        playVideo();
        handleCommentLoadReel();
        return;
    }

    const urlParts = window.location.pathname.split('/');
    const reelId = urlParts[urlParts.length - 1];

    if (reelId) {
        const newIndex = videoData.findIndex(video => String(video.id) === String(reelId));

        if (newIndex !== -1) {
            activeIndex = newIndex;
            loadVideo(activeIndex);
            playVideo();
            handleCommentLoadReel();
        }
    }
});

function initReelNavigation(videos, startIndex = 0) {
    if (Array.isArray(videos) && videos.length > 0) {
        videoData = videos;

        activeIndex = Math.min(Math.max(0, startIndex), videos.length - 1);

        const currentVideo = videoData[activeIndex];
        loadVideo(activeIndex);
        playVideo();
        handleCommentLoadReel();

        updateURL(currentVideo.id);
    } else {
        console.error('No se proporcionaron datos de videos válidos');
    }
}

// Toggle play/pause
function togglePlay() {
    if (isPlaying) {
        pauseVideo();
    } else {
        playVideo();
    }
}

// Play video
function playVideo() {
    videoPlayer.play().catch(error => {
        console.error('Error playing video:', error);
    });

    isPlaying = true;
    playIcon.classList.add('hidden');
    pauseIcon.classList.remove('hidden');
    videoOverlay.classList.remove('visible');
}

// Pause video
function pauseVideo() {
    videoPlayer.pause();

    isPlaying = false;
    playIcon.classList.remove('hidden');
    pauseIcon.classList.add('hidden');
    videoOverlay.classList.add('visible');
}

// Toggle mute/unmute
function toggleMute() {
    isMuted = !isMuted;
    videoPlayer.muted = isMuted;

    if (isMuted) {
        volumeIcon.classList.add('hidden');
        muteIcon.classList.remove('hidden');
    } else {
        volumeIcon.classList.remove('hidden');
        muteIcon.classList.add('hidden');
    }
}

// Update progress bar and time display
function updateProgress() {
    if (videoPlayer.duration) {
        const currentProgress = (videoPlayer.currentTime / videoPlayer.duration) * 100;
        progressBar.style.width = `${currentProgress}%`;

        // Format current time
        const minutes = Math.floor(videoPlayer.currentTime / 60);
        const seconds = Math.floor(videoPlayer.currentTime % 60);
        currentTimeEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
}

// Handle progress bar click
function handleProgressClick(e) {
    const rect = progressContainer.getBoundingClientRect();
    const clickPosition = e.clientX - rect.left;
    const percentage = (clickPosition / rect.width) * 100;

    // Update video time based on click position
    videoPlayer.currentTime = (percentage / 100) * videoPlayer.duration;
}

// Handle video end
function handleVideoEnd() {
    isPlaying = false;
    playIcon.classList.remove('hidden');
    pauseIcon.classList.add('hidden');
    videoOverlay.classList.add('visible');
}

// Handle share button
function handleShare() {
    const currentVideo = videoData[activeIndex];
    const reelUrl = `${URL_BASE}/reel/${currentVideo.id}`;

    if (navigator.share) {
        navigator.share({
            title: titleShare,
            text: titleShare,
            url: reelUrl,
        }).catch(error => {
            console.error('Error sharing:', error);
        });
    }
}

function handleReport() {
    const currentVideo = videoData[activeIndex];

    var reelId = currentVideo.id;
    var reason = $('#reportReason').val();

    if (!sessionActive) {
        return;
    }
    element = $(this);
    element.blur();
    element.attr({ 'disabled': 'true' });
    element.find('i').addClass('spinner-border spinner-border-sm align-middle mr-1');

    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        type: "POST",
        url: URL_BASE + "/report/reel",
        dataType: 'json',
        data: {
            id: reelId,
            reason: reason
        },
        success: function (response) {

            if (response.success) {
                swal({
                    title: thanks,
                    text: response.text,
                    type: "success",
                    confirmButtonText: ok
                });

                $('.modalReport').modal('hide');
                element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
                element.removeAttr('disabled');
                $('#reportReason').prop('selectedIndex', 0);
            } else {
                $('.modalReport').modal('hide');

                let errors = '';
                let $key = '';

                for ($key in response.errors) {
                    errors += response.errors[$key];
                }

                swal({
                    title: error_oops,
                    text: errors,
                    type: "error",
                    confirmButtonText: ok
                });
                element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
                element.removeAttr('disabled');
            }
        }
    }).fail(function (jqXHR, ajaxOptions, thrownError) {
        $('#preloaderReel').hide();

        if (jqXHR.status === 419) {
            window.location.reload();
        }

        swal({
            title: error_oops,
            text: error_occurred,
            type: "error",
            confirmButtonText: ok
        });
    });
}

function handleDelete() {
    const currentVideo = videoData[activeIndex];
    var reelId = currentVideo.id;

    if (!sessionActive) {
        return;
    }

    swal({
        title: delete_confirm,
        type: "warning",
        showLoaderOnConfirm: true,
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: yes_confirm,
        cancelButtonText: cancel_confirm,
        closeOnConfirm: false,
    },
        function (isConfirm) {
            if (isConfirm) {
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: "POST",
                    url: URL_BASE + "/reel/delete/" + reelId,
                    dataType: 'json',
                    success: function (response) {

                        if (response.success) {
                            window.location.href = URL_BASE + '/' + currentVideo.user.username + '/reels';
                        } else {
                            swal({
                                title: error_oops,
                                text: error_occurred,
                                type: "error",
                                confirmButtonText: ok
                            });
                        }
                    }
                }).fail(function (jqXHR, ajaxOptions, thrownError) {
                    $('#preloaderReel').hide();

                    if (jqXHR.status === 419) {
                        window.location.reload();
                    }

                    swal({
                        title: error_oops,
                        text: error_occurred,
                        type: "error",
                        confirmButtonText: ok
                    });
                });
            }
        });
}

// Add this code to set up events in the comments container
function setupCommentContainerEvents() {
    const commentContainer = document.querySelector('.comment-container');

    if (commentContainer) {
        // Stop touch event propagation when they occur inside the comments container
        commentContainer.addEventListener('touchstart', function (e) {
            e.stopPropagation();
        }, { passive: false });

        commentContainer.addEventListener('touchmove', function (e) {
            e.stopPropagation();
        }, { passive: false });

        commentContainer.addEventListener('touchend', function (e) {
            e.stopPropagation();
        }, { passive: false });
    }
}

// Modify touch control functions to check if the event occurs in the comments container
function handleTouchStart(e) {
    // Don't start sliding if we're inside the comments container
    if (isInsideCommentContainer(e.target)) return;
    touchStartY = e.touches[0].clientY;
}

function handleTouchMove(e) {
    // Don't process sliding if we're inside the comments container
    if (isInsideCommentContainer(e.target)) return;
    if (touchStartY === null) return;

    const touchEndY = e.touches[0].clientY;
    const diff = touchStartY - touchEndY;

    // Threshold for swipe detection (50px)
    if (Math.abs(diff) > 50) {
        if (diff > 0 && activeIndex < videoData.length - 1) {
            // Swipe up - next video
            nextVideo();
            touchStartY = null;
        } else if (diff < 0 && activeIndex > 0) {
            // Swipe down - previous video
            prevVideo();
            touchStartY = null;
        }
    }
}

function handleTouchEnd(e) {
    // Don't finish sliding if we're inside the comments container
    if (isInsideCommentContainer(e.target)) return;
    touchStartY = null;
}

// Helper function to check if an element is inside the comments container
function isInsideCommentContainer(element) {
    let current = element;
    while (current) {
        if (current.classList &&
            (current.classList.contains('comment-container') ||
                current.classList.contains('comment-container show'))) {
            return true;
        }
        current = current.parentElement;
    }
    return false;
}

document.addEventListener('click', function (e) {
    if (e.target.matches('.toggleCommentsReel')) {
        document.querySelector('.comment-container').classList.toggle('show');
    }
});

// Keyboard event handler
function handleKeyDown(e) {
    if (activeIndex === null) return;

    if (e.key === 'ArrowUp' && activeIndex > 0) {
        prevVideo();
    } else if (e.key === 'ArrowDown' && activeIndex < videoData.length - 1) {
        nextVideo();
    } else if (e.key === 'Escape' && !isReelPage) {
        closeReel();
    }
}

function incrementReelViews() {
    const currentVideo = videoData[activeIndex];
    const MAX_REELS = 1000;

    if (!sessionActive) {
        return;
    }

    if (!currentVideo || playedVideos.has(currentVideo.id.toString()) || currentVideo.user.id == userAuthId) {
        return;
    }

    fetch(`${URL_BASE}/reels/view/${currentVideo.id}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            } else {
                playedVideos.add(currentVideo.id.toString());

                if (playedVideos.size > MAX_REELS) {
                    // Convert to array and cut
                    const trimmed = [...playedVideos].slice(-MAX_REELS);
                    playedVideos = new Set(trimmed);
                }

                localStorage.setItem('playedReels', JSON.stringify([...playedVideos]));
            }
        })
        .catch(error => {
            console.error('Error incrementing views:', error);
        });
}

function updateVideoById(id, newData) {
    const video = videoData.find(video => video.id === id);
    if (video) {
        Object.assign(video, newData);
    }
    return video;
}

function handleLikeReel() {

    if (!sessionActive) {
        return;
    }

    let currentVideo = videoData[activeIndex];
    let reelId = currentVideo.id;
    let isLiked = iconLikeReel.classList.contains('bi-heart-fill') && iconLikeReel.classList.contains('text-danger');
    let currentCount = parseInt(counterLikes.textContent);

    if (isLiked) {
        iconLikeReel.classList.remove('bi-heart-fill', 'text-danger');
        iconLikeReel.classList.add('bi-heart');
        counterLikes.textContent = currentCount - 1;
        updateVideoById(reelId, { likes: currentCount - 1 });
        updateVideoById(reelId, { isLikedUser: false });

    } else {
        iconLikeReel.classList.remove('bi-heart');
        iconLikeReel.classList.add('bi-heart-fill', 'text-danger');
        counterLikes.textContent = currentCount + 1;
        updateVideoById(reelId, { likes: currentCount + 1 });
        updateVideoById(reelId, { isLikedUser: true });
    }

    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        type: "POST",
        url: URL_BASE + "/reel/like",
        dataType: 'json',
        data: {
            id: reelId
        },
        error: function () {
            counterLikes.textContent = currentCount;

            if (isLiked) {
                iconLikeReel.classList.remove('bi-heart');
                iconLikeReel.classList.add('bi-heart-fill', 'text-danger');
                updateVideoById(reelId, { isLikedUser: true });
            } else {
                iconLikeReel.classList.remove('bi-heart-fill', 'text-danger');
                iconLikeReel.classList.add('bi-heart');
                updateVideoById(reelId, { isLikedUser: false });
            }
        }
    });
}

function handleCommentLoadReel() {

    if (!sessionActive) {
        return;
    }

    if (!commentsLoaded) {
        commentsLoaded = true;
        let currentVideo = videoData[activeIndex];
        let reelId = currentVideo.id;

        $('#wrapContainerCommentsReel').html('');

        $('#preloaderReel').show();

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            url: URL_BASE + "/reel/comments/load",
            dataType: 'json',
            data: {
                id: reelId
            },
            success: function (result) {
                if (result.comments) {
                    $('#wrapContainerCommentsReel').html(result.comments);
                    $('#preloaderReel').hide();
                    jQuery(".timeAgo").timeago();
                } else {
                    $('#preloaderReel').hide();
                }
            }
        }).fail(function (jqXHR, ajaxOptions, thrownError) {
            $('#preloaderReel').hide();

            if (jqXHR.status === 419) {
                window.location.reload();
            }

            swal({
                title: error_oops,
                text: error_occurred,
                type: "error",
                confirmButtonText: ok
            });
        });
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', init);