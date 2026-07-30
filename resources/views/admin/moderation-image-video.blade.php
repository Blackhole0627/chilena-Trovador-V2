@extends('admin.layout')

@section('content')
<h5 class="mb-4 fw-light">
  <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
  <i class="bi-chevron-right me-1 fs-6"></i>
  <span class="text-muted">{{ __('general.moderation_image_video') }}</span>
</h5>

<div class="content">
  <div class="row">

    <div class="col-lg-12">

      @if (session('success_message'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check2 me-1"></i> {{ session('success_message') }}

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
                  <input class="form-check-input" type="checkbox" name="moderation_status" @checked($settings->moderation_status) 
                  value="1" role="switch">
                </div>
              </div>
            </fieldset><!-- end row -->

            <div class="row mb-3">
              <label class="col-sm-2 col-form-label text-lg-end">API User</label>
              <div class="col-sm-10">
                <input value="{{ $settings->sightengine_api_user }}" name="sightengine_api_user" type="password" class="form-control">
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-sm-2 col-form-label text-lg-end">API Secret</label>
              <div class="col-sm-10">
                <input value="{{ $settings->sightengine_api_api_secret }}" name="sightengine_api_api_secret" type="password" class="form-control">
                <small class="w-100 d-block mt-1"><i class="bi-exclamation-triangle-fill me-1 text-warning"></i> Important: To moderate videos you must have a paid plan <a href="https://sightengine.com/pricing" target="_blank">https://sightengine.com/pricing</a></small>
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
