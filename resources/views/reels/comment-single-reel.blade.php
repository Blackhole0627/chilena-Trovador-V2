<div class="wrap-comments{{ $comment->id }} wrapComments">
        <div class="comments isCommentWrap media li-group pt-3 pb-3 {{ $isReply ? 'pl-5 isReplyComment' : ''}}" data="{{ $comment->id }}">
            <a class="float-left" href="{{url($comment->user->username)}}" target="_blank">
                <img class="rounded-circle mr-3 avatarUser"
                    src="{{Helper::getFile(config('path.avatar').$comment->user->avatar)}}" width="40"></a>
            <div class="media-body">
                <h6 class="media-heading mb-0">
                    <a href="{{url($comment->user->username)}}" target="_blank">
                        {{$comment->user->hide_name == 'yes' ? $comment->user->username : $comment->user->name}}
                    </a>

                    @if ($comment->user->verified_id == 'yes')
                    <small class="verified">
                        <i class="bi bi-patch-check-fill"></i>
                    </small>
                    @endif

                </h6>
                <p class="list-grid-block p-text mb-0 text-word-break updateComment isComment{{ $comment->id }}">{!! Helper::linkText(Helper::checkText($comment->reply)) !!}</p>
                <span class="small sm-font sm-date text-muted timeAgo mr-2" data="{{date('c', strtotime($comment->created_at))}}"></span>
                
                @auth
                    <span class="small sm-font sm-date text-muted mr-2 c-pointer font-weight-bold replyButton" data="{{ $isReply ? $comment->comment_reels_id : $comment->id }}" data-username="{{'@'.$comment->user->username}}">
                        {{ __('general.reply') }}
                    </span>
                @endauth

                @auth
                    @if ($comment->user_id == auth()->id() || $creator == auth()->id())
                    <div class="dropdown d-inline align-middle">
                        <span href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false">
                            <i class="bi-three-dots"></i>
                        </span>

                        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                            <a class="dropdown-item {{ $isReply ? 'delete-replies-reel' : 'delete-comment-reel' }}" data="{{ $comment->id }}" data-type="{{ $isReply ? 'isReplies' : 'isComment' }}" href="javascript:void(0);">
                                <i class="feather icon-trash-2 mr-2"></i> {{ __('general.delete') }}
                            </a>
                        </div>
                    </div>
                    @endif
                @endauth

                <span class="likeCommentReel c-pointer float-right pulse-btn" data-id="{{ $comment->id }}" data-type="{{ $isReply ? 'isReplies' : 'isComment' }}">
                    <i class="@if (auth()->check() && $comment->likes->where('user_id', auth()->id())->first()) fas fa-heart text-red mr-1 @else far fa-heart mr-1 @endif"></i>
                    <span class="countCommentsLikes">{{ $comment->likes->count() != 0 ? $comment->likes->count() : null }}</span>
                </span>
            </div><!-- media-body -->
        </div>

        @if (!$fromReplies)
            @include('reels.replies-reel')
        @endif

    </div><!-- wrapComments -->