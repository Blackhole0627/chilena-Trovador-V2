@extends('layouts.app')

@section('title') {{__('general.video_call_settings')}} -@endsection

@section('content')
<section class="section section-sm">
    <div class="container">
      <div class="row justify-content-center text-center mb-sm">
        <div class="col-lg-8 py-5">
          <h2 class="mb-0 font-montserrat"><i class="bi-camera-video mr-2"></i> {{__('general.video_call_settings')}}</h2>
          <p class="lead text-muted mt-0">{{__('general.subtitle_video_call_settings')}}</p>
        </div>
      </div>
      <div class="row">

        @include('includes.cards-settings')

        <div class="col-md-6 col-lg-9 mb-5 mb-lg-0">

          @if (session('status'))
                  <div class="alert alert-success">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                			<span aria-hidden="true">×</span>
                			</button>

                    {{ session('status') }}
                  </div>
                @endif

          @include('errors.errors-forms')

          <form method="POST" action="{{ url()->current() }}">
            @csrf
                <div class="form-group mb-4">
                  <label class="w-100 ">{{__('general.price_video_call')}} *</label>
                  <div class="input-group mb-2">
                    
                    <div class="input-group-prepend">
                      <span class="input-group-text">{{$settings->currency_symbol}}</span>
                    </div>
                        <input value="{{ auth()->user()->price_video_call }}" class="form-control form-control-lg isNumber" required name="price_video_call" autocomplete="off" placeholder="{{__('general.price_video_call')}}" type="text">
                    </div>
                    <small class="btn-block">
                      * {{ __('general.minimum') }} {{ Helper::priceWithoutFormat($settings->video_call_min_price) }} - {{ __('general.maximum') }} {{ Helper::priceWithoutFormat($settings->video_call_max_price) }}

                      @if ($settings->wallet_format != 'real_money')
						<strong>({{Helper::equivalentMoney($settings->wallet_format)}})</strong>
					 @endif
                    </small>
                </div>

                <div class="form-group mb-4">
                    <label class="w-100 ">{{__('general.duration')}} *</label>
                <div class="w-100">
                    <div class="input-group mb-2">
                        <div class="input-group-prepend">
                        <span class="input-group-text"><i class="bi-clock"></i></span>
                        </div>
                    <select name="video_call_duration" class="form-control custom-select">
                        @for ($i = 5; $i <= $settings->video_call_max_duration; $i+=5)
                        <option @selected(auth()->user()->video_call_duration == $i) value="{{ $i }}">{{$i}} {{ __('general.minutes') }}</option>
                        @endfor
                        </select>
                    </div>
                    </div>
                </div><!-- End Row Form Group -->

                <button class="btn btn-1 btn-success btn-block buttonActionSubmit" type="submit">{{__('general.save_changes')}}</button>

          </form>
        </div><!-- end col-md-6 -->
      </div>
    </div>
  </section>
@endsection
