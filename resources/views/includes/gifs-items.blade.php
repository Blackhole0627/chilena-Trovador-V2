@if ($gifs->count())
<div class="container">
  <div class="row">
		<input class="form-control mb-2 search-gif" type="text" name="q" autocomplete="off" placeholder="{{ __('general.search_gif') }}">

		<div class="container-gifs w-100 d-block">
			@include('includes.item-gif')
		</div>
  </div>
</div>
@endif
