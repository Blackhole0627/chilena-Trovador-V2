@extends('admin.layout')

@section('content')
	<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      <a class="text-reset" href="{{ url('panel/admin/withdrawals') }}">{{ __('general.withdrawals') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      #{{$data->id}}
  </h5>

<div class="content">
	<div class="row">

		<div class="col-lg-12">

      @include('errors.errors-forms')

			<div class="card shadow-custom border-0">
				<div class="card-body p-lg-5">

             <dl class="row">
              <dt class="col-sm-2 text-lg-end">{{ __('admin.user') }}</dt>
              <dd class="col-sm-10">
                @if (isset($data->user()->username))
                    <a href="{{ url($data->user()->username) }}" target="_blank">
                    {{ $data->user()->username }} <i class="bi-box-arrow-up-right"></i>
                  </a>
                        @else
                            {{ __('general.no_available') }}
                    @endif
                </dd>

              @if ($data->gateway == 'PayPal')
              <dt class="col-sm-2 text-lg-end">{{ __('admin.paypal_account') }}</dt>
              <dd class="col-sm-10">{{$data->account}}</dd>
              @elseif ($data->gateway == 'Payoneer')
              <dt class="col-sm-2 text-lg-end">{{ __('general.payoneer_account') }}</dt>
              <dd class="col-sm-10">{{$data->account}}</dd>
              @elseif ($data->gateway == 'Zelle')
              <dt class="col-sm-2 text-lg-end">{{ __('general.zelle_account') }}</dt>
              <dd class="col-sm-10">{{$data->account}}</dd>
              @elseif ($data->gateway == 'Western Union')
              <dt class="col-sm-2 text-lg-end">{{ __('auth.full_name') }}</dt>
              <dd class="col-sm-10">{{ $data->user()->name ?? __('general.no_available') }}</dd>
              <dt class="col-sm-2 text-lg-end">{{ __('general.country') }}</dt>
              <dd class="col-sm-10">{{ isset($data->user()->countries_id) != '' ? $data->user()->country()->country_name : __('general.no_available')}}</dd>
              <dt class="col-sm-2 text-lg-end">{{ __('general.document_id') }}</dt>
              <dd class="col-sm-10">{{$data->account}}</dd>
              @elseif ($data->gateway == 'Bitcoin')
              <dt class="col-sm-2 text-lg-end">{{ __('general.bitcoin_wallet') }}</dt>
              <dd class="col-sm-10">{{$data->account}}</dd>
              @elseif ($data->gateway == 'Mercado Pago')
              <dt class="col-sm-2 text-lg-end">Alias MP</dt>
              <dd class="col-sm-10">{{$data->account}}</dd>
              <dt class="col-sm-2 text-lg-end">No. CVU</dt>
              <dd class="col-sm-10">{{$data->user()->cvu ?? __('general.no_available') }}</dd>
              @else
              <dt class="col-sm-2 text-lg-end">{{ __('general.bank_details') }}</dt>
              <dd class="col-sm-10">{!!Helper::checkText($data->account)!!}</dd>
            @endif

            <dt class="col-sm-2 text-lg-end">{{ __('admin.amount') }}</dt>
            <dd class="col-sm-10">{{Helper::amountFormatDecimal($data->amount) }}</dd>

            <dt class="col-sm-2 text-lg-end">{{ __('admin.date') }}</dt>
            <dd class="col-sm-10">{{date('d M, Y', strtotime($data->date))}}</dd>

            <dt class="col-sm-2 text-lg-end">{{ __('admin.status') }}</dt>
            <dd class="col-sm-10"><span class="badge bg-{{ $data->status == 'paid' ? 'success' : 'warning' }}">{{ $data->status == 'paid' ? __('general.paid') : __('general.pending_to_pay') }}</span></dd>

            @if ($data->status == 'paid')
            <dt class="col-sm-2 text-lg-end">{{ __('general.date_paid') }}</dt>
            <dd class="col-sm-10">{{date('d M, Y', strtotime($data->date_paid))}}</dd>
          @endif

            </dl>

            @if ($data->status == 'pending' && isset($data->user()->username))
						<div class="row mb-3">
		          <div class="col-sm-10 offset-sm-2">
                <form method="POST" action="{{ url('panel/admin/withdrawals/paid', $data->id) }}" enctype="multipart/form-data" class="d-inline me-2">
                  @csrf
		            <button type="submit" class="btn btn-success"><i class="bi-check2 me-1"></i> {{ __('general.mark_paid') }}</button>
              </form>

              {{-- Delete --}}
            <button class="btn btn-danger pull-right margin-separator" data-bs-toggle="modal" data-bs-target="#reject" type="button">
              <i class="bi-x-lg me-1"></i> {{ __('general.reject') }}
            </button>
		          </div>
		        </div>
          @endif

				 </div><!-- card-body -->
 			</div><!-- card  -->
 		</div><!-- col-lg-12 -->

	</div><!-- end row -->

  <div class="modal fade" id="reject" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
		  <div class="modal-content">
			<div class="modal-header border-bottom-0">
			  <h5 class="modal-title">{{ __('admin.reason') }}</h5>
			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
			  <form method="POST" action="{{ route('withdrawals.reject', $data->id) }}" enctype="multipart/form-data">
				@csrf
				<div class="row">
				  <div class="col-sm-12">
					<textarea class="form-control" name="reason" required maxlength="250" rows="4"></textarea>
					<small class="w-100 d-block">{{ __('general.info_reject') }}</small>
				  </div>
				</div><!-- end row -->
	  
			  <div class="modal-footer border-0">
				<button type="submit" class="btn btn-dark float-right">{{ __('auth.send') }}</button>
			  </div>
			</form>
		  </div><!-- modal-body -->
		  </div><!-- modal-content -->
		</div><!-- modal-dialog -->
	  </div><!-- modal -->
</div><!-- end content -->
@endsection
