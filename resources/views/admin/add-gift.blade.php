<div class="modal" tabindex="-1" id="addGiftModal">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header border-0">
            <h5 class="modal-title">
               {{ __('general.add_new') }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body">
            <!-- form start -->
            <form method="POST" action="{{ route('gifts.store') }}" enctype="multipart/form-data" files="true">
               @csrf
               <div class="mb-3">
                  <div class="mb-1">
                     <input name="image" required type="file" accept="image/*" class="form-control custom-file rounded-pill">
                   </div>
                   <small style="font-size: 13px;">
                     <i class="bi-info-circle me-1"></i> {{ __('general.recommended_size') }} 200x200 px (PNG, SVG, GIF)
                   </small>
                   
               </div>

               <div class="mb-4">
                  <input type="text" required class="form-control isNumber" value="" name="price"
                     maxlength="255" placeholder="{{ __('general.price') }} ({{ __('general.minimum') }} 0.50)">
               </div>

               <div class="box-footer text-center">
                  <button type="submit" class="btn btn-lg btn-dark w-100 rounded-pill buttonActionSubmit">
                     <i></i> {{ __('admin.save') }}
                  </button>
               </div>
            </form>
         </div>
      </div>
   </div>
</div>