<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $settings->title }}</title>
    <link href="{{ url('public/css/core.min.css') }}?v={{config('settings.version')}}" rel="stylesheet">
    <link href="{{ url('public/bootstrap/css/bootstrap.min.css') }}?v={{config('settings.version')}}" rel="stylesheet">
    <link href="{{ url('public/img', $settings->favicon) }}" rel="icon">
    <script src="https://js.pusher.com/8.3.0/pusher.min.js"></script>
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #0a3b8c;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Desktop Video Container */
        .video-container {
            background-color: #000;
            border-radius: 25px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 1200px;
            aspect-ratio: 4 / 3;
            margin: 0 auto;
        }

        .timer-badge {
            position: absolute;
            top: 20px;
            left: 30px;
            background-color: rgb(105 105 105 / 50%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 10;
        }

        .controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            z-index: 10;
        }

        .control-btn {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #8a8a8a;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 24px;
        }

        .control-btn.end-call {
            background-color: #ff3b30;
        }

        .control-btn:hover {
            opacity: 0.9;
        }

        .control-btn.muted {
            background-color: #ff3b30;
        }

        /* Mobile Video Container */
        .mobile-container {
            display: none;
            position: relative;
            width: 100%;
            height: 100vh;
            margin: 0 auto;
            overflow: hidden;
            background-color: #000;
            background-position: center;
        }

        .mobile-timer-badge {
            position: absolute;
            top: 70px;
            left: 30px;
            background-color: rgb(105 105 105 / 50%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            z-index: 10;
        }

        .mobile-controls {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            z-index: 10;
        }

        .mobile-control-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(80, 80, 80, 0.8);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 20px;
        }

        .mobile-control-btn.end-call {
            background-color: #ff3b30;
        }

        .mobile-control-btn.muted {
            background-color: #ff3b30;
        }

        /* Main video streams */
        #desktop-main-stream,
        #mobile-main-stream {
            width: 100%;
            height: 100%;
            position: absolute;
            object-fit: cover;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {

            html,
            body {
                overflow: hidden;
            }

            .desktop-view {
                display: none;
            }

            .mobile-container {
                display: block;
                border-radius: 0;
            }

            .mobile-controls {
                position: fixed;
                bottom: 5vh;
            }
        }

        @media (min-height: 800px) and (max-width: 768px) {
            .mobile-controls {
                bottom: 8vh;
            }
        }

        /* Dot pattern for background decoration */
        .dot-pattern {
            position: absolute;
            width: 150px;
            height: 150px;
            background-image: radial-gradient(circle, rgba(255, 255, 255, 0.2) 2px, transparent 2px);
            background-size: 15px 15px;
            z-index: -1;
        }

        .dot-pattern.top-right {
            top: 0;
            right: 0;
        }

        .dot-pattern.bottom-left {
            bottom: 0;
            left: 0;
        }

        .avatar-user {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 10px;
            z-index: 5;
        }
    </style>
</head>

<body>
    <!-- Dot patterns for background -->
    <div class="dot-pattern top-right"></div>
    <div class="dot-pattern bottom-left"></div>

    <div class="container-fluid p-0">
        <!-- Desktop View -->
        <div class="desktop-view">
            <div class="video-container">
                <!-- Main Stream Container (will show remote user) -->
                <div id="desktop-main-stream"></div>

                <!-- Connecting message -->
                <div class="avatar-user">
                    <img src="{{Helper::getFile(config('path.avatar').$avatarCurrentUser)}}" alt="User" class="rounded-circle">
                </div>

                <!-- Timer badge -->
                <div class="timer-badge">
                    <i class="fas fa-clock"></i> <span id="desktop-timer">{{ is_null($audioCall->joined_at) ? $audioCall->minutes : $audioCall->timeElapsed }}</span> {{ __('general.m') }}
                </div>

                <!-- Control buttons -->
                <div class="controls">
                    <div class="control-btn" id="desktop-mic-btn">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <div class="control-btn end-call" id="desktop-end-call-btn">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile View -->
        <div class="mobile-container">
            <!-- Main Stream Container (will show remote user) -->
            <div id="mobile-main-stream"></div>

            <!-- Connecting message -->
            <div class="avatar-user">
                    <img src="{{Helper::getFile(config('path.avatar').$avatarCurrentUser)}}" alt="User" class="rounded-circle">
                </div>

            <!-- Timer badge -->
            <div class="mobile-timer-badge">
                <i class="fas fa-clock"></i> <span id="mobile-timer">{{ is_null($audioCall->joined_at) ? $audioCall->minutes : $audioCall->timeElapsed }}</span> {{ __('general.m') }}
            </div>

            <!-- Control buttons -->
            <div class="mobile-controls">
                <div class="mobile-control-btn" id="mobile-mic-btn">
                    <i class="fas fa-microphone"></i>
                </div>
                <div class="mobile-control-btn end-call" id="mobile-end-call-btn">
                    <i class="fas fa-phone-alt"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle with Popper -->
    <script src="{{ url('public/js/core.min.js') }}?v={{config('settings.version')}}"></script>
    <script src="{{ url('public/bootstrap/js/bootstrap.bundle.min.js') }}?v={{config('settings.version')}}"></script>
    <script src="{{ url('public/js/agora/AgoraRTCSDK-v4.js') }}?v={{config('settings.version')}}"></script>

    <script>
    const AGORA_APP_ID = "{{ $settings->agora_app_id }}";
    const CHANNEL_NAME = "audio-call-channel-{{ $audioCall->id }}";
    const TOKEN = "{{ $agoraToken }}" || null;

    const REDIRECT_URL = "{{ route('audio.call.finish', $audioCall->id) }}";

    const PUSHER_APP_KEY = "{{ config('broadcasting.connections.pusher.key') }}";
    const PUSHER_CLUSTER = "{{ config('broadcasting.connections.pusher.options.cluster') }}";
    const PUSHER_CHANNEL = "audio-call-timer-channel-{{ $audioCall->id }}";

    let rtc = {
        client: null,
        localAudioTrack: null
    };

    let timerInterval;
    let minutes = 0;

    let isMicMuted = false;
    let isMobile = window.matchMedia("(max-width: 768px)").matches;

    let timerStarted = false;
    let pusher;
    let timerChannel;

    // Audio call UI elements
    const desktopMicBtn = document.getElementById("desktop-mic-btn");
    const desktopEndCallBtn = document.getElementById("desktop-end-call-btn");
    const desktopTimerElement = document.getElementById("desktop-timer");

    const mobileMicBtn = document.getElementById("mobile-mic-btn");
    const mobileEndCallBtn = document.getElementById("mobile-end-call-btn");
    const mobileTimerElement = document.getElementById("mobile-timer");


    let isSpeakerOn = true;

    window.onload = async function() {
        initializePusher();
        await initializeAgora();
    };

    function initializePusher() {
        pusher = new Pusher(PUSHER_APP_KEY, {
            cluster: PUSHER_CLUSTER,
            forceTLS: true
        });
        
        timerChannel = pusher.subscribe(PUSHER_CHANNEL);
        
        timerChannel.bind('timer-update', function(data) {
            updateTimerDisplay(data);
            statusAudioCall(data);
        });
        
        timerChannel.bind('timer-start', function(data) {
            if (!timerStarted) {
                startTimer();
                timerStarted = true;
            }
        });
        
        timerChannel.bind('call-end', function() {
            endCall();
        });
    }

    async function initializeAgora() {
        rtc.client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
        
        rtc.client.on("user-published", async (user, mediaType) => {
            await rtc.client.subscribe(user, mediaType);
            
            if (mediaType === "audio") {
                const remoteAudioTrack = user.audioTrack;
                remoteAudioTrack.play();
            }
        });
        
        
        try {
            await rtc.client.join(AGORA_APP_ID, CHANNEL_NAME, TOKEN, null);
            
            // Create only audio track for audio calls
            rtc.localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack({
                encoderConfig: "music_standard",
                ANS: true, // Automatic Noise Suppression
                AEC: true, // Acoustic Echo Cancellation
                AGC: true, // Automatic Gain Control
            });
            
            // Enable volume indicator
            rtc.client.enableAudioVolumeIndicator();
            
            await rtc.client.publish([rtc.localAudioTrack]);
            
            triggerPusherEvent('timer-start', {});
            startTimer();
            timerStarted = true;
        
            
        } catch (error) {
            console.error("Error initializing Agora: ", error);
            if (error.code === "PERMISSION_DENIED") {
                alert("{{ __('general.error_connection_permission') }}");
            } else {
                alert(`{{ __('general.agora_error') }}: ${error.code}`);
            }
        }
    }

    function triggerPusherEvent(eventName) {
        fetch("{{ route('timer.audio.call') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                channel: PUSHER_CHANNEL,
                event: eventName,
                id: {{ $audioCall->id }}
            })
        }).catch(error => console.error("Error sending Pusher event AudioCall:", error));
    }

    function startTimer() {
        timerInterval = setInterval(() => {
            triggerPusherEvent('timer-update', {});
    
        }, 60000);
    }

    function statusAudioCall(minutesRemaining) {
        console.log(minutesRemaining);
        if (minutesRemaining <= 0) {
            triggerPusherEvent('call-end', {});
            endCall();
        }
    }

    function updateTimerDisplay(minutes) {
        if (desktopTimerElement) desktopTimerElement.innerHTML = `${minutes}`;
        if (mobileTimerElement) mobileTimerElement.innerHTML = ` ${minutes}`;
    }

    // End call
    async function endCall() {
        clearInterval(timerInterval);

        if (rtc.localAudioTrack) {
            rtc.localAudioTrack.close();
        }

        try {
            await rtc.client.leave();

            setTimeout(() => {
                window.location.href = REDIRECT_URL;
            }, 1000);
        } catch (error) {
            console.error("Error leaving channel: ", error);
        }
    }

    // Event listeners for desktop
    if (desktopMicBtn) desktopMicBtn.addEventListener("click", toggleMic);
    if (desktopEndCallBtn) desktopEndCallBtn.addEventListener("click", function() {
        swal(
            {
                title: "{{__('general.delete_confirm')}}",
                type: "warning",
                showLoaderOnConfirm: true,
                showCancelButton: true,
                confirmButtonColor: "#ff3b30",
                confirmButtonText: "{{ __('general.yes') }}",
                cancelButtonText: "{{ __('general.no') }}",
                closeOnConfirm: false,
            },
            function (isConfirm) {
                if (isConfirm) {
                    triggerPusherEvent('call-end', {});
                    endCall();
                }
            });
    });

    // Event listeners for mobile
    if (mobileMicBtn) mobileMicBtn.addEventListener("click", toggleMic);
    if (mobileEndCallBtn) mobileEndCallBtn.addEventListener("click", function() {
        swal(
            {
                title: "{{__('general.delete_confirm')}}",
                type: "warning",
                showLoaderOnConfirm: true,
                showCancelButton: true,
                confirmButtonColor: "#ff3b30",
                confirmButtonText: "{{ __('general.yes') }}",
                cancelButtonText: "{{ __('general.no') }}",
                closeOnConfirm: false,
            },
            function (isConfirm) {
                if (isConfirm) {
                    triggerPusherEvent('call-end', {});
                    endCall();
                }
            });
    });

    function toggleMic() {
        if (rtc.localAudioTrack) {
            isMicMuted = !isMicMuted;
            rtc.localAudioTrack.setEnabled(!isMicMuted);
            
            if (isMicMuted) {
                if (desktopMicBtn) desktopMicBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
                if (mobileMicBtn) mobileMicBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
                if (desktopMicBtn) desktopMicBtn.classList.add("muted");
                if (mobileMicBtn) mobileMicBtn.classList.add("muted");
            } else {
                if (desktopMicBtn) desktopMicBtn.innerHTML = '<i class="fas fa-microphone"></i>';
                if (mobileMicBtn) mobileMicBtn.innerHTML = '<i class="fas fa-microphone"></i>';
                if (desktopMicBtn) desktopMicBtn.classList.remove("muted");
                if (mobileMicBtn) mobileMicBtn.classList.remove("muted");
            }
        }
    }
    </script>
</body>
</html>