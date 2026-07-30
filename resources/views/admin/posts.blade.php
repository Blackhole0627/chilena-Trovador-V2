@extends('admin.layout')

@section('content')
	<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      <span class="text-muted">{{ __('general.posts') }} ({{$data->total()}})</span>
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

			<div class="card shadow-custom border-0">
				<div class="card-body p-lg-4">

					@if ($data->total() !=  0 && $data->count() != 0)
					<div class="d-lg-flex justify-content-lg-between align-items-center mb-2 w-100">
						<form action="{{ url('panel/admin/posts') }}" id="formSort" method="get">
							 <select name="sort" id="sort" class="form-select d-inline-block w-auto filter">
									<option @selected($sort == '') value="">{{ __('admin.sort_id') }}</option>
									<option @selected($sort == 'pending') value="pending">{{ __('admin.pending') }}</option>
								</select>
								</form><!-- form -->

								<!-- form -->
								<form class="mt-lg-0 mt-2 position-relative" role="search" autocomplete="off" action="{{ url()->current() }}"
									method="get">
									<i class="bi bi-search btn-search bar-search"></i>
									<input type="text" name="q" class="form-control ps-5 w-auto" value="{{ $query ?? '' }}" required minlength="1"  placeholder="{{ __('general.search') }} {{ __('general.by') }} (ID, {{ __('admin.description') }})">
								</form><!-- form -->
						</div>
						@endif

					<div class="table-responsive p-0">
						<table class="table table-hover">
						 <tbody>

							@if ($data->count() !=  0)
								 <tr>
									  <th class="active">ID</th>
										<th class="active">{{ __('admin.description') }}</th>
										<th class="active">{{ __('admin.content') }}</th>
										<th class="active">{{ __('admin.type') }}</th>
										<th class="active">{{ __('general.creator') }}</th>
										<th class="active">{{ __('admin.date') }}</th>
										<th class="active">{{ __('admin.status') }}</th>
										<th class="active">{{ __('admin.actions') }}</th>
									</tr>

								@foreach ($data as $post)

									@php
										$allFiles = $post->media()->groupBy('type')->get();
									@endphp

									<tr>
										<td>{{ $post->id }}</td>
										<td>{{ str_limit($post->description, 40, '...') }}</td>

										<td>
											@if ($allFiles->count() != 0)
												@foreach ($allFiles as $media)

													@if ($media->type == 'image')
														<i class="far fa-image"></i>
													@endif

													@if ($media->type == 'video')
														<i class="far fa-play-circle"></i>
													@endif

													@if ($media->type == 'music')
														<i class="fa fa-microphone"></i>
														@endif

														@if ($media->type == 'file')
													<i class="far fa-file-archive"></i>
													@endif

													@if ($media->type == 'epub')
													<i class="fas fa-book-open"></i>
													@endif

												@endforeach

											@else
												<i class="fa fa-font"></i>
											@endif
										</td>

										<td>{{ $post->locked == 'yes' ? __('users.content_locked') : __('general.public') }}</td>
										<td>
											@if (isset($post->user()->username))
												<a href="{{url($post->user()->username)}}" target="_blank">
													{{$post->user()->username}} <i class="fa fa-external-link-square-alt"></i>
												</a>
											@else
												<em>{{ __('general.no_available') }}</em>
											@endif

											</td>
										<td>{{ Helper::formatDate($post->date) }}

											<a href="#" class="ms-1" data-bs-toggle="modal" data-bs-target="#editDate{{ $post->id }}" >
												<i class="bi-pencil-square"></i>
											</a>
										</td>
										<td>
											@switch($post->status)
												@case('active')
												<span class="rounded-pill badge bg-success">
													{{ __('admin.active') }}
												</span>
													@break

												@case('pending')
													<span class="rounded-pill badge bg-warning">
													{{ __('admin.pending') }}
													</span>
													@break

												@case('encode')
												<span class="rounded-pill badge bg-info">
													{{ __('general.encode') }}
													</span>
													@break

												@case('schedule')
												<span class="rounded-pill badge bg-info">
													{{ __('general.scheduled') }}
													</span>
													<a tabindex="0" role="button" data-bs-container="body" data-bs-toggle="popover" data-bs-trigger="focus" data-bs-placement="top" data-bs-content="{{ __('general.date_schedule') }} {{ Helper::formatDateSchedule($post->scheduled_date) }}">
														<i class="far fa-question-circle"></i>
													  </a>
													@break
											@endswitch
											</td>
										<td>
											<div class="d-flex">
											@if (isset($post->user()->username) && $post->status != 'encode')

											<a href="{{ url($post->user()->username, 'post').'/'.$post->id }}" target="_blank" class="btn btn-success btn-sm rounded-pill me-2" title="{{ __('admin.view') }}">
												<i class="bi-eye"></i>
											</a>

											@if ($post->status == 'active')
												<button type="button" class="btn btn-primary btn-sm padding-btn rounded-pill me-2" data-bs-toggle="modal" data-bs-target="#likeExtras{{ $post->id }}" >
													<i class="bi-plus-lg"></i>
												</button>
											@endif
										@endif

											@if ($post->status == 'pending')
											<form method="POST" action="{{ url('panel/admin/posts/approve/'.$post->id) }}" class="displayInline">
												@csrf
												<button type="submit" class="btn btn-success btn-sm padding-btn rounded-pill me-2 actionApprovePost">
													{{ __('admin.approve') }}
												</button>
											</form>
											@endif

											<form method="POST" action="{{ url('panel/admin/posts/delete/'.$post->id) }}" class="displayInline">
												@csrf
												@if ($post->status == 'active' || $post->status == 'encode' || $post->status == 'schedule')
													<button type="submit" class="btn btn-danger btn-sm padding-btn rounded-pill actionDelete">
														<i class="bi-trash-fill"></i>
													</button>
												@else
													<button type="submit" class="btn btn-danger btn-sm padding-btn rounded-pill actionDeletePost">
														{{ __('general.reject') }}
													</button>
												@endif
											</form>
									 </div>
									</td>
									</tr><!-- /.TR -->


									<div class="modal fade" id="likeExtras{{ $post->id }}" tabindex="-1" role="dialog" aria-hidden="true">
									<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header border-bottom-0">
										<h5 class="modal-title">{{__('general.increase_number_likes')}}</h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
										</div>
										<div class="modal-body">
										<form method="POST" action="{{ url('panel/admin/likes/extras/add', $post->id) }}" enctype="multipart/form-data">
											@csrf
											<select name="likes" class="form-select">
												@for ($i = 10; $i <= 500; $i+=10)
													<option value="{{$i}}">{{$i}}</option>
													
												@endfor												
											</select>
										<div class="modal-footer border-0">
											<button type="submit" class="btn btn-dark rounded-pill float-right"><i></i> {{__('users.save')}}</button>
										</div>
										</form>
									</div><!-- modal-body -->
									</div><!-- modal-content -->
									</div><!-- modal-dialog -->
								</div><!-- modal -->

								<div class="modal fade" id="editDate{{ $post->id }}" tabindex="-1" role="dialog" aria-hidden="true">
									<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header border-bottom-0">
										<h5 class="modal-title">{{__('general.edit_date_post')}}</h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
										</div>
										<div class="modal-body">
										<form method="POST" action="{{ url('panel/admin/edit/date', $post->id) }}" enctype="multipart/form-data">
											@csrf
											<select name="days" class="form-select">
												<option value="2">{{ __('general.two_days_ago') }}</option>
												<option value="30">{{ __('general.one_month_ago') }}</option>
												<option value="90">{{ __('general.three_months_ago') }}</option>
												<option value="180">{{ __('general.six_months_ago') }}</option>
												<option value="365">{{ __('general.one_year_ago') }}</option>
											</select>
										<div class="modal-footer border-0">
											<button type="submit" class="btn btn-dark rounded-pill float-right"><i></i> {{__('users.save')}}</button>
										</div>
										</form>
									</div><!-- modal-body -->
									</div><!-- modal-content -->
									</div><!-- modal-dialog -->
								</div><!-- modal -->


									@endforeach

									@else
										<h5 class="text-center p-5 text-muted fw-light m-0">{{ __('general.no_results_found') }}

											@if (isset($query) || $sort != '')
												<div class="d-block w-100 mt-2">
													<a href="{{url()->current()}}"><i class="bi-arrow-left me-1"></i> {{ __('auth.back') }}</a>
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
			{{ $data->appends(['sort' => $sort, 'q' => $query])->onEachSide(0)->links() }}
		@endif
 		</div><!-- col-lg-12 -->

	</div><!-- end row -->
</div><!-- end content -->
@endsection
