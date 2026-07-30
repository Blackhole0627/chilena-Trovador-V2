@if ($stickers->count())
<div class="container">
  <div class="row">
		@foreach ($stickers as $sticker)
			<div class="col-3">
	      <span class="insertSticker c-pointer" data-url="{{ $sticker->url }}">
            <img src="{{ $sticker->url }}" width="70">
        </span>
	    </div>
		@endforeach
  </div>
</div>
@endif