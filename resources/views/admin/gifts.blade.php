@extends('admin.layout')

@section('content')
	<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      <span class="text-muted">{{ __('general.gifts') }}</span>

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
                    <th class="active">{{ __('general.image') }}</th>
                     <th class="active">{{ __('general.price') }}</th>
                     <th class="active">{{ __('admin.status') }}</th>
                     <th class="active">{{ __('admin.actions') }}</th>
                   </tr>

                 @foreach ($data as $gift)
                   <tr>
                    <td><img src="{{ url('public/img/gifts', $gift->image) }}" width="50"></td>
                     <td>{{ $gift->price }}</td>
                     <td>
                        <span class="badge bg-{{ $gift->status ? 'success' : 'secondary'}}">
                            {{ $gift->status ? __('general.enabled') : __('general.disabled')}}
                        </span>
                    </td>
                     
                     <td>
                        <div class="d-flex">
                            <a href="{{ route('gifts.edit', ['gift' => $gift->id]) }}" class="btn btn-success rounded-pill btn-sm me-2">
                                <i class="bi-pencil"></i>
                            </a>

                            <form method="POST" action="{{ route('gifts.destroy', ['gift' => $gift->id]) }}" accept-charset="UTF-8" class="d-inline-block align-top">
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

 @include('admin.add-gift')

@endsection
