<div class="comment-content">
    @foreach ($comments as $comment)
        @include('reels.comment-single-reel')
    @endforeach
</div>

<div class="alert alert-danger alert-small dangerAlertComments mb-0 display-none rounded-0 text-break">
    <ul class="list-unstyled m-0 showErrorsComments"></ul>
</div>

<div class="isReplyTo display-none w-100 font-weight-bold bg-light py-2 px-3">
    {{ __('general.replying_to') }} <span class="username-reply"></span>

    <span class="float-right c-pointer cancelReply" title="{{ __('admin.cancel') }}">
        <i class="bi-x-lg"></i>
    </span>
</div>

@auth
<div class="container-reel-footer py-2 px-3">
    <div class="media position-relative pt-3 border-top">
        <div class="blocked display-none"></div>
        <div class="media-body">
            <form action="{{ url('reel/comment') }}" method="post" class="comments-form">
                @csrf
                <input type="hidden" name="reel_id" value="{{ $reelId }}">
                <input class="isReply" type="hidden" name="isReply" id="isReplyInput" value="">

                <div>
                    <span class="triggerEmoji px-1" data-toggle="dropdown" id="dropdownEmojiReel" aria-haspopup="true" aria-expanded="false" role="button" style="cursor: pointer;">
                        <i class="bi-emoji-smile"></i>
                    </span>

                    <div class="dropdown-menu dropdown-menu-right dropdown-emoji custom-scrollbar"
                        aria-labelledby="dropdownEmojiReel">
                        @include('includes.emojis')
                    </div>
                </div>

                <input type="text" name="comment" required minlength="1" class="form-control commentOnReel inputComment emojiArea border-0"
                    autocomplete="off" placeholder="{{__('general.write_comment')}}">
            </form>
        </div>
    </div>
</div><!-- container-reel-footer -->
@endauth