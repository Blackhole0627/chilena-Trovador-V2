@extends('admin.layout')

@section('content')
<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
    <i class="bi-chevron-right me-1 fs-6"></i>
    <a class="text-reset" href="{{ url('panel/admin/gifts') }}">{{ __('general.gifts') }}</a>
    <i class="bi-chevron-right me-1 fs-6"></i>
    <span class="text-muted">{{ __('admin.edit') }}</span>
  </h5>

<div class="content">
	<div class="row">

		<div class="col-lg-12">

			@if (session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="bi bi-check2 me-1"></i>	{{ session('success') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                  <i class="bi bi-x-lg"></i>
                </button>
                </div>
              @endif

              @include('errors.errors-forms')

			<div class="card shadow-custom border-0">
				<div class="card-body p-lg-5">

					 <form method="POST" action="{{ route('gifts.update', ['gift' => $gift->id]) }}" enctype="multipart/form-data">
						 @csrf

                         <div class="row mb-3">
                            <label class="col-sm-2 col-form-label text-lg-end">{{ __('general.image') }}</label>
                            <div class="col-lg-5 col-sm-10">
                          <div class="d-block mb-2">
                            <img src="{{url('/public/img/gifts', $gift->image)}}" style="width:100px">
                          </div>
          
                          <div class="input-group mb-1">
                            <input name="image" type="file" class="form-control custom-file rounded-pill">
                          </div>
                          <small class="d-block">{{ __('general.recommended_size') }} 200x200 px (PNG, SVG, GIF)</small>
                            </div>
                          </div>

                          <div class="row mb-3">
                            <label class="col-sm-2 col-form-label text-lg-end">{{ __('general.price') }}</label>
                            <div class="col-sm-10">
                                <input value="{{ $gift->price }}" name="price" type="text" class="form-control isNumber" placeholder="{{ __('general.price') }} ({{ __('general.minimum') }} 0.50)" autocomplete="off">
                            </div>
                        </div>

					<fieldset class="row mb-3">
			         <legend class="col-form-label col-sm-2 pt-0 text-lg-end">{{ __('admin.status') }}</legend>
			         <div class="col-sm-10">
			           <div class="form-check form-switch form-switch-md">
			            <input class="form-check-input" type="checkbox" name="status" @checked($gift->status) value="1" role="switch">
			          </div>
			         </div>
			       </fieldset><!-- end row -->

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
