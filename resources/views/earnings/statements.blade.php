@extends('layouts.app')

@section('title') {{ __('general.monthly_statement') }} -@endsection

@section('content')
<section class="section section-sm">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 py-4">

        <h4 class="mb-4 font-montserrat"><i class="feather icon-file-text mr-2"></i> {{ __('general.monthly_statement') }}</h4>

        @if ($months->isEmpty())
          <div class="alert alert-light border" role="alert">
            <i class="feather icon-info mr-1"></i> {{ __('general.no_available') }}
          </div>
        @else
          <div class="card shadow-sm border-0">
            <div class="table-responsive">
              <table class="table mb-0 align-middle">
                <thead>
                  <tr>
                    <th>{{ __('general.date') }}</th>
                    <th class="text-right">{{ __('general.net') }}</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($months as $row)
                  <tr>
                    <td>{{ str_pad($row->m, 2, '0', STR_PAD_LEFT) }}/{{ $row->y }}</td>
                    <td class="text-right">{{ Helper::amountFormatDecimal($row->net) }} {{ $settings->currency_code }}</td>
                    <td class="text-right">
                      <a href="{{ url('earnings/' . $row->y . '/' . $row->m . '/pdf') }}" class="btn btn-sm btn-outline-primary">
                        <i class="feather icon-download mr-1"></i> PDF
                      </a>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif

      </div>
    </div>
  </div>
</section>
@endsection
