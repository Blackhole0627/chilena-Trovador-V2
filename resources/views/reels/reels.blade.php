<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ auth()->check() && auth()->user()->dark_mode == 'on' ? 'dark' : 'light' }}">
  <head>
    <meta charset="UTF-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="{{ config('settings.theme_color_pwa') }}">
    <link href="{{ url('public/img', $settings->favicon) }}" rel="icon">
    <title>{{__('general.reels')}} - {{$settings->title}}</title>
    
    @include('includes.css_general')

    <script>
      let videoData = [

      @if (isset($reelSingle))
        {
            id: {{ $reelSingle->id }},
            canSeeUser: true,
            src: "{{ Helper::getFile(config('path.reels') . $reelSingle->media->name) }}",
            thumbnail: "{{ $reelSingle->media->video_poster ? Helper::getFile(config('path.reels') . $reelSingle->media->video_poster) : Helper::getFile(config('path.avatar') . $reelSingle->user->avatar) }}",
            duration: "{{ $reelSingle->media->duration_video }}",
            user: {
                id: {{ $reelSingle->user->id }},
                name: "{{ $reelSingle->user->hide_name == 'yes' ? $reelSingle->user->username : $reelSingle->user->name }}",
                username: "{{ $reelSingle->user->username }}",
                avatar: "{{ Helper::getFile(config('path.avatar') . $reelSingle->user->avatar) }}",
                cover: "{{ Helper::getFile(config('path.cover') . $reelSingle->user->cover) }}",
            },
            title: "{{ $reelSingle->title }}",
            likes: {{ $reelSingle->likes }},
            views: {{ $reelSingle->views }},
            comments_count: {{ $reelSingle->comments_count }},
            isLikedUser: {{ auth()->check() && auth()->user()->checkLikeOnReel($reelSingle->id) ? 'true' : 'false' }},
            reelIsPublic: {{ $reelSingle->type == 'public' ? 'true' : 'false' }},
        },
      @endif

      @foreach ($reels as $reel)
      {
              id: {{ $reel->id }},
              canSeeUser: true,
              src: "{{ Helper::getFile(config('path.reels') . $reel->media->name) }}",
              thumbnail: "{{ $reel->media->video_poster ? Helper::getFile(config('path.reels') . $reel->media->video_poster) : Helper::getFile(config('path.avatar') . $reel->user->avatar) }}",
              duration: "{{ $reel->media->duration_video }}",
              user: {
                  id: {{ $reel->user->id }},
                  name: "{{ $reel->user->hide_name == 'yes' ? $reel->user->username : $reel->user->name }}",
                  username: "{{ $reel->user->username }}",
                  avatar: "{{ Helper::getFile(config('path.avatar') . $reel->user->avatar) }}",
                  cover: "{{ Helper::getFile(config('path.cover') . $reel->user->cover) }}",
              },
              title: "{{ $reel->title }}",
              likes: {{ $reel->likes }},
              views: {{ $reel->views }},
              comments_count: {{ $reel->comments_count }},
              isLikedUser: {{ auth()->check() && auth()->user()->checkLikeOnReel($reel->id) ? 'true' : 'false' }},
              reelIsPublic: {{ $reel->type == 'public' ? 'true' : 'false' }},
          },
          @endforeach
      ];

      // Variable title share
      const titleShare = "{{ __('general.reels') }} - " + "{{ config('app.name') }}";
    </script>
  </head>

<body style="background-color: #000 !important;">
    <div class="loader-reels">
      <div class="active" id="videoLoaderPage"></div>
      <div class="loader-text">
        {{ __('general.loading') }} {{  __('general.reels') }}
      </div>
    </div>

    <div id="videoGrid" class="d-none"></div>

  @include('reels.video-full-screen')
  
  @guest
    @include('includes.modal-login')
  @endguest

  @if (auth()->check() && $settings->disable_tips == 'off')
     @include('includes.modal-tip')
   @endif

  @include('includes.javascript_general')

</body>
</html>