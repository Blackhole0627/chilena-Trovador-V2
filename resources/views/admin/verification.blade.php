@extends('admin.layout')

@section('content')
	<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      <span class="text-muted">{{ __('admin.verification_requests') }} ({{$data->total()}})</span>
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
				<div class="card-body p-lg-4">

					@if ($data->isNotEmpty())
					<div class="d-lg-flex justify-content-lg-between align-items-center mb-2 w-100">
					<!-- form -->
					<form class="mt-lg-0 mt-2 position-relative" role="search" autocomplete="off" action="{{ url()->current() }}"
						method="get">
						<i class="bi bi-search btn-search bar-search"></i>
						<input type="text" name="q" class="form-control ps-5 w-auto" value="{{ $query }}" required minlength="2"  placeholder="{{ __('general.search') }}">
					</form><!-- form -->
					</div>
					@endif

					<div class="table-responsive p-0">
						<table class="table table-hover">
						 <tbody>

							@if ($data->isNotEmpty())
								 <tr>
									  <th class="active">ID</th>
										<th class="active">{{ trans('admin.user') }}</th>
										<th class="active">{{ trans('general.address') }}</th>
										<th class="active">{{ trans('general.city') }}</th>
										<th class="active">{{ trans('general.country') }}</th>
										<th class="active">{{ trans('general.zip') }}</th>
										<th class="active">{{ trans('general.image') }}</th>
										<th class="active">{{ trans('general.form_w9') }}</th>
										<th class="active">{{ trans('admin.date') }}</th>
										<th class="active">{{ trans('admin.actions') }}</th>
									</tr>

								@foreach ($data as $verify)
									<tr>
										<td>{{ $verify->id }}</td>
										<td>
											@if (! isset($verify->creator->username))
												<em>{{ trans('general.no_available') }}</em>
											@else
											<a href="{{ url($verify->creator->username) }}" target="_blank">{{ $verify->creator->name }}
												<i class="bi-box-arrow-up-right ms-1"></i>
											</a>
										@endif
										</td>
										<td>{{ $verify->address }}</td>
										<td>{{ $verify->city }}</td>
										<td>
											@if (! isset($verify->creator->username)
														|| isset($verify->creator->username)
														&& ! isset($verify->creator->country()->country_name)
														)
												<em>{{ trans('general.no_available') }}</em>
												@else
											{{ $verify->creator->country()->country_name }}
										@endif

										</td>
										<td>{{ $verify->zip }}</td>
										<td>
									<a href="{{ route('private.assets', ['type' => 'verification', 'filename' => $verify->image]) }}" class="glightbox" data-gallery="gallery{{$verify->id}}">
											{{ trans('admin.see_document_id') }} <i class="bi-arrows-fullscreen ms-1"></i>
										</a>

										@if ($verify->image_reverse)
											<a href="{{ route('private.assets', ['type' => 'verification', 'filename' => $verify->image_reverse]) }}" class="glightbox d-none" data-gallery="gallery{{$verify->id}}">
													{{ trans('admin.see_document_id') }} <i class="bi-arrows-fullscreen ms-1"></i>
												</a>
										@endif

										@if ($verify->image_selfie)
											<a href="{{ route('private.assets', ['type' => 'verification', 'filename' => $verify->image_selfie]) }}" class="glightbox d-none" data-gallery="gallery{{$verify->id}}">
													{{ trans('admin.see_document_id') }} <i class="bi-arrows-fullscreen ms-1"></i>
												</a>
										@endif
									</td>
										<td>
											@if ($verify->form_w9)
												<a href="{{ url('file/verification', $verify->form_w9) }}" target="_blank">
													{{ trans('general.form_w9') }} <i class="bi-box-arrow-up-right ms-1"></i>
												</a>
											@else
												<span class="text-muted"><em>{{ __('general.not_applicable') }}</em></span>
											@endif

										</td>
										<td>{{ Helper::formatDate($verify->created_at) }}</td>
									<td>

								@if ($verify->status == 'pending')

							<div class="d-flex">
								@if (isset($verify->creator->username))
								<form method="POST" action="{{ url('panel/admin/verification/members/approve', $verify->id).'/'.$verify->user_id }}" class="displayInline">
									@csrf
									<button type="submit" class="btn btn-success btn-sm rounded-pill actionApprove me-2" title="{{ trans('admin.approve') }}">
										<i class="bi-check2"></i>
									</button>
								</form>
							 @endif

							 <form method="POST" action="{{ url('panel/admin/verification/members/delete', $verify->id).'/'.$verify->user_id }}" class="displayInline">
								@csrf
								<button type="submit" class="btn btn-danger btn-sm rounded-pill actionDeleteVerification" title="{{ trans('admin.reject') }}">
									<i class="bi-x"></i>
								</button>
							</form>

									</div>

									 @else
										 <span class="rounded-pill badge bg-success">{{trans('admin.approved')}}</span>
									 @endif
									 </td>

									</tr><!-- /.TR -->
									@endforeach

									@else
										<h5 class="text-center p-5 text-muted fw-light m-0">{{ trans('general.no_results_found') }}

											@if (isset($query))
												<div class="d-block w-100 mt-2">
												<a href="{{url('panel/admin/verification/members')}}"><i class="bi-arrow-left me-1"></i> {{ __('auth.back') }}</a>
												</div>
											@endif
										</h5>
									@endif

								</tbody>
								</table>
							</div><!-- /.box-body -->

				 </div><!-- card-body -->
 			</div><!-- card  -->

		@if ($data->lastPage() > 1)
			{{ $data->links() }}
		@endif
 		</div><!-- col-lg-12 -->

	</div><!-- end row -->
</div><!-- end content -->
@endsection
