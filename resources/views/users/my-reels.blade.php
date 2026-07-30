@extends('layouts.app')

@section('title') {{__('general.my_reels')}} -@endsection

@section('content')
<section class="section section-sm">
    <div class="container">
      <div class="row justify-content-center text-center mb-sm">
        <div class="col-lg-8 py-5">
          <h2 class="mb-0 font-montserrat">
             <svg xmlns="http://www.w3.org/2000/svg" class="align-bottom"  fill="currentColor" width="35" height="35" viewBox="0 0 50 50">
                    <path d="M 15 4 C 8.9365932 4 4 8.9365932 4 15 L 4 35 C 4 41.063407 8.9365932 46 15 46 L 35 46 C 41.063407 46 46 41.063407 46 35 L 46 15 C 46 8.9365932 41.063407 4 35 4 L 15 4 z M 16.740234 6 L 27.425781 6 L 33.259766 16 L 22.574219 16 L 16.740234 6 z M 29.740234 6 L 35 6 C 39.982593 6 44 10.017407 44 15 L 44 16 L 35.574219 16 L 29.740234 6 z M 14.486328 6.1035156 L 20.259766 16 L 6 16 L 6 15 C 6 10.199833 9.7581921 6.3829803 14.486328 6.1035156 z M 6 18 L 44 18 L 44 35 C 44 39.982593 39.982593 44 35 44 L 15 44 C 10.017407 44 6 39.982593 6 35 L 6 18 z M 21.978516 23.013672 C 20.435152 23.049868 19 24.269284 19 25.957031 L 19 35.041016 C 19 37.291345 21.552344 38.713255 23.509766 37.597656 L 31.498047 33.056641 C 33.442844 31.951609 33.442844 29.044485 31.498047 27.939453 L 23.509766 23.398438 L 23.507812 23.398438 C 23.018445 23.120603 22.49297 23.001607 21.978516 23.013672 z M 21.982422 24.986328 C 22.158626 24.988232 22.342399 25.035052 22.521484 25.136719 L 30.511719 29.677734 C 31.220922 30.080703 31.220922 30.915391 30.511719 31.318359 L 22.519531 35.859375 C 21.802953 36.267773 21 35.808686 21 35.041016 L 21 25.957031 C 21 25.573196 21.201402 25.267385 21.492188 25.107422 C 21.63758 25.02744 21.806217 24.984424 21.982422 24.986328 z" stroke="currentColor" stroke-width="2" fill="none"></path>
                    </svg> {{__('general.my_reels')}}</h2>
          <p class="lead text-muted mt-0">{{__('general.my_reels_subtitle')}}</p>

          <div class="mt-2">
            @if ($settings->allow_reels)
              <a class="btn btn-primary" href="{{ url('create/reel') }}">
                <i class="bi-plus"></i> {{ __('general.create_reel') }}
              </a>
            @endif
          </div>
          
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

          @if (session('success_message'))
          <div class="alert alert-success">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">×</span>
              </button>

            <i class="bi-check-circle mr-1"></i> {{ session('success_message') }}
          </div>
          @endif

          @include('errors.errors-forms')

          @if ($reels->isNotEmpty())
          <div class="d-lg-flex d-block justify-content-between align-items-center mb-3 text-word-break">
            <form class="position-relative mr-3 w-100 mb-lg-0 mb-2" role="search" autocomplete="off" action="{{ url('my/reels') }}" method="get">
              <i class="bi bi-search btn-search bar-search"></i>
             <input type="text" minlength="3" required="" name="q" class="form-control pl-5" value="{{ request('q') }}" placeholder="{{ __('general.search') }}" aria-label="Search">
          </form>

            <div class="w-lg-100">
              <select class="form-control custom-select w-100 pr-4 filter">
                <option @selected(! request()->get('sort')) value="{{url('my/reels')}}">{{__('general.latest')}}</option>
                <option @selected(request()->get('sort') == 'oldest') value="{{url('my/reels').'?sort=oldest'}}">{{__('general.oldest')}}</option>
                <option @selected(request()->get('sort') == 'popular') value="{{url('my/reels').'?sort=popular'}}">{{__('general.popular')}}</option>
                <option @selected(request()->get('sort') == 'views') value="{{url('my/reels').'?sort=views'}}">{{__('general.views')}}</option>
              </select>
            </div>
          </div>

          <div class="card shadow-sm mb-2">
          <div class="table-responsive">
            <table class="table table-striped m-0">
              <thead>
                <tr>
                  <th scope="col">{{__('admin.title')}}</th>
                  <th scope="col">{{__('general.type')}}</th>
                  <th scope="col">{{__('general.interactions')}}</th>
                  <th scope="col">{{__('admin.date')}}</th>
                  <th scope="col">{{__('admin.status')}}</th>
                  <th scope="col">{{__('admin.actions')}}</th>
                </tr>
              </thead>

              <tbody>

                @foreach ($reels as $reel)
                  <tr>
                    <td>
                      @if ($reel->media)
                        <img src="{{ route('resize', ['path' => 'reels', 'file' => $reel->media->video_poster, 'size' => 35, 'crop' => 'fit']) }}" width="35" height="35" class="rounded mr-2">
                        @endif

                      {{ $reel->title ? str_limit($reel->title, 40, '...') : __('general.no_available') }}

                      <a href="javascript:void(0);" class="ml-1" data-toggle="modal" data-target="#modalEditReel{{ $reel->id }}">
                        <i class="bi-pencil-square"></i>
                      </a>
                    </td>
                    <td>
                      @if ($reel->type == 'private')
                        <i class="feather icon-lock mr-1" title="{{__('general.available_only_for_subscribers')}}"></i>
                      @else
                        <i class="iconmoon icon-WorldWide mr-1" title="{{__('general.available_everyone')}}"></i>
                      @endif
                    </td>
                    <td>
                      <i class="far fa-heart"></i> {{ $reel->likes }} 
                      <i class="far fa-comment ml-1"></i> {{ ($reel->comments_count) }}
                      <i class="feather icon-eye ml-1"></i> {{ $reel->views }}
                    </td>
                    <td>{{Helper::formatDate($reel->created_at)}}</td>
                    <td>
                      @if ($reel->status == 'active')
                        <span class="badge badge-pill badge-success text-uppercase">{{__('general.active')}}</span>
                      @elseif($reel->status == 'encode')
                      <span class="badge badge-pill badge-info text-uppercase">{{__('general.encode')}}</span>
                        @else
                        <span class="badge badge-pill badge-warning text-uppercase">{{__('general.pending')}}</span>
                      @endif
                    </td>
                    <td>
                      <div class="d-flex align-items-between">
                        @if ($reel->status == 'active')
                        <a href="{{ route('reels.section.show', $reel->id) }}" class="btn btn-link p-0 e-none mr-2">
                          <i class="bi-eye mr-1"></i>
                        </a>
                        @endif

                        <form action="{{ route('reel.delete', $reel->id) }}" method="POST">
                          @csrf
                          <button type="submit" class="btn btn-link p-0 btnActionDelete e-none">
                            <i class="bi-trash mr-1"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>

                  <div class="modal fade show" id="modalEditReel{{ $reel->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content">
                        <div class="modal-header border-bottom-0">
                          <h5 class="modal-title">{{ __('general.edit_reel') }} #{{ $reel->id }}</h5>
                          <button type="button" class="close close-inherit" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">
                              <i class="bi bi-x-lg"></i>
                            </span>
                          </button>
                        </div>
                        <div class="modal-body">
                          <form method="POST" action="{{ route('update.reel', $reel->id) }}" enctype="multipart/form-data" class="formCommentEdit">
                            @csrf
                          <input type="text" class="form-control" name="title" id="title" value="{{ $reel->title }}" placeholder="{{ __('general.title') }} ({{ __('general.optional') }})">

                          <div class="d-block w-100 mt-2">
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill float-right buttonActionSubmit">{{ __('admin.save') }}</button>
                          </div>

                        </form>
                      </div><!-- modal-body -->
                      </div><!-- modal-content -->
                    </div><!-- modal-dialog -->
                  </div>
                @endforeach
              </tbody>
            </table>
          </div>
          </div><!-- card -->

          @if ($reels->hasPages())
  		    {{ $reels->onEachSide(0)->links() }}
  		@endif

        @else
          <div class="my-5 text-center">
            <span class="btn-block mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="align-bottom border-0 ico-no-result"  fill="currentColor" width="20" height="20" viewBox="0 0 50 50">
                    <path d="M 15 4 C 8.9365932 4 4 8.9365932 4 15 L 4 35 C 4 41.063407 8.9365932 46 15 46 L 35 46 C 41.063407 46 46 41.063407 46 35 L 46 15 C 46 8.9365932 41.063407 4 35 4 L 15 4 z M 16.740234 6 L 27.425781 6 L 33.259766 16 L 22.574219 16 L 16.740234 6 z M 29.740234 6 L 35 6 C 39.982593 6 44 10.017407 44 15 L 44 16 L 35.574219 16 L 29.740234 6 z M 14.486328 6.1035156 L 20.259766 16 L 6 16 L 6 15 C 6 10.199833 9.7581921 6.3829803 14.486328 6.1035156 z M 6 18 L 44 18 L 44 35 C 44 39.982593 39.982593 44 35 44 L 15 44 C 10.017407 44 6 39.982593 6 35 L 6 18 z M 21.978516 23.013672 C 20.435152 23.049868 19 24.269284 19 25.957031 L 19 35.041016 C 19 37.291345 21.552344 38.713255 23.509766 37.597656 L 31.498047 33.056641 C 33.442844 31.951609 33.442844 29.044485 31.498047 27.939453 L 23.509766 23.398438 L 23.507812 23.398438 C 23.018445 23.120603 22.49297 23.001607 21.978516 23.013672 z M 21.982422 24.986328 C 22.158626 24.988232 22.342399 25.035052 22.521484 25.136719 L 30.511719 29.677734 C 31.220922 30.080703 31.220922 30.915391 30.511719 31.318359 L 22.519531 35.859375 C 21.802953 36.267773 21 35.808686 21 35.041016 L 21 25.957031 C 21 25.573196 21.201402 25.267385 21.492188 25.107422 C 21.63758 25.02744 21.806217 24.984424 21.982422 24.986328 z"></path>
                    </svg>
            </span>

            @if (request('q'))
              <h4 class="font-weight-light">{{__('general.no_results_found')}}</h4>
              <a href="{{ url('my/reels') }}" class="btn btn-primary btn-sm mt-3">
                <i class="bi-arrow-left mr-1"></i> {{ __('general.go_back') }}
              </a>
            @else
              <h4 class="font-weight-light">{{__('general.not_reels_created')}}</h4>

              @if ($settings->allow_reels)
              <a href="{{ url('create/reel') }}" class="btn btn-primary btn-sm mt-3">
                <i class="bi-plus"></i> {{ __('general.create_reel') }}
              </a>
              @endif
            @endif
          </div>
        @endif
        </div><!-- end col-md-6 -->

      </div>
    </div>
  </section>
@endsection
