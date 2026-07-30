@extends('layouts.app')

@section('css')
    <style>
      .fileuploader-items {white-space: unset !important;}
      .fileuploader-item:nth-child(1) {margin-left: 16px !important;}
    </style>
@endsection

@section('content')
<section class="section section-sm">
    <div class="container">
      <div class="row justify-content-center text-center mb-sm">
        <div class="col-lg-12 pt-5 pb-3">
          <h4 class="mb-0 font-montserrat">
            <a href="javascript:history.back();" class="text-decoration-none mr-2" title="{{__('general.go_back')}}">
              <i class="fas fa-arrow-left"></i>
            </a> {{__('general.edit_post')}}
          </h4>
        </div>
      </div>

      <div class="row justify-content-center">
        <div class="col-lg-8 mb-5 mb-lg-0 wrap-post">

          @if ($settings->moderation_status)
          <div class="alert alert-info">
            <i class="bi bi-info-circle-fill mr-1"></i> {{ __('general.moderation_status_info') }}
          </div>
          @endif

          <form method="POST" action="{{ url('update/edit') }}" enctype="multipart/form-data" id="formUpdateEdit">
            @csrf
            <input type="hidden" name="id" value="{{$data->id}}" />
          <div class="card mb-4 card-border-0 rounded-large shadow-large">
            <div class="blocked display-none"></div>
            <div class="card-body pb-0">

              <div class="media">
                <div class="media-body">
                <textarea name="description" id="updateDescription" data-post-length="{{$settings->update_length}}" rows="5" cols="40" placeholder="{{__('general.write_something')}}" class="form-control textareaAutoSize updateDescription emojiArea border-0">{{$data->description}}</textarea>
              </div>
            </div><!-- media -->

                <input class="custom-control-input d-none" id="customCheckLocked" type="checkbox" {{$data->locked == 'yes' ? 'checked' : ''}}  name="locked" value="yes">

                <!-- Alert -->
                <div class="alert alert-danger my-3 display-none errorUdpate" id="errorUdpate">
                 <ul class="list-unstyled m-0 showErrorsUdpate" id="showErrorsUdpate"></ul>
               </div><!-- Alert -->

            </div><!-- card-body -->

            <div class="card-footer bg-white border-0 pt-0 rounded-large">
              <div class="justify-content-between align-items-center">

                <div class="form-group @if ($data->price == 0.00) display-none @endif" id="price" >
                  <div class="input-group mb-2">
                  <div class="input-group-prepend">
                    <span class="input-group-text">{{$settings->currency_symbol}}</span>
                  </div>
                      <input class="form-control isNumber" value="{{$data->price != 0.00 ? $data->price : null}}" autocomplete="off" name="price" placeholder="{{__('general.price')}}" type="text">
                  </div>
                </div><!-- End form-group -->

                @if (!$mediaCount && $data->locked == 'yes')
                <div class="form-group @if (! $data->title) display-none @endif" id="titlePost" >
                  <div class="input-group mb-2">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="bi-type"></i></span>
                  </div>
                      <input class="form-control @if ($data->title) active @endif" value="{{$data->title ? $data->title : null}}" autocomplete="off" name="title" maxlength="100" placeholder="{{__('admin.title')}}" type="text">
                  </div>
                  <small class="form-text text-muted mb-4">
                    {{ __('general.title_post_info', ['numbers' => 100]) }}
                  </small>
                </div><!-- End form-group -->
                @endif

                <div class="w-100 mb-2">
                  <small class="container-preview" id="previewImage">
                    @if ($fileZip)
                      <strong><em>{{ $fileZip->file_name }}.zip</em></strong>

                      <a href="javascript:void(0)" data-file="{{ $fileZip->file }}" class="text-danger p-1 btn-tooltip removeMediaFile" data-toggle="tooltip" data-placement="top" title="{{__('general.delete')}}">
                        <i class="fa fa-times-circle"></i>
                      </a>
                    @endif
                  </small>
                  <a href="javascript:void(0)" id="removePhoto" class="text-danger p-1 small display-none btn-tooltip" data-toggle="tooltip" data-placement="top" title="{{__('general.delete')}}"><i class="fa fa-times-circle"></i></a>
                </div>

                <div class="w-100 mb-2">
                  <small class="container-preview" id="previewEpub">
                    @if ($fileEpub)
                      <strong><em>{{ $fileEpub->file_name }}.epub</em></strong>

                      <a href="javascript:void(0)" data-file="{{ $fileEpub->file }}" class="text-danger p-1 btn-tooltip removeMediaFile" data-toggle="tooltip" data-placement="top" title="{{__('general.delete')}}">
                        <i class="fa fa-times-circle"></i>
                      </a>
                    @endif
                  </small>
                  <a href="javascript:void(0)" id="removeEpub" class="text-danger p-1 small display-none btn-tooltip-form" data-toggle="tooltip" data-placement="top" title="{{__('general.delete')}}"><i class="fa fa-times-circle"></i></a>
                </div>

                  @if ($data->can_media_edit)
                  <input @if ($preloadedFile) data-fileuploader-files='{!! $preloadedFile !!}' @else data-filter @endif type="file" name="photo[]" id="filePhoto" accept="image/*,video/mp4,video/x-m4v,video/quicktime,audio/mp3" class="visibility-hidden">

                  <button type="button" class="btnMultipleUpload btn e-none align-bottom @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill btn-upload btn-tooltip" data-toggle="tooltip" data-placement="top" title="{{__('general.upload_media')}} ({{ __('general.media_type_upload') }})">
                    <i class="feather icon-image f-size-20 align-bottom"></i>
                  </button>

                  @if ($settings->allow_zip_files)
                  <input type="file" name="zip" id="fileZip" accept="application/x-zip-compressed" class="visibility-hidden">

                  <button type="button" class="btn btn-upload btn-tooltip e-none align-bottom @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill" data-toggle="tooltip" data-placement="top" title="{{__('general.upload_file_zip')}}" onclick="$('#fileZip').trigger('click')">
                    <i class="bi bi-file-earmark-zip f-size-20 align-bottom"></i>
                  </button>
                  @endif

                  @if ($settings->allow_epub_files)
                    <input type="file" name="epub" id="ePubFile" accept="application/epub+zip" class="visibility-hidden">

                    <button type="button" class="btn btn-post btn-tooltip-form p-bottom-8 e-none @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill" data-toggle="tooltip" data-placement="top" title="{{__('general.upload_epub_file')}}" onclick="$('#ePubFile').trigger('click')">
                      <i class="bi-book f-size-20 align-bottom"></i>
                    </button>
                  @endif

                  @endif

                  @if ($data->price == 0.00 && !$settings->ppv_only_free_accounts || auth()->user()->free_subscription == 'yes' && $settings->ppv_only_free_accounts)
                  <button type="button" id="setPrice" class="btn btn-upload btn-tooltip e-none align-bottom @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill" data-toggle="tooltip" data-placement="top" title="{{__('general.price_post_ppv')}}">
                    <i class="feather icon-tag f-size-20 align-bottom"></i>
                  </button>
                @endif

                @if ($data->price == 0.00)
                  @if (!$settings->disable_free_post)
                  <button type="button" id="contentLocked" class="btn e-none align-bottom @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill btn-upload btn-tooltip {{$data->locked == 'yes' ? '' : 'unlock'}}" data-toggle="tooltip" data-placement="top" title="{{__('users.locked_content')}}">
                    <i class="feather icon-{{$data->locked == 'yes' ? '' : 'un'}}lock f-size-20 align-bottom"></i>
                  </button>
                  @endif
                @endif

              @if (!$mediaCount && $data->locked == 'yes')
              <button type="button" id="setTitle" class="btn btn-tooltip-form @if ($data->title) btn-active-hover @endif e-none btn-post @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill" data-toggle="tooltip" data-placement="top" title="{{__('general.title_post_block')}}">
                <i class="bi-type f-size-20 align-bottom"></i>
              </button>
              @endif

              <button type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="btn btn-post p-bottom-8 btn-tooltip-form e-none @if (auth()->user()->dark_mode == 'off') text-primary @else text-white @endif rounded-pill">
                  <i class="bi-emoji-smile f-size-20 align-bottom"></i>
              </button>

              <div class="dropdown-menu dropdown-menu-right dropdown-emoji custom-scrollbar" aria-labelledby="dropdownEmoji">
                @include('includes.emojis')
              </div>

                <div class="d-inline-block float-right mt-3 mt-lg-1 position-relative w-100-mobile">

                  <span class="d-inline-block float-right position-relative rounded-pill w-100-mobile">
                    <span class="btn-blocked display-none"></span>

                    <button type="submit" class="btn btn-sm btn-primary rounded-pill float-right btnEditUpdate w-100-mobile">
                      <i></i> {{__('users.save')}}
                    </button>
                  </span>
                
                  <div id="the-count" class="float-right my-2 mr-2">
                    <small id="maximum">{{$settings->update_length}}</small>
                  </div>
                </div>

              </div>
            </div><!-- card footer -->
          </div><!-- card -->
        </form>
        </div><!-- end col-md-6 -->
      </div>
    </div>
  </section>
@endsection

@section('javascript')
<script type="text/javascript">
$('#maximum').html({{$settings->update_length}}-$('#updateDescription').val().length);

let postId = {{$data->id}};

@if ($fileZip || $fileEpub)
  $(".removeMediaFile").on('click', function (e) {
      e.preventDefault();

      let element = $(this);
      let file = element.data('file');
      element.blur();

      swal({
          title: delete_confirm,
          type: "error",
          showLoaderOnConfirm: true,
          showCancelButton: true,
          confirmButtonColor: "#dd6b55",
          confirmButtonText: yes_confirm,
          cancelButtonText: cancel_confirm,
        },
        function (isConfirm) {
          if (isConfirm) {
            $.post(URL_BASE + '/delete/media', {
              file: file,
              _token: $('meta[name="csrf-token"]').attr('content')
            });
            element.parents('.container-preview').html('');
          }
        });
    });
  @endif
</script>
@endsection
