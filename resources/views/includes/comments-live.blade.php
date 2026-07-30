  @foreach ($comments as $comment)
  <li class="chatlist mb-1" data="{{ $comment->id }}">
    <img src="{{Helper::getFile(config('path.avatar').$comment->user()->avatar)}}" alt="User" class="rounded-circle mr-1" width="20" height="20">
    <strong>{{ $comment->user()->username }}</strong>

    @if ($comment->user()->id == $live->user_id)
      <small class="badge badge-success">{{ __('general.creator') }}</small>
    @endif

    @if ($comment->user()->verified_id == 'yes' && $comment->user()->id != $live->user_id)
      <small class="verified">
           <i class="bi-patch-check-fill"></i>
         </small>
    @endif

    <p class="d-inline">
      {{ $comment->comment }}

      @if ($comment->joined)

        @if ($comment->user_id == auth()->id())
          {{ __('general.you_have_joined') }}
        @else
          {{ __('general.has_joined') }}
        @endif

      @endif

      @if ($comment->tip)
        {{ __('general.tipped') }} <span class="badge badge-pill badge-success tipped-live px-3"><i class="bi-coin mr-1"></i> {{ Helper::priceWithoutFormat($comment->earnings) }}</span>
      @endif

      @if ($comment->gift_id)
        @if (isset($comment->gift->id))
          <span class="d-block w-100">
            <img src="{{ url('public/img/gifts', $comment->gift->image) }}" width="100">
          </span>
        @endif

        <small class="d-block w-100">
          <i class="bi-gift mr-1"></i> <strong>{{ __('general.sent_a_gift_for') }} {{ Helper::priceWithoutFormat($comment->earnings) }}</strong>
        </small>
      @endif
    </p>
  </li>
  @endforeach
