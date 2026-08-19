<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    * { font-family: "DejaVu Sans", sans-serif; }
    body { font-size: 12px; color: #333; margin: 0; }
    .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 18px; }
    .header h2 { margin: 0 0 4px; font-size: 18px; }
    .header p { margin: 2px 0; color: #666; }
    table.detail { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.detail th, table.detail td { border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; }
    table.detail th { background: #f5f5f5; text-align: left; }
    .text-right { text-align: right; }
    .totals { width: 45%; margin-top: 20px; margin-left: 55%; border-collapse: collapse; }
    .totals td { padding: 5px 8px; font-size: 12px; }
    .totals tr.grand td { border-top: 2px solid #333; font-weight: bold; }
    .footer { margin-top: 30px; font-size: 10px; color: #999; text-align: center; }
  </style>
</head>
<body>

  <div class="header">
    <h2>{{ $settings->title }}</h2>
    <p>{{ __('general.monthly_statement') }}</p>
    <p>{{ '@' . $user->username }} &nbsp;&middot;&nbsp; {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}</p>
  </div>

  <table class="detail">
    <thead>
      <tr>
        <th>{{ __('general.date') }}</th>
        <th>{{ __('general.type') }}</th>
        <th>{{ __('general.from') }}</th>
        <th class="text-right">{{ __('general.amount') }}</th>
        <th class="text-right">{{ __('general.platform') }}</th>
        <th class="text-right">{{ __('general.net') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($transactions as $t)
      <tr>
        <td>{{ Helper::formatDate($t->created_at) }}</td>
        <td>{{ __('general.' . $t->type) }}</td>
        <td>{{ optional($t->user())->username ?? '-' }}</td>
        <td class="text-right">{{ Helper::amountFormatDecimal($t->amount) }}</td>
        <td class="text-right">{{ Helper::amountFormatDecimal($t->amount - $t->earning_net_user) }}</td>
        <td class="text-right">{{ Helper::amountFormatDecimal($t->earning_net_user) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <table class="detail" style="margin-top: 18px;">
    <thead>
      <tr>
        <th>{{ __('general.type') }}</th>
        <th class="text-right">{{ __('general.amount') }}</th>
        <th class="text-right">{{ __('general.platform') }}</th>
        <th class="text-right">{{ __('general.net') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($transactions->groupBy('type') as $type => $group)
      <tr>
        <td>{{ __('general.' . $type) }} ({{ $group->count() }})</td>
        <td class="text-right">{{ Helper::amountFormatDecimal($group->sum('amount')) }}</td>
        <td class="text-right">{{ Helper::amountFormatDecimal($group->sum('amount') - $group->sum('earning_net_user')) }}</td>
        <td class="text-right">{{ Helper::amountFormatDecimal($group->sum('earning_net_user')) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <table class="totals">
    <tr>
      <td>{{ __('general.amount') }}</td>
      <td class="text-right">{{ Helper::amountFormatDecimal($gross) }} {{ $settings->currency_code }}</td>
    </tr>
    <tr>
      <td>{{ __('general.platform') }}</td>
      <td class="text-right">{{ Helper::amountFormatDecimal($commission) }} {{ $settings->currency_code }}</td>
    </tr>
    <tr class="grand">
      <td>{{ __('general.net') }}</td>
      <td class="text-right">{{ Helper::amountFormatDecimal($net) }} {{ $settings->currency_code }}</td>
    </tr>
  </table>

  <div class="footer">
    {{ $settings->title }} &nbsp;&middot;&nbsp; {{ __('general.monthly_statement') }} {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $year }}
  </div>

</body>
</html>
