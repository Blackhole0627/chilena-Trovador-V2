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

// State variables
let activeIndex = null;
let isPlaying = false;
let isMuted = false;
let touchStartX = null;
let isLoading = false;
let currentPageReel = 1;
let isLoadingMore = false;
var hasMoreReels = true;

// Initialize the application
function init() {
    renderVideoGrid();
    setupEventListeners();
}

// Render video thumbnails grid
function renderVideoGrid() {
    // Clear grid only on first load
    if (currentPageReel === 1) {
        videoGrid.innerHTML = '';
    }

    videoData.forEach((video, index) => {
        const thumbnail = document.createElement('div');
        thumbnail.className = 'swiper-slide aspect-ratio-9x16 bg-light-subtle rounded-2 border';
        thumbnail.innerHTML = `
    <a href="javascript:void(0);" class="d-block item-image aspect-ratio-9x16 img-latest position-relative">
                  <span class="button-play">
                    <i class="bi bi-play-fill text-white"></i>
                  </span>

                  <img src="${video.thumbnail}" class="not-hover-img img-slider-videos height-100 img-fit-cover rounded" alt="${video.user.name}">
                </a>
                `;

        // Use the absolute index considering loaded pages
        const absoluteIndex = videoData.length - index - 1;
        thumbnail.addEventListener('click', () => openReel(absoluteIndex));
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

    // Add click event listener to videoOverlay
    videoOverlay.addEventListener('click', handleOverlayClick);

    // Touch events for swipe
    videoReel.addEventListener('touchstart', handleTouchStart, { passive: true });
    videoReel.addEventListener('touchmove', handleTouchMove, { passive: true });
    videoReel.addEventListener('touchend', handleTouchEnd, { passive: true });

    // Keyboard events
    document.addEventListener('keydown', handleKeyDown);
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

// Close video reel
function closeReel() {
    // First pause all video playback
    pauseVideo();

    // Hide loader if it's showing
    hideLoader();

    // Reset src to stop any background loading/playing
    videoPlayer.src = "";

    videoReel.classList.remove('active');
    document.body.classList.remove('no-scroll');

    activeIndex = null;
}

// Load video at specified index
function loadVideo(index) {
    const video = videoData[index];

    // Show loader before loading the new video
    showLoader();

    videoPlayer.src = video.src;
    userAvatar.src = video.user.avatar;
    userName.textContent = video.user.name;
    totalDurationEl.textContent = video.duration;

    // Reset progress
    progressBar.style.width = '0%';
    currentTimeEl.textContent = '0:00';

    updateNavigationButtons();
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
    showLoader();
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
    
    // If we're at the last video, check if we need to load more
    if (activeIndex === videoData.length - 1 && hasMoreReels) {
        nextButton.classList.remove('disabled');
    } else if (activeIndex === videoData.length - 1 && !hasMoreReels) {
        nextButton.classList.add('disabled');
    } else {
        nextButton.classList.remove('disabled');
    }
}

// Navigate to previous video
function prevVideo() {
    if (activeIndex > 0) {
        activeIndex--;
        loadVideo(activeIndex);
        playVideo();
    }
}

// Navigate to next video
function nextVideo() {
    if (activeIndex < videoData.length - 1) {
        // If we have more videos loaded, just go to the next one
        activeIndex++;
        loadVideo(activeIndex);
        playVideo();
    } else if (hasMoreReels && !isLoadingMore) {
        // If we need to load more and we're not already loading
        loadMoreReels();
    }
}

// Load more reels via AJAX
function loadMoreReels() {
    if (isLoadingMore || !hasMoreReels) return;
    
    isLoadingMore = true;
    showLoader();
    
    // Increment page for pagination
    currentPageReel++;
    
    // Make AJAX request to get more reels
    fetch(URL_BASE + `/reels/load-more?page=${currentPageReel}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
        .then(data => {
        if (data.reels && data.reels.length > 0) {
            // Add new reels to videoData
            let newReels = data.reels.map(reel => ({
                id: reel.id,
                src: reel.media.video,
                thumbnail: reel.media.video_poster ? reel.media.video_poster : reel.user.avatar,
                duration: reel.media.duration_video,
                user: {
                    name: reel.user.name,
                    avatar: reel.user.avatar,
                }
            }));
            
            // Prepend new reels to videoData (para mantener orden cronológico)
            videoData = [...newReels, ...videoData];
            
            // Actualizar activeIndex para compensar los nuevos items
            activeIndex += newReels.length;
            
            // Render new videos
            renderAdditionalVideos(newReels);
            
            // Load the next video
            loadVideo(activeIndex);
            playVideo();
            
            hasMoreReels = data.has_more;
        } else {
            hasMoreReels = false;
        }
        
        updateNavigationButtons();
        isLoadingMore = false;
        hideLoader();
    })
    .catch(error => {
        console.error('Error loading more reels:', error);
        isLoadingMore = false;
        hideLoader();
    });
}

// Render additional videos to the grid
function renderAdditionalVideos(newReels) {
    newReels.forEach((video, index) => {
        const thumbnail = document.createElement('div');
        thumbnail.className = 'swiper-slide aspect-ratio-9x16 bg-light-subtle rounded-2 border';
        thumbnail.innerHTML = `
            <a href="javascript:void(0);" class="d-block item-image aspect-ratio-9x16 img-latest position-relative">
                <span class="button-play">
                    <i class="bi bi-play-fill text-white"></i>
                </span>
                <img src="${video.thumbnail}" class="not-hover-img img-slider-videos height-100 img-fit-cover rounded" alt="${video.user.name}">
            </a>`;

        videoGrid.insertBefore(thumbnail, videoGrid.firstChild);
        
        thumbnail.addEventListener('click', () => openReel(index));
    });
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

    // Auto-advance to next video
    if (activeIndex < videoData.length - 1) {
        setTimeout(() => {
            nextVideo();
        }, 1000);
    } else if (hasMoreReels) {
        // If we're at the end and have more reels to load
        setTimeout(() => {
            loadMoreReels();
        }, 1000);
    }
}

// Handle share button
function handleShare() {
    const currentVideo = videoData[activeIndex];
    
    if (navigator.share) {
        navigator.share({
            title: `${currentVideo.user.name} - Reel`,
            text: `Check out this reel from ${currentVideo.user.name}`,
            url: `${window.location.origin}/reel/${currentVideo.id}`,
        }).catch(error => {
            console.error('Error sharing:', error);
        });
    }
}

// Touch event handlers for swipe
function handleTouchStart(e) {
    touchStartX = e.touches[0].clientX;
}

function handleTouchMove(e) {
    if (touchStartX === null) return;

    const touchEndX = e.touches[0].clientX;
    const diff = touchStartX - touchEndX;

    // Threshold for swipe detection (50px)
    if (Math.abs(diff) > 50) {
        if (diff > 0 && (activeIndex < videoData.length - 1 || hasMoreReels)) {
            // Swipe left - next video
            nextVideo();
            touchStartX = null;
        } else if (diff < 0 && activeIndex > 0) {
            // Swipe right - previous video
            prevVideo();
            touchStartX = null;
        }
    }
}

function handleTouchEnd() {
    touchStartX = null;
}

// Keyboard event handler
function handleKeyDown(e) {
    if (activeIndex === null) return;

    if (e.key === 'ArrowLeft' && activeIndex > 0) {
        prevVideo();
    } else if (e.key === 'ArrowRight' && (activeIndex < videoData.length - 1 || hasMoreReels)) {
        nextVideo();
    } else if (e.key === ' ' || e.key === 'Spacebar') {
        togglePlay();
        e.preventDefault();
    } else if (e.key === 'Escape') {
        closeReel();
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', init);