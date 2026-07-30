@foreach ($gifs as $gif)
<div @class(['col-12 p-0', 'mb-2'=> !$loop->last])>
    <span class="insertGif c-pointer" data-url="{{ $gif['images']['fixed_height']['url'] }}">
        <img src="{{ $gif['images']['fixed_height']['url'] }}" class="rounded" width="100%">
    </span>
</div>
@endforeach