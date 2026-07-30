@extends('layouts.app')

@section('title') {{ __('general.age_verification') }} -@endsection

@section('content')
<section class="section section-sm">
    <div class="container">
        <div class="row justify-content-center text-center mb-sm">
            <div class="col-lg-12 py-5">
                @if (session('error_verification'))
                  <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                			<span aria-hidden="true">×</span>
                			</button>
                            {{ session('error_verification') }}
                  </div>
                @endif

                <span class="mb-3" style="font-size: 80px;">
                    <i class="bi-person-bounding-box"></i>
                </span>
                <h2 class="mb-0 font-montserrat">
                    {{ __('general.age_verification') }}
                </h2>

                <p class="lead text-muted mt-0">

                    @if (auth()->user()->age_verification === 0 && auth()->user()->role != 'admin')
                        {{ __('general.age_verification_desc') }}

                        @elseif(auth()->user()->age_verification === 1 || auth()->user()->role === 'admin')
                        <div class="alert alert-success">
                            {{ __('general.age_verification_verified') }}
                        </div>

                        @elseif(auth()->user()->age_verification === 2)
                        <div class="alert alert-primary">
                            {{ __('general.age_verification_progress') }}
                        </div>

                        @elseif(auth()->user()->age_verification === 3)
                        <div class="alert alert-danger">
                            {!! __('general.error_age_verification', ['email' => '<strong>'.config('settings.email_admin').'</strong>']) !!}
                        </div>
                    @endif

                    @if (auth()->user()->age_verification === 0 && auth()->user()->role != 'admin')
                    <span class="d-block mt-3 w-100">
                        <a href="{{ route('age.start') }}" id="ageStart" class="btn btn-primary btn-arrow btn-arrow-sm px-4">
                            {{ __('general.verify_age') }}
                        </a>
                    </span>
                    @endif
                </p>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const link = document.getElementById('ageStart');

        if (link) {
            link.addEventListener('click', function(event) {
                link.style.pointerEvents = 'none';
                link.style.opacity = '0.5';

                // Append the spinner icon
                const spinner = document.createElement('i');
                spinner.className = 'spinner-border spinner-border-sm align-middle mr-1';
                link.prepend(spinner);
            });
        }
    });
</script>
@endsection