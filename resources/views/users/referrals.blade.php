@extends('layouts.app')

@section('title') {{__('general.referrals')}} -@endsection

@section('content')
<section class="section section-sm">
    <div class="container">
      <div class="row justify-content-center text-center mb-sm">
        <div class="col-lg-8 py-5">
          <h2 class="mb-0 font-montserrat"><i class="bi bi-person-plus mr-2"></i> {{__('general.referrals')}}</h2>

          @if ($settings->referral_system == 'on')
            <p class="lead text-muted mt-0">
              {{__('general.referrals_welcome_desc', ['percentage' => auth()->user()->custom_profit_referral ?: $settings->percentage_referred])}}
              <small class="d-block">
                @if ($settings->referral_transaction_limit <> 'unlimited')
                  * {{ trans_choice('general.total_transactions_per_referral', $settings->referral_transaction_limit, ['percentage' => auth()->user()->custom_profit_referral ?: $settings->percentage_referred, 'total' => $settings->referral_transaction_limit]) }}
                @else
                  * {{__('general.total_transactions_referral_unlimited', ['percentage' => auth()->user()->custom_profit_referral ?: $settings->percentage_referred])}}
                @endif

              </small>
            </p>

            <button class="d-none copy-url" id="copyLink" data-clipboard-text="{{ url(auth()->user()->username.'?ref='.auth()->user()->id) }}"></button>
            <span>
              <span class="text-muted">{{ __('general.your_referral_link') }}</span>

              <span class="text-break"><strong>{{ url(auth()->user()->username.'?ref='.auth()->user()->id) }}</strong></span>

              <button class="btn btn-link e-none p-1 text-decoration-none" data-toggle="tooltip" data-placement="top" title="{{__('general.copy_link')}}" onclick="$('#copyLink').trigger('click')">
  							<i class="far fa-clone"></i>
  						</button>
            </span>
          @else
          <div class="alert alert-danger mt-3">
          <span class="alert-inner--text">
            <i class="fa fa-exclamation-triangle mr-1"></i> {{ __('general.referral_system_disabled') }}
          </span>
        </div>
          @endif

        </div>
      </div>
      <div class="row">

        <div class="col-lg-12 mb-5 mb-lg-0">

          <div class="content">
            <div class="row">
              <div class="col-lg-3 mb-2">
                <div class="card">
                  <div class="card-body">
                    <h5>
                      <i class="fas fa-hand-holding-usd mr-2 text-primary icon-dashboard"></i> {{Helper::amountFormatDecimal(auth()->user()->balance)}}
                    </h5>
                    <small>{{ __('general.balance') }}</small>
                    @if (auth()->user()->balance >= $settings->amount_min_withdrawal)
                    <a href="{{ url('settings/withdrawals')}}" class="link-border color-link"> {{ __('general.make_withdrawal') }}</a>

                    @else
                    <a href="javascript:;" class="link-border color-link text-muted" data-toggle="tooltip" title="{{__('general.amount_min_withdrawal')}} {{Helper::amountWithoutFormat($settings->amount_min_withdrawal)}} {{$settings->currency_code}}">
                       {{ __('general.make_withdrawal') }}
                      </a>
                  @endif
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-3 mb-2">
                <div class="card">
                  <div class="card-body">
                    <h5><i class="fas fa-users mr-2 text-primary icon-dashboard"></i> {{ number_format(auth()->user()->referrals()->count()) }}</h5>
                    <small>{{ __('general.total_registered_users') }}</small>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-3 mb-2">
                <div class="card">
                  <div class="card-body">
                    <h5><i class="fa fa-receipt mr-2 text-primary icon-dashboard"></i> {{ number_format(auth()->user()->referralTransactions()->count()) }}</h5>
                    <small>{{ __('general.total_transactions') }}</small>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-3 mb-2">
                <div class="card">
                  <div class="card-body">
                    <h5><i class="fas fa-hand-holding-usd mr-2 text-primary icon-dashboard"></i> {{ Helper::amountFormatDecimal(auth()->user()->referralTransactions()->sum('earnings')) }}</h5>
                    <small>{{ __('general.earnings_total') }}</small>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-12 mt-3 py-4">
                 <div class="card">
                   <div class="card-body">
                     <h4 class="mb-4">{{ __('admin.transactions') }}</h4>

                     <div class="table-responsive">
                       <table class="table table-striped m-0">
                         <thead>
                           <tr>
                             <th scope="col">{{__('admin.type')}}</th>
                             <th scope="col">{{__('admin.date')}}</th>
                             <th scope="col">{{__('general.earnings')}}</th>
                           </tr>
                         </thead>

                         <tbody>

                        @if ($transactions->count() != 0)
                           @foreach ($transactions as $referred)
                             <tr>
                               <td>{{ __('general.'.$referred->type) }}</td>
                               <td>{{ Helper::formatDate($referred->created_at) }}</td>
                               <td>{{ Helper::amountFormatDecimal($referred->earnings) }}</td>
                             </tr>
                           @endforeach

                         @else
                           <tr>
                             <td colspan="12" class="text-center">{{ __('general.no_transactions_yet') }}</td>
                           </tr>
                          @endif

                         </tbody>
                       </table>
                     </div>
                   </div>
                 </div><!-- card -->

                 @if ($transactions->hasPages())
         			    	{{ $transactions->links() }}
         			    	@endif

              </div><!-- col-lg-12 -->

            </div><!-- end row -->
          </div><!-- end content -->

        </div><!-- end col-md-6 -->

      </div>
    </div>
  </section>
@endsection
