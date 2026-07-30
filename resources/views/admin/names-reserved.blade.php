@extends('admin.layout')

@section('content')
	<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      <span class="text-muted">{{ __('general.names_reserved') }}</span>

			<a href="javascript:void(0);" class="btn btn-sm btn-dark float-lg-end mt-1 mt-lg-0" data-bs-toggle="modal" data-bs-target="#addName">
				<i class="bi-plus-lg"></i> {{ __('general.add_new') }}
			</a>
  </h5>

<div class="content">
	<div class="row">

		<div class="col-lg-12">

            @include('errors.errors-forms')

			@if (session('success_message'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="bi bi-check2 me-1"></i>	{{ session('success_message') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                  <i class="bi bi-x-lg"></i>
                </button>
                </div>
              @endif

			<div class="card shadow-custom border-0">
				<div class="card-body p-lg-4">

                    <div class="alert alert-secondary py-2">
                        <i class="bi-info-circle me-2"></i> {{ __('general.alert_names_reserved') }}
                    </div>

					<div class="table-responsive p-0">
						<table class="table table-hover">
						 <tbody>

							@if ($reserveds->count() !=  0)
								 <tr>
									  <th class="active">ID</th>
										<th class="active">{{ __('admin.name') }}</th>
										<th class="active">{{ __('admin.actions') }}</th>
									</tr>

								@foreach ($reserveds as $reserved)
									<tr>
										<td>{{ $reserved->id }}</td>
										<td>{{ $reserved->name }}</td>

										<td>
											<div class="d-flex">

                                            @if ($reserved->editable)
											<form method="POST" action="{{ url('panel/admin/reserved/delete', $reserved->id) }}" class="d-inline-block align-top">
												@csrf
												<button type="submit" class="btn btn-danger rounded-pill btn-sm actionDelete">
													<i class="bi-trash-fill"></i>
												</button>
											</form>
                                            @else
                                             {{ __('general.no_available') }}
                                            @endif
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

		@if ($reserveds->lastPage() > 1)
			{{ $reserveds->links() }}
		@endif
 		</div><!-- col-lg-12 -->

	</div><!-- end row -->

    <div class="modal fade" id="addName" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header border-bottom-0">
          <h5 class="modal-title">{{trans('general.add_new')}}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" action="{{ url('panel/admin/add/name/reserved') }}" enctype="multipart/form-data">
            @csrf

            <input type="text" name="name" required class="form-control" placeholder="{{ __('admin.name') }}">
  
          <div class="modal-footer border-0">
            <button type="submit" class="btn btn-dark rounded-pill float-right"><i></i> {{__('users.save')}}</button>
          </div>
        </form>
      </div><!-- modal-body -->
      </div><!-- modal-content -->
    </div><!-- modal-dialog -->
  </div><!-- modal -->
</div><!-- end content -->

</div><!-- end content -->
@endsection
