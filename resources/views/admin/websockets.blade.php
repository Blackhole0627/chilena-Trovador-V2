@extends('admin.layout')

@section('content')
<h5 class="mb-4 fw-light">
  <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
  <i class="bi-chevron-right me-1 fs-6"></i>
  <span class="text-muted">Websockets</span>
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

          <form method="POST" action="{{ url('panel/admin/websockets') }}" enctype="multipart/form-data">
            @csrf

            <fieldset class="row mb-4">
              <legend class="col-form-label col-sm-2 pt-0 text-lg-end">
                {{ __('admin.status') }} 
              </legend>
              <div class="col-sm-10">
                <div class="form-check form-switch form-switch-md">
                  <input class="form-check-input" type="checkbox" name="websockets" @checked($settings->websockets) 
                  value="1" role="switch">
                </div>
              </div>
            </fieldset><!-- end row -->

            <div class="row mb-3">
              <label class="col-sm-2 col-form-label text-lg-end">Pusher App ID</label>
              <div class="col-sm-10">
                <input value="{{ env('PUSHER_APP_ID') }}" name="PUSHER_APP_ID" type="text" class="form-control">
                <p class="d-block m-0">
                  <a href="https://dashboard.pusher.com/apps" target="_blank" rel="noopener noreferrer">
                    https://dashboard.pusher.com/apps <i class="bi-box-arrow-up-right ms-1"></i>
                  </a>
                </p>
              </div>
            </div>
              <div class="row mb-3">
                <label class="col-sm-2 col-form-label text-lg-end">Pusher App Key</label>
                <div class="col-sm-10">
                  <input value="{{ env('PUSHER_APP_KEY') }}" name="PUSHER_APP_KEY" type="text" class="form-control">
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label text-lg-end">Pusher App Secret</label>
                <div class="col-sm-10">
                  <input value="{{ env('PUSHER_APP_SECRET') }}" name="PUSHER_APP_SECRET" type="text" class="form-control">
                </div>
              </div>

              <div class="row mb-3">
                <label class="col-sm-2 col-form-label text-lg-end">Pusher Cluster Zone</label>
                <div class="col-sm-10">
                  <input value="{{ env('PUSHER_APP_CLUSTER') }}" name="PUSHER_APP_CLUSTER" type="text" class="form-control">
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