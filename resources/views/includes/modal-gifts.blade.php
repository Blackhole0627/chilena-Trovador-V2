<div class="modal fade" id="giftsForm" tabindex="-1" role="dialog" aria-labelledby="modal-form" aria-hidden="true">
	<div class="modal-dialog modalGifts modal-dialog-scrollable" role="document">
		<div class="modal-content p-lg-3">
			<div class="modal-header border-bottom-0">
				<h6 class="modal-title">
                    <i class="bi-gift mr-1"></i> {{__('general.send_a_gift')}}
                    <small class="d-block w-100">{{ __('general.send_gift_desc_payment') }}</small>
                </h6>
				<button type="button" class="close close-inherit" data-dismiss="modal" aria-label="Close">
					<i class="bi bi-x-lg"></i>
				</button>
			  </div>

			<form method="post" style="display: contents;" action="{{url('send/gift')}}" id="formSendGift">
				@csrf

				@if (request()->route()->named('profile'))
					<input type="hidden" name="user_id" value="{{ $user->id }}" />
				@endif

				@if (request()->is('messages/*'))
					<input type="hidden" name="isMessage" value="1" />
					<input type="hidden" name="user_id" value="{{ $user->id }}" />
				@endif

				@if (request()->route()->named(['live', 'live.private']))
					<input type="hidden" name="isLive" value="1" />
					
					@if ($live)
						<input type="hidden" name="liveID" value="{{ $live->id }}"  />
						<input type="hidden" name="user_id" value="{{ $creator->id }}" />
					@endif

				@endif

			<div class="modal-body p-0 custom-scrollbar">
				<div class="card bg-white shadow border-0">
					<div class="card-body text-center">
                        <div class="btn-group-toggle btn-group-radio d-inline" data-toggle="buttons">
                        @foreach ($gifts as $gift)
                            <label class="btn btn-radio">
                              <input type="radio" required name="gift" value="{{ $gift->id }}" id="gift{{ $gift->id }}"> 
                              <img src="{{ url('public/img/gifts', $gift->image) }}" width="80">
                              <small class="d-block w-100 mt-1">
                                {{ Helper::formatPrice($gift->price, true) }}
                              </small>
                            </label>
                        @endforeach
                        </div>
					</div>
				</div>
			</div>

            <div class="modal-footer">

				@if (request()->is('messages/*') || request()->route()->named(['live', 'live.private']))
				<div class="form-group w-100">
					<input type="text" class="form-control" maxlength="50" name="message" placeholder="{{ __('general.write_short_message') }}">
				</div>
				@endif

                @if ($taxRatesCount != 0 && auth()->user()->isTaxable()->count())
                  <ul class="list-group w-100 list-group-flush border-dashed-radius">
                  	@foreach (auth()->user()->isTaxable() as $tax)
                  		<li class="list-group-item py-1 list-taxes">
                  	    <div class="row">
                  	      <div class="col">
                  	        <small>{{ $tax->name }} {{ $tax->percentage }}% {{ __('general.applied_price') }}</small>
                  	      </div>
                  	    </div>
                  	  </li>
                  	@endforeach
                  </ul>
                @endif

				<div class="alert alert-danger w-100 display-none" id="errorGift">
					<ul class="list-unstyled m-0" id="showErrorsGift"></ul>
				</div>

                <div class="text-center w-100">
                    <button type="submit" class="btn btn-primary px-5 giftBtn">
						<i></i> {{ __('general.send_gift') }}
					</button>
                </div>
              </div>
			</form>
		</div>
	</div>
</div><!-- End Modal new Message -->