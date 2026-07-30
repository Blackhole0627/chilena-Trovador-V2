@extends('admin.layout')

@section('content')
	<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      <a class="text-reset" href="{{ url('panel/admin/deposits') }}">{{ __('general.deposits') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      <span class="text-muted">{{ __('general.deposits') }} #{{$data->id}}</span>
  </h5>

<div class="content">
	<div class="row">

		<div class="col-lg-12">

      @include('errors.errors-forms')

			<div class="card shadow-custom border-0">
				<div class="card-body p-lg-5">

					<dl class="row">

					 <dt class="col-sm-2 text-lg-end">ID</dt>
					 <dd class="col-sm-10">{{$data->id}}</dd>

					 <dt class="col-sm-2 text-lg-end">{{ __('admin.transaction_id') }}</dt>
					 <dd class="col-sm-10">{{$data->txn_id != 'null' ? $data->txn_id : __('general.not_available')}}</dd>

					 <dt class="col-sm-2 text-lg-end">{{ __('auth.full_name') }}</dt>
					 <dd class="col-sm-10">{{$data->user()->name ?? __('general.no_available')}}</dd>

					 <dt class="col-sm-2 text-lg-end">{{ __('general.image') }}</dt>
					 <dd class="col-sm-10">
						 <a class="glightbox" href="{{ route('private.assets', ['type' => 'deposits', 'filename' => $data->screenshot_transfer]) }}" data-gallery="gallery{{$data->id}}">
							 {{ __('admin.view') }} <i class="bi-arrows-fullscreen"></i>
						 </a>
					 </dd>

					 <dt class="col-sm-2 text-lg-end">{{ __('auth.email') }}</dt>
					 <dd class="col-sm-10">{{$data->user()->email ?? __('general.no_available')}}</dd>

					 <dt class="col-sm-2 text-lg-end">{{ __('admin.amount') }}</dt>
					 <dd class="col-sm-10"><strong class="text-success">{{App\Helper::amountFormat($data->amount)}}</strong></dd>

					 <dt class="col-sm-2 text-lg-end">{{ __('general.payment_gateway') }}</dt>
					 <dd class="col-sm-10">{{ $data->payment_gateway == 'Bank Transfer' ? __('general.bank_transfer') : $data->payment_gateway}}</dd>

					 <dt class="col-sm-2 text-lg-end">{{ __('admin.date') }}</dt>
					 <dd class="col-sm-10">{{date($settings->date_format, strtotime($data->date))}}</dd>

				 </dl><!-- row -->

				 @if ($data->status == 'pending')

					 <div class="row mb-3">
	 					<div class="col-sm-10 offset-sm-2">

				@if (isset($data->user()->name))
					{{-- Approve Donation --}}
					<form method="POST" action="{{ route('approve.deposits') }}" class="d-inline">
						@csrf
						<input type="hidden" name="id" value="{{ $data->id }}">
						<input type="submit" value="{{ __('general.approve') }}" class="btn btn-success pull-right me-2">
					</form>
				@endif

				{{-- Delete Deposit --}}
				<form method="POST" action="{{ route('delete.deposits') }}" class="d-inline" id="formDeleteDeposits">
					@csrf
					<input type="hidden" name="id" value="{{ $data->id }}">
					<button type="submit" class="btn btn-danger pull-right margin-separator actionDelete">
						<i class="bi-trash me-2"></i>{{ __('general.delete') }}
					</button>
				</form>

				</div>
			</div>

			@endif



				 </div><!-- card-body -->
 			</div><!-- card  -->
 		</div><!-- col-lg-12 -->

	</div><!-- end row -->
</div><!-- end content -->
@endsection
