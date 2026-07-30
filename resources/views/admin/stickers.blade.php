@extends('admin.layout')

@section('content')
	<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      <span class="text-muted">{{ __('Stickers') }}</span>

			<a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#addGiftModal" class="btn btn-sm btn-dark float-lg-end mt-1 mt-lg-0">
				<i class="bi-plus-lg me-1"></i> {{ __('general.add_new') }}
			</a>
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

				<div class="card-body p-lg-4">

					<div class="table-responsive p-0">
						<table class="table table-hover">
						 <tbody>

               @if ($data->count() !=  0)
                  <tr>
                    <th class="active">{{ __('general.url_sticker') }}</th>
                     <th class="active">{{ __('Sticker') }}</th>
                     <th class="active">{{ __('admin.actions') }}</th>
                   </tr>

                 @foreach ($data as $sticker)
                   <tr>
                     <td>{{ str_limit($sticker->url, 100, '...') }}</td>
                    <td><img src="{{ $sticker->url }}" width="50"></td>
                     
                     <td>
                        <div class="d-flex">
                            <form method="POST" action="{{ route('stickers.destroy', ['id' => $sticker->id]) }}" accept-charset="UTF-8" class="d-inline-block align-top">
                                @csrf
                                <button class="btn btn-danger rounded-pill btn-sm actionDelete" type="button">
                                  <i class="bi-trash-fill"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                   </tr><!-- /.TR -->
                   @endforeach

                    @else
                        <h5 class="text-center p-5 text-muted fw-light m-0">{{ __('general.no_results_found') }}</h5>
                    @endif

                        </tbody>
                        </table>
                    </div><!-- /.box-body -->
            </div><!-- card-body -->
        </div><!-- card  -->

        @if ($data->lastPage() > 1)
        {{ $data->onEachSide(0)->links() }}
        @endif
    </div><!-- col-lg-12 -->
	</div><!-- end row -->
</div><!-- end content -->

<div class="modal" tabindex="-1" id="addGiftModal">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header border-0">
            <h5 class="modal-title">
               {{ __('general.add_new') }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <!-- form start -->
            <form method="POST" action="{{ route('stickers.store') }}" enctype="multipart/form-data" files="true">
               @csrf
               <div class="mb-4">
                  <input type="text" required class="form-control" value="" name="url"
                     maxlength="255" placeholder="{{ __('general.url_sticker') }}">
               </div>

               <div class="box-footer text-center">
                  <button type="submit" class="btn btn-lg btn-dark w-100 rounded-pill buttonActionSubmit">
                     <i></i> {{ __('admin.save') }}
                  </button>
               </div>
            </form>
         </div>
      </div>
   </div>
</div>

@endsection
