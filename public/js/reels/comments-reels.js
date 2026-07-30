(function ($) {
	"use strict";


	$(document).on('click', '.toggleCommentsReel', function (e) {
		$('.comment-container').toggleClass('show');
	});

	$(document).on('click', '.showMoreRepliesReel', function (e) {
		let element = $(this).parents('.wrapComments').find('.showMoreRepliesReelContainer');
		let span = '<span class="line-replies"></span>';
		element.toggleClass('d-none');

		if (element.hasClass('d-none')) {
			$(this).html(span + $(this).attr('data-show'));
		} else {
			$(this).html(span + $(this).attr('data-hide'));
		}
	});

	//============= Comments
	$(document).on('keypress', '.commentOnReel', function (e) {

		if (e.which == 13) {

			let element = $(this);
			let isReplyTo = $('.container-comments').find('.isReplyTo');
			let inputIsReply = $('.container-comments ').find('.isReply');
			let counterComments = $('#counterComments');
			let currentCounterComments = parseInt(counterComments.text());
			let form = element.closest('form')[0];
			let currentVideo = videoData[activeIndex];
			let reelId = currentVideo.id;

			console.log(currentVideo);

			if (form.checkValidity()) {

				e.preventDefault();

				element.blur();

				element.parents('.container-reel-footer').find('.blocked').show();

				$.ajax({
					headers: {
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
					},
					type: "POST",
					url: URL_BASE + "/reel/comment",
					dataType: 'json',
					data: $('.comments-form').serialize(),
					success: function (result) {

						if (result.success) {
							counterComments.text(currentCounterComments + 1);

							updateVideoById(reelId, { comments_count: currentCounterComments + 1 });

							$('.commentOnReel').val('');
							$('.dangerAlertComments').fadeOut(1);

							if (result.isReply) {
								if ($('.wrap-comments' + result.idComment + ' .showMoreRepliesReelContainer').length) {
									$('.wrap-comments' + result.idComment + ' .showMoreRepliesReelContainer').append(result.data);
								} else {
									$('.wrap-comments' + result.idComment).append(result.data);
								}
							} else {
								$('.comment-content').animate({ scrollTop: 0 }, 100);
								$('.comment-content').prepend(result.data);
							}

							jQuery(".timeAgo").timeago();

							$('.blocked').hide();
							isReplyTo.slideUp(50);
							inputIsReply.val('');

						} else {
							let error = '';
							let $key = '';

							for ($key in result.errors) {
								error += '<li><i class="fa fa-times-circle mr-1"></i> ' + result.errors[$key] + '</li>';
							}

							$('.showErrorsComments').html(error);
							$('.dangerAlertComments').fadeIn(500);
							$('.blocked').hide();

							counterComments.text(currentCounterComment);
							updateVideoById(reelId, { comments_count: currentCounterComments});
						}
					}//<-- RESULT
				}).fail(function (jqXHR) {
					showErrorOccurred(jqXHR);
					$('.blocked').hide();

					element.removeAttr('disabled');

					counterComments.text(currentCounterComment);
					updateVideoById(reelId, { comments_count: currentCounterComments});
				});//<--- AJAX
			}

		}//e.which == 13
	});

	$(document).on('click', '.likeCommentReel', function () {

		let element = $(this);
		let commentId = element.data('id');
		let type = element.data('type');
		let elementCounterComments = element.find('.countCommentsLikes');
		let currentCounterComments = parseInt(elementCounterComments.text());

		$.post({
			url: URL_BASE + "/comment/like/reel",
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
				'Accept': 'application/json'
			},
			data: {
				comment_id: commentId,
				typeComment: type
			}
		}).done(function (data) {
			if (data.success == true) {
				if (data.type == 'like') {
					elementCounterComments.text(isNaN(currentCounterComments) ? 1 : currentCounterComments + 1);
					element.find('i').attr('class', 'fas fa-heart text-red mr-1');

					element.blur();

				} else if (data.type == 'unlike') {
					elementCounterComments.text(isNaN(currentCounterComments) || currentCounterComments == 1 ? '' : currentCounterComments - 1);
					element.find('i').attr('class', 'far fa-heart mr-1');

					element.blur();
				}
			} else {
				window.location.reload();
			}

			if (data.session_null) {
				window.location.reload();
			}

		}).fail(function (jqXHR) {
			elementCounterComments.text(isNaN(currentCounterComments) ? '' : currentCounterComments);

			showErrorOccurred(jqXHR);
		});
	});

	$(document).on('click', '.delete-comment-reel', function (e) {

		e.preventDefault();

		let element = $(this);
		let id = element.attr("data");
		let type = element.attr("data-type");
		let input = element.parents('.container-comments').find('.inputComment');
		let inputIsReply = element.parents('.container-comments').find('.isReply');
		let isReplyTo = element.parents('.container-comments').find('.isReplyTo');
		let counterRepliesComment = element.parents('.wrapComments').find('.isReplyComment').length;
		let counterComments = $('#counterComments');
		let currentCounterComments = parseInt(counterComments.text());
		let currentVideo = videoData[activeIndex];
		let reelId = currentVideo.id;

		element.blur();

		$.ajaxSetup({
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});

		swal({
			title: delete_confirm,
			text: confirm_delete_comment,
			type: "error",
			showLoaderOnConfirm: true,
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: yes_confirm,
			cancelButtonText: cancel_confirm,
			closeOnConfirm: true,
		},
			function (isConfirm) {
				if (isConfirm) {
					$.post(URL_BASE + "/delete/comment/reel/" + id, function (data) {
						if (data.success) {
							element.parents('.wrapComments').fadeOut(400, function () {
								let commentsToDelete = counterRepliesComment != 0 ? counterRepliesComment + 1: 1;
								counterComments.text(currentCounterComments - commentsToDelete);
								updateVideoById(reelId, { comments_count: currentCounterComments - commentsToDelete });
								element.parents('.wrapComments').remove();

								if (inputIsReply.val() === id && type === 'isComment') {
									input.val('');
									isReplyTo.slideUp(50);
									inputIsReply.val('');
								}
							});
						} else {
							showErrorOccurred();
						}
					}).fail(function (jqXHR) {
						showErrorOccurred(jqXHR);
					});
				}
			});
	});

	$(document).on('click', '.delete-replies-reel', function (e) {

		e.preventDefault();

		let element = $(this);
		let id = element.attr("data");
		let counterComments = $('#counterComments');
		let currentCounterComments = parseInt(counterComments.text());
		let currentVideo = videoData[activeIndex];
		let reelId = currentVideo.id;

		element.blur();

		$.ajaxSetup({
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});

		swal({
			title: delete_confirm,
			text: confirm_delete_comment,
			type: "error",
			showLoaderOnConfirm: true,
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: yes_confirm,
			cancelButtonText: cancel_confirm,
			closeOnConfirm: true,
		},
			function (isConfirm) {
				if (isConfirm) {
					$.post(URL_BASE + "/reply/delete/reel/" + id, function (data) {
						if (data.success) {
							counterComments.text(currentCounterComments - 1);
							updateVideoById(reelId, { comments_count: currentCounterComments - 1 });
							element.parents('.media').fadeOut(400, function () {
								element.parents('.media').remove();
							});
						} else {
							showErrorOccurred();
						}
					}).fail(function (jqXHR) {
						showErrorOccurred(jqXHR);
					});
				}
			});
	});

	function showErrorOccurred(jqXHR) {
		const status = jqXHR.status;
		const message = JSON.parse(jqXHR.responseText).message;

		swal({
			title: error_oops,
			text: error_occurred + ' - ' + status + ': ' + message,
			type: "error",
			confirmButtonText: ok
		});
	}

})(jQuery);