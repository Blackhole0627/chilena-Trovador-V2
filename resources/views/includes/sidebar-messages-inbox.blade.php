<div class="w-100 p-3 border-bottom">
	@php
		$inboxFilter = request('filter', 'newest');
		$inboxSearch = request('q');
		$newestFilterUrl = url()->current() . '?' . http_build_query(array_filter(['filter' => 'newest', 'q' => $inboxSearch], fn ($value) => $value !== null && $value !== ''));
		$unreadFilterUrl = url()->current() . '?' . http_build_query(array_filter(['filter' => 'unread', 'q' => $inboxSearch], fn ($value) => $value !== null && $value !== ''));
		$oldestFilterUrl = url()->current() . '?' . http_build_query(array_filter(['filter' => 'oldest', 'q' => $inboxSearch], fn ($value) => $value !== null && $value !== ''));
	@endphp
	<div class="w-100">
		<a href="{{url()->previous()}}" class="h4 mr-1 text-decoration-none">
			<i class="fa fa-arrow-left"></i>
		</a>

		<span class="h5 align-top font-weight-bold">{{trans('general.messages')}}</span>

		<span class="float-right">
			<a href="#" class="h4 text-decoration-none" data-toggle="modal" data-target="#newMessageForm" title="{{trans('general.new_message')}}">
				<i class="feather icon-edit"></i>
			</a>
		</span>
	</div>

	<div class="w-100 mt-3">
		<div class="position-relative">
			<input type="text" class="form-control rounded-pill" style="padding-right: 78px;" id="searchInboxMessages" value="{{ $inboxSearch }}" placeholder="{{ trans('general.search') }}" autocomplete="off">
			<span class="position-absolute text-muted" style="right: 16px; top: 50%; transform: translateY(-50%);">
				<i class="feather icon-search" id="searchInboxIcon"></i>
				<span class="spinner-border spinner-border-sm d-none" id="searchInboxLoader"></span>
				<span id="searchInboxClear" class="text-muted c-pointer @if (! $inboxSearch) d-none @endif" style="font-size: 38px; line-height: 1;">&times;</span>
			</span>
		</div>
	</div>

	<div class="w-100 mt-3">
		<div class="btn-group btn-group-sm d-flex">
			<a href="{{ $newestFilterUrl }}" data-filter="newest" class="btn inbox-filter-btn @if ($inboxFilter == 'newest') btn-primary @else btn-outline-primary @endif mr-3 flex-fill">{{ trans('general.newest') }}</a>
			<a href="{{ $unreadFilterUrl }}" data-filter="unread" class="btn inbox-filter-btn @if ($inboxFilter == 'unread') btn-primary @else btn-outline-primary @endif mr-3 flex-fill">{{ trans('general.unread') }}</a>
			<a href="{{ $oldestFilterUrl }}" data-filter="oldest" class="btn inbox-filter-btn @if ($inboxFilter == 'oldest') btn-primary @else btn-outline-primary @endif flex-fill">{{ trans('general.oldest') }}</a>
		</div>
	</div>
</div>

<div id="messagesInboxResults">
	@include('includes.messages-inbox')
</div>
