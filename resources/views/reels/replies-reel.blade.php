@if ($comment->replies?->count())
<div class="btn-block mb-4 text-left wrap-container-replies-reel"">
            <a href=" javascript:void(0)" class="showMoreRepliesReel" data-hide="{{ __('general.hide_replies') }}" data-show="{{ __('general.view_replies') }}">
    <span class="line-replies"></span>{{ __('general.view_replies') }}
    </a>
</div>

<div class="d-none showMoreRepliesReelContainer">
    @foreach ($comment->replies as $comment)
        @include('reels.comment-single-reel', ['fromReplies' => true, 'isReply' => true])
    @endforeach
</div>
@endif