(function ($) {
    "use strict";

    // Start Video Call
    $(document).on('click', '.startVideoCall', function (e) {

        e.preventDefault();

        $('#callingToFan').html(callingToFan);
        $('#callingStatus').html(please_wait_answer);
        
        var videoModal = new bootstrap.Modal(document.getElementById('videoCallModal'), {
            backdrop: 'static',
            keyboard: false
        });
        videoModal.show();

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'post',
            url: URL_BASE + '/create/video-call',
            data: { 'user': user_id_chat },
            dataType: 'json'
        }).done(function (data) {
            if (data.status) {
                $('#cancelCall').attr('data-id', data.buyer);
                $('#cancelCall').attr('data-videocall', data.videoCallId);
            } else {
                 bootstrap.Modal.getInstance(document.getElementById('videoCallModal')).hide();
                 $('.popout').addClass('popout-error error-video-call').html(error_occurred).fadeIn('500');
            }

        }).fail(function (jqXHR, ajaxOptions, thrownError) {
            bootstrap.Modal.getInstance(document.getElementById('videoCallModal')).hide();
            
            const errorMessage = error_occurred + ' - ' + jqXHR.responseJSON?.message || error_occurred;

            $('.popout').addClass('popout-error error-video-call').html(errorMessage).fadeIn('500');
        });
    });

    $(document).on('click', '#cancelCall', function (e) {
        e.preventDefault();

        const videoCallId = $(this).attr('data-videocall');
        const buyerId = $(this).attr('data-id');
        const element = $(this);

        element.attr({ 'disabled': 'true' });
        bootstrap.Modal.getInstance(document.getElementById('videoCallModal')).hide();

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'post',
            url: URL_BASE + '/cancel/video-call/' + videoCallId,
            data: { 'user': buyerId },
            dataType: 'json'
        }).done(function () {
            element.removeAttr('disabled');

        }).fail(function (jqXHR, ajaxOptions, thrownError) {
            element.removeAttr('disabled');

            const errorMessage = error_occurred + ' - ' + jqXHR.responseJSON?.message || error_occurred;

            $('.popout').addClass('popout-error error-video-call').html(errorMessage).fadeIn('500');
        });
    });

    // Start Audio Call
    $(document).on('click', '.startAudioCall', function (e) {

        e.preventDefault();

        $('#callingAudioToFan').html(callingToFan);
        $('#callingAudioStatus').html(please_wait_answer);
        
        var audioModal = new bootstrap.Modal(document.getElementById('audioCallModal'), {
            backdrop: 'static',
            keyboard: false
        });
        audioModal.show();

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'post',
            url: URL_BASE + '/create/audio-call',
            data: { 'user': user_id_chat },
            dataType: 'json'
        }).done(function (data) {
            if (data.status) {
                $('#cancelAudioCall').attr('data-id', data.buyer);
                $('#cancelAudioCall').attr('data-audiocall', data.audioCallId);
            } else {
                 bootstrap.Modal.getInstance(document.getElementById('audioCallModal')).hide();
                 $('.popout').addClass('popout-error error-video-call').html(error_occurred).fadeIn('500');
            }

        }).fail(function (jqXHR, ajaxOptions, thrownError) {
            bootstrap.Modal.getInstance(document.getElementById('audioCallModal')).hide();
            
            const errorMessage = error_occurred + ' - ' + jqXHR.responseJSON?.message || error_occurred;

            $('.popout').addClass('popout-error error-video-call').html(errorMessage).fadeIn('500');
        });
    });

    $(document).on('click', '#cancelAudioCall', function (e) {
        e.preventDefault();

        const audioCallId = $(this).attr('data-audiocall');
        const buyerId = $(this).attr('data-id');
        const element = $(this);

        element.attr({ 'disabled': 'true' });
        bootstrap.Modal.getInstance(document.getElementById('audioCallModal')).hide();

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'post',
            url: URL_BASE + '/cancel/audio-call/' + audioCallId,
            data: { 'user': buyerId },
            dataType: 'json'
        }).done(function () {
            element.removeAttr('disabled');

        }).fail(function (jqXHR, ajaxOptions, thrownError) {
            element.removeAttr('disabled');

            const errorMessage = error_occurred + ' - ' + jqXHR.responseJSON?.message || error_occurred;

            $('.popout').addClass('popout-error error-video-call').html(errorMessage).fadeIn('500');
        });
    });

})(jQuery);