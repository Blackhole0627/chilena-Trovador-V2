@extends('admin.layout')

@section('content')
<h5 class="mb-4 fw-light">
  <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
  <i class="bi-chevron-right me-1 fs-6"></i>
  <span class="text-muted">Cron Job</span>
</h5>

<div class="content">
  <div class="row">

    <div class="col-lg-12">
      <div class="card shadow-custom border-0">
        <div class="card-body p-lg-5">
            <div class="row mb-3">
              <label class="col-sm-2 col-form-label text-lg-end">{{ __('general.command') }}</label>
              <div class="col-sm-10">
                @if (auth()->user()->isSuperAdmin())
                <input value="{{ '/usr/local/bin/php ' .base_path('artisan schedule:run >> /dev/null 2>&1') }}" id="command" readonly type="text" class="form-control">
                <small class="w-100 d-block text-muted">* {{ __('general.command_only_cpanel') }}</small>
                @else
                <input value="*************************************************" readonly type="text" class="form-control">
                <small class="w-100 d-block text-muted"><i class="bi-exclamation-triangle-fill me-1 text-warning"></i> {{ __('general.only_super_admin_command') }}</small>
                @endif
              </div>
            </div>

            @if (auth()->user()->isSuperAdmin())
            <div class="row mb-3">
                <div class="col-sm-10 offset-sm-2">
                  <button type="button" id="copyCommand" class="btn btn-dark mt-3 px-5">{{ __('general.copy') }}</button>
                </div>
              </div>
              @endif
        </div><!-- card-body -->
      </div><!-- card  -->
    </div><!-- col-lg-12 -->

  </div><!-- end row -->
</div><!-- end content -->
@endsection

@if (auth()->user()->isSuperAdmin())
    @section('javascript')
    <script>
        // JavaScript
        const commandInput = document.getElementById('command');
        const copyButton = document.getElementById('copyCommand');

        commandInput.addEventListener('click', function() {
            this.select();
        });

        copyButton.addEventListener('click', function() {
            commandInput.select();
            navigator.clipboard.writeText(commandInput.value);

            copyButton.innerHTML = '{{ __('general.copied') }}';
            copyButton.classList.remove('btn-dark');
            copyButton.classList.add('btn-success');
            setTimeout(function() {
                copyButton.innerHTML = '{{ __('general.copy') }}';
                copyButton.classList.remove('btn-success');
                copyButton.classList.add('btn-dark');
            }, 2000);
        });
    </script>
    @endsection
@endif