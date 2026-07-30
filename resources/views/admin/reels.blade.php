@extends('admin.layout')

@section('css')
<link href="{{ asset('public/js/plyr/plyr.css')}}?v={{$settings->version}}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
	<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
	  <span class="text-muted">{{ __('general.reels') }} ({{$data->total()}})</span>
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
					<div class="table-responsive p-0">
						<table class="table table-hover">
						 <tbody>

							@if ($data->count() !=  0)
								 <tr>
                                        <th class="active">ID</th>
									    <th class="active">{{__('admin.title')}}</th>
                                        <th class="active">{{__('general.creator')}}</th>
                                        <th class="active">{{__('general.type')}}</th>
                                        <th class="active">{{__('general.interactions')}}</th>
                                        <th class="active">{{__('admin.date')}}</th>
                                        <th class="active">{{__('admin.status')}}</th>
                                        <th class="active">{{__('admin.actions')}}</th>
									</tr>

								@foreach ($data as $reel)
									<tr>
                                        <td>{{ $reel->id }}</td>
										<td class="text-break">{{ $reel->title ? str_limit($reel->title, 40, '...') : __('general.no_available') }}</td>
                                        <td>
                                            @if (isset($reel->user->username))
                                                <a href="{{url($reel->user->username)}}" target="_blank">
                                                    {{ '@' . $reel->user->username}} <i class="fa fa-external-link-square-alt"></i>
                                                </a>
                                            @else
                                                <em>{{ __('general.no_available') }}</em>
                                            @endif
                                        </td>
										<td>
											@if ($reel->type == 'private')
                                                {{__('general.private')}}
                                            @else
                                                {{__('general.public')}}
                                            @endif
										</td>

										<td>
											<i class="far fa-heart"></i> {{ $reel->likes }} 
                                            <i class="far fa-comment ms-1"></i> {{ ($reel->comments_count) }}
                                            <i class="bi-eye ms-1"></i> {{ $reel->views }}
											</td>
										<td>{{ Helper::formatDate($reel->created_at) }}</td>
										<td>
											@if ($reel->status == 'active')
                                                <span class="rounded-pill badge bg-success">{{__('general.active')}}</span>
                                            @elseif($reel->status == 'encode')
                                            <span class="rounded-pill badge bg-info">{{__('general.encode')}}</span>
                                                @else
                                                <span class="rounded-pill badge bg-warning">{{__('general.pending')}}</span>
                                            @endif
									    </td>

                                        <td>
                                        <div class="d-flex">

                                            @if ($reel->status == 'active')
                                            <a href="{{ Helper::getFile(config('path.reels').$reel->media->name) }}" class="btn btn-success btn-sm rounded-pill me-2 glightbox" data-gallery="gallery{{$reel->media->id}}">
                                                <i class="bi-eye"></i>
                                            </a>
                                            @endif

                                            <form action="{{ route('reels.destroy', $reel->id) }}" method="POST" class="displayInline">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm padding-btn rounded-pill actionDelete">
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
@endsection

@section('javascript')
<script src="{{ asset('public/js/plyr/plyr.min.js') }}?v={{$settings->version}}"></script>
<script src="{{ asset('public/js/plyr/plyr.polyfilled.min.js') }}?v={{$settings->version}}"></script>
@endsection