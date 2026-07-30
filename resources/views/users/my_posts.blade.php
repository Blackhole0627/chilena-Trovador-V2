@extends('layouts.app')

@section('title') {{__('general.my_posts')}} -@endsection

@section('content')
<section class="section section-sm">
    <div class="container">
      <div class="row justify-content-center text-center mb-sm">
        <div class="col-lg-8 py-5">
          <h2 class="mb-0 font-montserrat"><i class="feather icon-feather mr-2"></i> {{__('general.my_posts')}}</h2>
          <p class="lead text-muted mt-0">{{__('general.all_post_created')}}</p>
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

          @if ($posts->isNotEmpty())
          <div class="d-lg-flex d-block justify-content-between align-items-center mb-3 text-word-break">
            <form class="position-relative mr-3 w-100 mb-lg-0 mb-2" role="search" autocomplete="off" action="{{ url('my/posts') }}" method="get">
              <i class="bi bi-search btn-search bar-search"></i>
             <input type="text" minlength="3" required="" name="q" class="form-control pl-5" value="{{ request('q') }}" placeholder="{{ __('general.search') }}" aria-label="Search">
          </form>

            <div class="w-lg-100">
              <select class="form-control custom-select w-100 pr-4 filter">
                <option @selected(!request('sort')) value="{{ url('my/posts') }}">{{ __('general.all') }}</option>

                @if ($settings->allow_scheduled_posts)
                <option @selected(request('sort') == 'scheduled') value="{{ url('my/posts?sort=scheduled') }}">
                  {{ __('general.scheduled') }}
                </option> 
                @endif
                
                <option @selected(request('sort') == 'pending') value="{{ url('my/posts?sort=pending') }}">
                  {{ __('admin.pending') }}
                </option> 


                <option @selected(request('sort') == 'ppv') value="{{ url('my/posts?sort=ppv') }}">
                  {{ __('general.ppv') }}
                </option>
              </select>
            </div>
          </div>

          <div class="card shadow-sm mb-2">
          <div class="table-responsive">
            <table class="table table-striped m-0">
              <thead>
                <tr>
                  <th scope="col">{{__('admin.description')}}</th>
                  <th scope="col">{{__('admin.content')}}</th>
                  <th scope="col">{{__('admin.type')}}</th>
                  <th scope="col">{{ __('general.views') }}</th>
                  <th scope="col">{{__('general.price')}}</th>

                  @if ($settings->addon_crowdfund && $settings->allow_crowdfund)
                  <th scope="col">{{__('donations.funds_raised')}}</th>
                  @endif


                  <th scope="col">{{__('general.interactions')}}</th>
                  <th scope="col">{{__('admin.date')}}</th>
                  <th scope="col">{{__('admin.status')}}</th>
                </tr>
              </thead>

              <tbody>

                @foreach ($posts as $post)
                  <tr>
                    <td>
                    <a href="{{ url($post->creator->username, 'post').'/'.$post->id }}" target="_blank">
                      {{ str_limit($post->description, 20, '...') }} <i class="bi bi-box-arrow-up-right ml-1"></i>
                    </a>
                    </td>
                    <td>
                      @if ($post->media_count)
                      {{ $post->media_count }} {{trans_choice('general.files', $post->media_count )}}
                      @else
                      {{ __('general.text') }}
                      @endif
                    </td>
                    <td>
                      @if ($post->locked == 'yes')
                        <i class="feather icon-lock mr-1" title="{{__('users.content_locked')}}"></i>
                      @else
                        <i class="iconmoon icon-WorldWide mr-1" title="{{__('general.public')}}"></i>
                      @endif
                    </td>
                    <td>{{ $post->post_views }}</td>
                    <td>{{ Helper::amountFormatDecimal($post->price) }}</td>

                    @if ($settings->addon_crowdfund && $settings->allow_crowdfund)
                    <td>{{ Helper::amountFormatDecimal($post->funds_raised) }}</td>
                    @endif
                    
                    <td>
                      <i class="far fa-heart"></i> {{ $post->likes_count }} 
                      <i class="far fa-comment ml-1"></i> {{ ($post->comments_count + $post->replies_count) }}
                      <i class="feather icon-bookmark ml-1"></i> {{ $post->bookmarks_count }}
                    </td>
                    <td>{{Helper::formatDate($post->date)}}</td>
                    <td>
                      @if ($post->status == 'active')
                        <span class="badge badge-pill badge-success text-uppercase">{{__('general.active')}}</span>
                      @elseif($post->status == 'schedule')
                      <span class="badge badge-pill badge-info text-uppercase">{{__('general.scheduled')}}</span>
                        <a tabindex="0" role="button" data-container="body" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="{{ __('general.date_schedule') }} {{ Helper::formatDateSchedule($post->scheduled_date) }}">
                          <i class="far fa-question-circle"></i>
                        </a>
                        @else
                        <span class="badge badge-pill badge-warning text-uppercase">{{__('general.pending')}}</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          </div><!-- card -->

          @if ($posts->hasPages())
  			    	{{ $posts->onEachSide(0)->links() }}
  			    	@endif

        @else
          <div class="my-5 text-center">
            <span class="btn-block mb-3">
              <i class="feather icon-feather ico-no-result"></i>
            </span>

            @if (request('q'))
              <h4 class="font-weight-light">{{__('general.no_results_found')}}</h4>
              <a href="{{ url('my/posts') }}" class="btn btn-primary btn-sm mt-3">
                <i class="bi-arrow-left mr-1"></i> {{ __('general.go_back') }}
              </a>
            @else
              <h4 class="font-weight-light">{{__('general.not_post_created')}}</h4>
            @endif
          </div>
        @endif
        </div><!-- end col-md-6 -->

      </div>
    </div>
  </section>
@endsection
