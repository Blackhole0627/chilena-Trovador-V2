<div class="container-comments">
    <div class="container-media">
        <div class="comment-container">

            <div class="comment-header">
                <h5 class="comment-title">{{ __('general.comments') }}</h5>
                <button class="comment-close-icon toggleCommentsReel">
                    <i class="bi-x-lg"></i>
                </button>
            </div>

            <div id="wrapContainerCommentsReel" class="d-flex flex-column h-100 overflow-auto"></div>

            <div id="preloaderReel" class="preloader-reel">
            @for ($i = 0; $i < 9; ++$i)
                <div class="p-3 w-100 pt-0 pb-3">
                    <p class="mb-1 item-loading position-relative loading-text-1"></p> 
                    <p class="mb-1 item-loading position-relative loading-text-2"></p> 
                    <p class="mb-0 item-loading position-relative loading-text-3"></p> 
                </div>
                @endfor
            </div>        
    </div><!-- comment-container -->

</div><!-- container-media -->
</div><!-- container-comments -->