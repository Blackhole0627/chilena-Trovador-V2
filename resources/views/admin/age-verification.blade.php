@extends('admin.layout')

@section('css')
<link href="{{ asset('public/js/select2/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('public/js/select2/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<h5 class="mb-4 fw-light">
  <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
  <i class="bi-chevron-right me-1 fs-6"></i>
  <span class="text-muted">{{ __('general.age_verification') }}</span>
</h5>

<div class="content">
  <div class="row">

    <div class="col-lg-12">

      @if (session('success_message'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="bi bi-check2 me-1"></i>	{{ session('success_message') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                  <i class="bi bi-x-lg"></i>
                </button>
                </div>
              @endif

      @include('errors.errors-forms')

      <div class="card shadow-custom border-0">
        <div class="card-body p-lg-5">

          <form method="POST" action="{{ url()->current() }}" enctype="multipart/form-data">
            @csrf

            <fieldset class="row mb-4">
              <legend class="col-form-label col-sm-2 pt-0 text-lg-end">
                {{ __('admin.status') }} 
              </legend>
              <div class="col-sm-10">
                <div class="form-check form-switch form-switch-md">
                  <input class="form-check-input" type="checkbox" name="age_verification_status" @checked($settings->age_verification_status) 
                  value="1" role="switch">
                </div>
              </div>
            </fieldset><!-- end row -->

            <fieldset class="row mb-4">
              <legend class="col-form-label col-sm-2 pt-0 text-lg-end">
                {{ __('general.show_modal_age_verification') }} 
              </legend>
              <div class="col-sm-10">
                <div class="form-check form-switch form-switch-md">
                  <input class="form-check-input" type="checkbox" name="show_modal_age_verification" @checked($settings->show_modal_age_verification) 
                  value="1" role="switch">
                </div>
              </div>
            </fieldset><!-- end row -->

            <div class="row mb-5">
            <label class="col-sm-2 col-form-labe text-lg-end">Age verification provider</label>
            <div class="col-sm-10">
            <select name="age_verification_provider" class="form-select">
                <option @selected($settings->age_verification_provider == 'yoti') value="yoti">Yoti</option>
                <option @selected($settings->age_verification_provider == 'didit') value="didit">Didit</option>
            </select>
            </div>
        </div>

        <div class="row mb-3">
        <label class="col-sm-2 col-form-labe text-lg-end">{{ __('general.countries') }}</label>
        <div class="col-sm-10">
          <select name="age_verification_countries[]" multiple class="form-select ageVerificationCountries">
            @php
              $ageVerificationCountries = explode(',', $settings->age_verification_countries);
            @endphp

            @foreach (Countries::orderBy('country_name')->get() as $country)
            <option @if (in_array($country->country_code, $ageVerificationCountries)) selected="selected" @endif
              value="{{$country->country_code}}">{{ $country->country_name }}</option>
            @endforeach
          </select>
        </div>
      </div>

        <hr />

            <div class="row mb-3">
              <label class="col-sm-2 col-form-labe text-lg-end">{{ __('general.threshold_age_verification') }}</label>
              <div class="col-sm-10">
                <select name="threshold_age_verification" class="form-select">
                @for ($i = 19; $i <= 25; $i+=1)
                  <option @selected($settings->threshold_age_verification == $i) value="{{ $i }}">{{$i}} {{ __('general.years') }}</option>
                @endfor
                </select>
                <small class="form-text text-muted">{{ __('general.threshold_age_verification_desc') }}</small>
              </div>
          </div>

            <div class="row mb-3">
              <label class="col-sm-2 col-form-label text-lg-end">Yoti Client SDK ID</label>
              <div class="col-sm-10">
                <input value="{{ $settings->age_verification_yoti_sdk_id }}" name="age_verification_yoti_sdk_id" type="password" class="form-control">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-2 col-form-label text-lg-end">Yoti API Key</label>
              <div class="col-sm-10">
                <input value="{{ $settings->age_verification_yoti_api_key }}" name="age_verification_yoti_api_key" type="password" class="form-control">
              </div>
            </div>

            <hr />

            <div class="row mb-3">
              <label class="col-sm-2 col-form-label text-lg-end">Didit API Key</label>
              <div class="col-sm-10">
                <input value="{{ $settings->age_verification_didit_api_key }}" name="age_verification_didit_api_key" type="password" class="form-control">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-2 col-form-label text-lg-end">Didit Webhook Secret</label>
              <div class="col-sm-10">
                <input value="{{ $settings->age_verification_didit_webhook_secret }}" name="age_verification_didit_webhook_secret" type="password" class="form-control">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-2 col-form-label text-lg-end">Didit Workflow ID</label>
              <div class="col-sm-10">
                <input value="{{ $settings->age_verification_didit_workflow_id }}" name="age_verification_didit_workflow_id" type="password" class="form-control">
              </div>
            </div>
                      
            <div class="row mb-3">
              <div class="col-sm-10 offset-sm-2">
                <button type="submit" class="btn btn-dark mt-3 px-5">{{ __('admin.save') }}</button>
              </div>
            </div>

          </form>

        </div><!-- card-body -->
      </div><!-- card  -->
    </div><!-- col-lg-12 -->

  </div><!-- end row -->
</div><!-- end content -->
@endsection


@section('javascript')
  <script>
  $('.ageVerificationCountries').select2({
  tags: false,
  tokenSeparators: [','],
  placeholder: '{{ __('general.countries') }}',
});
</script>
@endsection