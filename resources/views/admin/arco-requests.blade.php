@extends('admin.layout')

@section('content')
<h5 class="mb-4 fw-light">
  <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
  <i class="bi-chevron-right me-1 fs-6"></i>
  <span class="text-muted">{{ __('general.arco_requests') }}</span>
</h5>

<div class="content">
  @if (session('success_message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check2 me-1"></i> {{ session('success_message') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
  @endif

  <div class="card shadow-custom border-0">
    <div class="card-body p-lg-4">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>{{ __('general.username') }}</th>
              <th>{{ __('general.arco_type') }}</th>
              <th>{{ __('general.arco_details') }}</th>
              <th>{{ __('general.date') }}</th>
              <th>{{ __('admin.status') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($requests as $r)
            <tr>
              <td>{{ $r->id }}</td>
              <td>{{ optional($r->user())->username ?? '-' }}</td>
              <td>{{ __('general.arco_' . $r->type) }}</td>
              <td class="small" style="max-width:260px">{{ $r->details ?: '-' }}</td>
              <td class="small">{{ Helper::formatDate($r->created_at) }}</td>
              <td>
                <form method="post" action="{{ url('panel/admin/arco-requests', $r->id) }}" class="d-flex">
                  @csrf
                  <select name="status" class="form-select form-select-sm w-auto me-2">
                    @foreach (['pending', 'in_progress', 'completed', 'rejected'] as $st)
                      <option value="{{ $st }}" @if ($r->status == $st) selected @endif>{{ __('general.arco_status_' . $st) }}</option>
                    @endforeach
                  </select>
                  <button class="btn btn-sm btn-dark" type="submit">{{ __('admin.save') }}</button>
                </form>
              </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('general.no_available') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $requests->links() }}
    </div>
  </div>
</div>
@endsection
