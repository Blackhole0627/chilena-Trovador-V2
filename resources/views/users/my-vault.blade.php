@extends('layouts.app')

@section('title') {{__('general.vault')}} -@endsection

@section('css')

<link rel="stylesheet"
    href="{{ asset('public/js/fileuploader/jquery.fileuploader-theme-gallery.css') }}?v={{ $settings->version }}">

<script>
    @if ($settings->video_encoding == 'off')
    var extensionsVault = ['png','jpeg','jpg','gif','ief','video/mp4'];
    @else
    var extensionsVault = ['png','jpeg','jpg','gif','ief','video/mp4','video/quicktime','video/3gpp','video/mpeg','video/x-matroska','video/x-ms-wmv','video/vnd.avi','video/avi','video/x-flv'];
  @endif
</script>

<style>
    :root {
        --colorVault:{{ auth()->user()->dark_mode == 'on'? '#222' : '#fff' }};
        --colorDark: {{ auth()->user()->dark_mode == 'on'? '#303030' : '#fff' }};
        --colorText: {{ auth()->user()->dark_mode == 'on'? '#fff' : '#35354f' }};
    }
    .fileuploader { max-width: 100%; display:block; padding: 0;}
    .fileuploader-theme-gallery .fileuploader-input,
    .fileuploader-theme-gallery .fileuploader-input-inner {
        background: var(--colorVault) !important;  
    }
    .fileuploader-items {white-space: unset !important;}
    .fileuploader-items-list {
        overflow: hidden !important;
    }
    .fileuploader-input-inner span {padding: 0 10px;}
    .fileuploader-item-inner { background: var(--colorDark) !important; }
    .fileuploader-item-inner h5 { color: var(--colorText) !important; }
</style>

@endsection

@section('content')
<section class="section section-sm">
    <div class="container">
        <div class="row justify-content-center text-center mb-sm">
            <div class="col-lg-8 py-5">
                <h2 class="mb-0 font-montserrat"><i class="feather icon-archive mr-2"></i> {{__('general.vault')}}</h2>
                <p class="lead text-muted mt-0">{{__('general.all_vault_uploaded')}}</p>
            </div>
        </div>
        <div class="row">

            <div class="col-md-12 mb-5 mb-lg-0">

                @if (session('notify'))
                <div class="alert alert-primary">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>

                    <i class="bi-info-circle mr-1"></i> {{ session('notify') }}
                </div>
                @endif

                @if ($settings->video_encoding == 'on')
                <div class="alert alert-info">
                    <i class="bi-info-circle-fill mr-1"></i> {{ __('general.video_encoding_warning') }}
                </div>
                @endif

                <div class="d-lg-flex d-block justify-content-between align-items-center mb-3 text-word-break">
                    <form class="position-relative mr-3 w-100 mb-lg-0 mb-2" role="search" autocomplete="off"
                        action="{{ url('my/vault') }}" method="get">
                        <i class="bi bi-search btn-search bar-search"></i>
                        <input type="text" minlength="3" required="" name="q" class="form-control pl-5"
                            value="{{ request('q') }}" placeholder="{{ __('general.search') }}" aria-label="Search">
                    </form>

                    <div class="w-lg-100">
                        <select class="form-control custom-select w-100 pr-4 filter">
                            <option @selected(!request('sort')) value="{{ url('my/vault') }}">{{ __('general.all') }}
                            </option>

                            <option @selected(request('sort')=='photos' ) value="{{ url('my/vault?sort=photos') }}">
                                {{ __('general.photos') }}
                            </option>

                            <option @selected(request('sort')=='videos' ) value="{{ url('my/vault?sort=videos') }}">
                                {{ __('general.videos') }}
                            </option>

                        </select>
                    </div>
                </div>

                <div class="d-block">
                    <!-- file input -->
                    <input @if ($preloadedFiles) data-fileuploader-files='{!! $preloadedFiles !!}' @endif type="file" name="files" class="gallery_media">
                </div>


                @if ($files->hasPages())
                    {{ $files->onEachSide(0)->links() }}
                @endif

                @if ($files->isEmpty() && request('q') || $files->isEmpty() && request('sort'))
                <div class="my-5 text-center">
                    <span class="btn-block mb-3">
                        <i class="feather icon-archive ico-no-result"></i>
                    </span>

                    <h4 class="font-weight-light">{{__('general.no_results_found')}}</h4>
                    <a href="{{ url('my/vault') }}" class="btn btn-primary btn-sm mt-3">
                        <i class="bi-arrow-left mr-1"></i> {{ __('general.go_back') }}
                    </a>
                </div>
                @endif
            </div><!-- end col-md-6 -->

        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('public/js/fileuploader/fileuploader-vault.js') }}?v={{ $settings->version }}"></script>
@endsection