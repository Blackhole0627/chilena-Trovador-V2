(function ($) {
	"use strict";

	//<---------------- Create Reel ----------->>>>
	$(document).on('click', '#createReelBtn', function (s) {

		s.preventDefault();
		let element = $(this);

		element.attr({ 'disabled': 'true' });
		element.find('i').addClass('spinner-border spinner-border-sm align-middle mr-1');

		(function () {

			$("#addReelForm").ajaxForm({
				dataType: 'json',
				error: function (responseText, statusText, xhr, $form) {
					element.removeAttr('disabled');

					if (!xhr) {
						xhr = '- ' + error_occurred;
					} else {
						xhr = '- ' + xhr;
					}

					$('.popout').removeClass('popout-success').addClass('popout-error').html(error_oops + ' ' + xhr + '').fadeIn('500').delay('5000').fadeOut('500');
					element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
				},
				success: function (result) {

					if (result.success) {
						$('#errorCreateReel').hide();

						if (result.encode) {
							swal({
								type: 'info',
								title: video_on_way,
								text: video_processed_info,
								confirmButtonText: ok
							});

							let api = $.fileuploader.getInstance($('input[name="media"]'));
							api.reset();

							$('input[name="media"]').val('');
							$('#title').val('');

							element.removeAttr('disabled');
							element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');

						} else {
							window.location.href = result.url;
						}

					} else {
						let error = '';
						let $key = '';

						for ($key in result.errors) {
							error += '<li><i class="fa fa-times-circle mr-1"></i> ' + result.errors[$key] + '</li>';
						}

						$('#showErrorsCreateReel').html(error);
						$('#errorCreateReel').fadeIn(500);

						element.removeAttr('disabled');
						element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');

						if (result.failed) {
							$('input[name="media"]').val('');
							$('#title').val('');

							let api = $.fileuploader.getInstance($('input[name="media"]'));
							api.reset();
						}
					}
				}//<----- SUCCESS
			}).submit();
		})(); //<--- FUNCTION %
	});//<<<-------- * END FUNCTION CLICK * ---->>>>

})(jQuery);
