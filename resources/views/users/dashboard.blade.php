@extends('layouts.app')

@section('title') {{__('admin.dashboard')}} -@endsection

@section('content')
<section class="section section-sm">
    <div class="container">
      <div class="row justify-content-center text-center mb-sm">
        <div class="col-lg-8 py-5">
          <h2 class="mb-0 font-montserrat"><i class="bi bi-speedometer2 mr-2"></i> {{__('admin.dashboard')}}</h2>
          <p class="lead text-muted mt-0">{{__('general.dashboard_desc')}}</p>
        </div>
      </div>
      <div class="row">

        <div class="col-lg-12 mb-5 mb-lg-0">

          <div class="content">
            <div class="row">
              <div class="col-lg-4 mb-2">
                <div class="card">
                  <div class="card-body overflow-hidden position-relative">
                    <h4><i class="fas fa-hand-holding-usd mr-2 text-primary icon-dashboard"></i> {{ Helper::amountFormatDecimal($earningNetUser) }}</h4>
                    <small>{{ __('admin.earnings_net') }}</small>

                    <span class="icon-wrap icon--dashboard"><i class="fas fa-hand-holding-usd"></i></span>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-4 mb-2">
                <div class="card">
                  <div class="card-body overflow-hidden position-relative">
                    <h4><i class="fas fa-wallet mr-2 text-primary icon-dashboard"></i> {{ Helper::amountFormatDecimal(auth()->user()->balance) }}</h4>
                    <small>{{ __('general.balance') }}
                      @if (auth()->user()->balance >= $settings->amount_min_withdrawal)
                      <a href="{{ url('settings/withdrawals')}}" class="link-border"> {{ __('general.make_withdrawal') }}</a>
                    @endif
                    </small>

                    <span class="icon-wrap icon--dashboard"><i class="fas fa-wallet"></i></span>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-4 mb-2">
                <div class="card">
                  <div class="card-body overflow-hidden position-relative">
                    <h4><i class="fas fa-users mr-2 text-primary icon-dashboard"></i> <span title="{{$subscriptionsActive}}">{{ Helper::formatNumber($subscriptionsActive) }}</span></h4>
                    <small>{{ __('general.subscriptions_active') }}</small>

                    <span class="icon-wrap icon--dashboard"><i class="fas fa-users"></i></span>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-4 mb-2">
                <div class="card">
                  <div class="card-body overflow-hidden position-relative">
                    <h4><i class="bi-arrow-repeat mr-2 text-primary icon-dashboard-2"></i> {{ Helper::amountFormatDecimal($earningNetSubscriptions) }}</h4>
                    <small>{{ __('general.earnings_net_subscriptions') }}</small>

                    <span class="icon-wrap icon--dashboard"><i class="bi-arrow-repeat"></i></span>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-4 mb-2">
                <div class="card">
                  <div class="card-body overflow-hidden position-relative">
                    <h4><i class="bi-coin mr-2 text-primary icon-dashboard-2"></i> {{ Helper::amountFormatDecimal($earningNetTips) }}</h4>
                    <small>{{ __('general.earnings_net_tips') }}</small>

                    <span class="icon-wrap icon--dashboard"><i class="bi-coin"></i></span>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-4 mb-2">
                <div class="card">
                  <div class="card-body overflow-hidden position-relative">
                    <h4><i class="bi-lock mr-2 text-primary icon-dashboard-2"></i> {{ Helper::amountFormatDecimal($earningNetPPV) }}</h4>
                    <small>{{ __('general.earnings_net_ppv') }}</small>

                    <span class="icon-wrap icon--dashboard"><i class="bi-lock"></i></span>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-4 mb-2">
                <div class="card">
                  <div class="card-body overflow-hidden position-relative">
                    <h6 class="{{$stat_revenue_today > 0 ? 'text-success' : 'text-danger' }} text-revenue">
                      {{ Helper::amountFormatDecimal($stat_revenue_today) }}
                      <small class="float-right ml-2">
                        <i class="bi bi-question-circle text-muted" data-toggle="tooltip" data-placement="top" title="{{ __('general.compared_yesterday') }}"></i>
                      </small>
                        {!! Helper::PercentageIncreaseDecrease($stat_revenue_today, $stat_revenue_yesterday) !!}
                    </h6>
                    <small>{{ __('general.revenue_today') }}</small>

                    <span class="icon-wrap icon--dashboard"><i class="bi bi-graph-up-arrow"></i></span>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-4 mb-2">
                <div class="card">
                  <div class="card-body overflow-hidden position-relative">
                    <h6 class="{{$stat_revenue_week > 0 ? 'text-success' : 'text-danger' }} text-revenue">
                      {{ Helper::amountFormatDecimal($stat_revenue_week) }}
                      <small class="float-right ml-2">
                        <i class="bi bi-question-circle text-muted" data-toggle="tooltip" data-placement="top" title="{{ __('general.compared_last_week') }}"></i>
                      </small>
                        {!! Helper::PercentageIncreaseDecrease($stat_revenue_week, $stat_revenue_last_week) !!}
                    </h6>
                    <small>{{ __('general.revenue_week') }}</small>

                    <span class="icon-wrap icon--dashboard"><i class="bi bi-graph-up-arrow"></i></span>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-4 mb-2">
                <div class="card">
                  <div class="card-body overflow-hidden position-relative">
                    <h6 class="{{$stat_revenue_month > 0 ? 'text-success' : 'text-danger' }} text-revenue">
                      {{ Helper::amountFormatDecimal($stat_revenue_month) }}
                      <small class="float-right ml-2">
                        <i class="bi bi-question-circle text-muted" data-toggle="tooltip" data-placement="top" title="{{ __('general.compared_last_month') }}"></i>
                      </small>
                        {!! Helper::PercentageIncreaseDecrease($stat_revenue_month, $stat_revenue_last_month) !!}
                    </h6>
                    <small>{{ __('general.revenue_month') }}</small>

                    <span class="icon-wrap icon--dashboard"><i class="bi bi-graph-up-arrow"></i></span>
                  </div>
                </div><!-- card 1 -->
              </div><!-- col-lg-4 -->

              <div class="col-lg-12 mt-3 py-4">
                 <div class="card">
                   <div class="card-body">

                    <div class="d-lg-flex d-block justify-content-between align-items-center mb-4">
                      <h4 class="mb-4 mb-lg-0">{{ __('general.earnings') }}</h4>

                     <select class="custom-select mb-4 mb-lg-0 w-auto d-block filterEarnings">
                      <option selected="" value="month">{{ __('general.this_month') }}</option>
                      <option value="last-month">{{ __('general.last_month') }}</option>
                      <option value="year">{{ __('general.this_year') }}</option>       
                    </select>
                    </div>
                     
                     <div class="d-block position-relative" style="height: 350px">
                        <div class="blocked display-none" id="loadChart">
                          <span class="d-flex justify-content-center align-items-center text-center w-100 h-100">
                           <i class="spinner-border spinner-border-sm mr-2 text-primary"></i> {{ __('general.loading') }}
                          </span>
                      </div>
                      <canvas id="Chart"></canvas>
                    </div>
                   </div>
                 </div>
              </div>
          
            <div class="col-md-6 mb-5 mb-lg-0">
              <div class="card shadow-sm">
                <div class="card-body pb-0">
                  <h6>{{ __('admin.recent_subscriptions') }}</h6>
                </div>
                <div class="table-responsive">
                  <table class="table table-striped m-0">
                    <thead>
                      <tr>
                        <th scope="col">{{__('general.subscriber')}}</th>
                        <th scope="col">{{__('admin.date')}}</th>
                        <th scope="col">{{__('admin.status')}}</th>
                      </tr>
                    </thead>
            
                    <tbody>
            
                      @foreach ($subscriptions as $subscription)
                      <tr>
                        <td>
                          @if (! isset($subscription->subscriber->username))
                          {{ __('general.no_available') }}
                          @else
                          <a href="{{url($subscription->subscriber->username)}}" class="mr-1">
                            <img src="{{Helper::getFile(config('path.avatar').$subscription->subscriber->avatar)}}" width="35"
                              height="35" class="rounded-circle mr-2">
            
                            {{$subscription->subscriber->hide_name == 'yes' ? $subscription->subscriber->username :
                            $subscription->subscriber->name}}
                          </a>
            
                          <a href="{{url('messages/'.$subscription->subscriber->id, $subscription->subscriber->username)}}"
                            title="{{__('general.message')}}">
                            <i class="feather icon-send mr-1 mr-lg-0"></i>
                          </a>
                          @endif
                        </td>
                        <td>{{Helper::formatDate($subscription->created_at)}}</td>
                        </td>            
                        <td>
                          @if ($subscription->stripe_id == ''
                          && strtotime($subscription->ends_at) > strtotime(now()->format('Y-m-d H:i:s'))
                          && $subscription->cancelled == 'no'
                          || $subscription->stripe_id != '' && $subscription->stripe_status == 'active'
                          || $subscription->stripe_id == '' && $subscription->free == 'yes'
                          )
                          <span class="badge badge-pill badge-success text-uppercase">{{__('general.active')}}</span>
                          @elseif ($subscription->stripe_id != '' && $subscription->stripe_status == 'incomplete')
                          <span class="badge badge-pill badge-warning text-uppercase">{{__('general.incomplete')}}</span>
                          @else
                          <span class="badge badge-pill badge-danger text-uppercase">{{__('general.cancelled')}}</span>
                          @endif
                        </td>
                      </tr>
                      @endforeach

                      @if ($subscriptions->isEmpty())
                      <tr>
                        <td colspan="12" class="text-center">{{ __('users.not_subscribers') }}</td>
                      </tr>
                      @endif

                    </tbody>
                  </table>
                </div>

                @if ($subscriptions->isNotEmpty())
                <div class="card-footer">
                  <a href="{{ url('my/subscribers') }}" class="text-muted font-weight-medium d-flex align-items-center justify-content-center arrow">
                    {{ __('general.view_all') }}
                  </a>
                </div>
                @endif
              </div><!-- card -->
            </div><!-- end col-md-6 -->

            <div class="col-md-6 mb-5 mb-lg-0">
              <div class="card shadow-sm">
                <div class="card-body pb-0">
                  <h6>{{ __('general.payments_received') }}</h6>
                </div>
                <div class="table-responsive">
                  <table class="table table-striped m-0">
                    <thead>
                      <tr>
                        <th scope="col">{{__('admin.date')}}</th>
                        <th scope="col">{{__('admin.amount')}}</th>
                        <th scope="col">{{__('admin.type')}}</th>
                        <th scope="col">{{__('general.earnings')}}</th>
                      </tr>
                    </thead>
            
                    <tbody>
            
                      @foreach ($transactions as $transaction)
                      <tr>
                        <td>{{ Helper::formatDate($transaction->created_at) }}</td>
                        <td>{{ Helper::amountFormatDecimal($transaction->amount) }}</td>
                        <td>
                          {{ __('general.'.$transaction->type) }}

                          @if (isset($transaction->gift->id) && request()->is('my/payments/received'))
                          <span class="d-block mt-2">
                            <img src="{{ url('public/img/gifts', $transaction->gift->image) }}" width="25">
                          </span>
                          @endif
                      </td>
                      <td>
                        {{ Helper::amountFormatDecimal($transaction->earning_net_user) }}
  
                        @if ($transaction->percentage_applied)
                          <a tabindex="0" role="button" data-container="body" data-toggle="popover" data-trigger="focus" data-placement="top" data-content="{{trans('general.percentage_applied')}} {{ $transaction->percentage_applied }} {{trans('general.platform')}} @if ($transaction->direct_payment) ({{ __('general.direct_payment') }}) @endif">
                            <i class="far fa-question-circle"></i>
                          </a>
                        @endif
                        
                      </td>
                      </tr>
                      @endforeach

                      @if ($transactions->isEmpty())
                      <tr>
                        <td colspan="12" class="text-center">{{ __('general.not_payment_received') }}</td>
                      </tr>
                      @endif

                    </tbody>
                  </table>
                </div>

                @if ($transactions->isNotEmpty())
                <div class="card-footer">
                  <a href="{{ url('my/payments/received') }}" class="text-muted font-weight-medium d-flex align-items-center justify-content-center arrow">
                    {{ __('general.view_all') }}
                  </a>
                </div>
                @endif

              </div><!-- card -->
            </div><!-- end col-md-6 -->

            </div><!-- end row -->
          </div><!-- end content -->

        </div><!-- end col-lg-12 -->

      </div>
    </div>
  </section>
@endsection

@section('javascript')
  <script src="{{ asset('public/js/Chart.min.js') }}"></script>

  <script type="text/javascript">

function decimalFormat(nStr)
{
  @if ($settings->decimal_format == 'dot')
	 $decimalDot = '.';
	 $decimalComma = ',';
	 @else
	 $decimalDot = ',';
	 $decimalComma = '.';
	 @endif

   switch ('{{$settings->currency_position}}') {
     case 'left':
     var currency_symbol_left = '{{$settings->currency_symbol}}';
     var currency_symbol_right = '';
     break;

     case 'left_space':
     var currency_symbol_left = '{{$settings->currency_symbol}} ';
     var currency_symbol_right = '';
     break;

     case 'right':
     var currency_symbol_right = '{{$settings->currency_symbol}}';
     var currency_symbol_left = '';
     break;

     case 'right_space':
     var currency_symbol_right = ' {{$settings->currency_symbol}}';
     var currency_symbol_left = '';
     break;

     default:
     var currency_symbol_right = '{{$settings->currency_symbol}}';
     var currency_symbol_left = '';
     break;
   }// End switch

    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? $decimalDot + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + $decimalComma + '$2');
    }
    return currency_symbol_left + x1 + x2 + currency_symbol_right;
  }

  function transparentize(color, opacity) {
			var alpha = opacity === undefined ? 0.5 : 1 - opacity;
			return Color(color).alpha(alpha).rgbString();
		}

  var init = document.getElementById("Chart").getContext('2d');

  const gradient = init.createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, '{{$settings->color_default}}');
                    gradient.addColorStop(1, '{{$settings->color_default}}2b');

  const lineOptions = {
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        hitRadius: 5,
                        pointHoverBorderWidth: 3
                    }

  var ChartArea = new Chart(init, {
      type: 'line',
      data: {
          labels: [{!!$label!!}],
          datasets: [{
              label: '{{__('general.earnings')}}',
              backgroundColor: gradient,
              borderColor: '{{ $settings->color_default}}',
              data: [{!!$data!!}],
              borderWidth: 2,
              fill: true,
              lineTension: 0.4,
              ...lineOptions
          }]
      },
      options: {
          scales: {
              yAxes: [{
                  ticks: {
                    min: 0, // it is for ignoring negative step.
                     display: true,
                      maxTicksLimit: 8,
                      padding: 10,
                      beginAtZero: true,
                      callback: function(value, index, values) {
                          return '@if($settings->currency_position == 'left'){{ $settings->currency_symbol }}@elseif ($settings->currency_position == 'left_space'){{ $settings->currency_symbol }} @endif' + value + '@if($settings->currency_position == 'right'){{ $settings->currency_symbol }}@elseif ($settings->currency_position == 'right_space'){{ ' '.$settings->currency_symbol }}@endif';
                      }
                  }
              }],
              xAxes: [{
                gridLines: {
                  display:false
                },
                display: true,
                ticks: {
                  maxTicksLimit: 15,
                  padding: 5,
                }
              }]
          },
          tooltips: {
            mode: 'index',
            intersect: false,
            reverse: true,
            backgroundColor: '#000',
            xPadding: 16,
            yPadding: 16,
            cornerRadius: 4,
            caretSize: 7,
              callbacks: {
                  label: function(t, d) {
                      var xLabel = d.datasets[t.datasetIndex].label;
                      var yLabel = t.yLabel == 0 ? decimalFormat(t.yLabel) : decimalFormat(t.yLabel.toFixed(2));
                      return xLabel + ': ' + yLabel;
                  }
              },
          },
          hover: {
            mode: 'index',
            intersect: false
          },
          legend: {
              display: false
          },
          responsive: true,
          maintainAspectRatio: false
      }
  });

//<<======= Get data Earnings Dashboard Creator
$(document).on('change','.filterEarnings', function(e) {
  var range = $(this).val();

  $(this).blur();
  
  $('#loadChart').show();

  $.ajax({
    url: URL_BASE+'/get/earnings/creator/' + range,
    success: function(data) {
      // Empty any previous chart data
      ChartArea.data.labels = [];
      ChartArea.data.datasets[0].data = [];
      
      ChartArea.data.labels = data.labels;
      ChartArea.data.datasets.forEach((dataset) => {
          dataset.data = data.datasets;
      });

      // Re-render the chart
      ChartArea.update();

      $('#loadChart').hide();
    }
  }).fail(function(jqXHR, ajaxOptions, thrownError) {
	  $('.popout').addClass('popout-error').html(error_reload_page).slideDown('500').delay('5000').slideUp('500');
    $('#loadChart').hide();
  });

});
  </script>
  @endsection
