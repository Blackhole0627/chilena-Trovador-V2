    @if ($files->isNotEmpty())
    <div class="row g-3">
    @foreach ($files as $file)
    <!-- Media Item -->
    <div class="col-lg-3 col-md-6 mb-3 vault-item"
    data-id="{{ $file->id }}"
    data-name="{{ $file->file_name }}"
    data-url="{{ Helper::getFile(config('path.vault') . $file->file) }}" 
    data-type="{{ $file->mime }}"
    data-size="{{ $file->bytes }}"
    data-local="{{ $file->file }}?vault=1">
        <div class="media-card" style="background-image: url('{{ $file->preview }}');">
            <div class="media-date">{{ Helper::formatDate($file->created_at) }}</div>
            <div class="media-icon-check"><i class="bi-circle text-shadow" style="text-shadow: 0px 0px 5px black;"></i></div>
            <div class="media-icon">
                <i class="bi-{{ $file->type == 'image' ? 'image-alt' : 'play-fill' }}"></i>
            </div>
            <div class="media-info">
                <div class="media-title">{{ $file->file_name }}</div>
            </div>
        </div>
    </div><!-- Media Item -->
@endforeach
</div><!-- row -->
@endif