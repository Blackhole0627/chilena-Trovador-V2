<div class="modal fade" id="modalVault" tabindex="-1" role="dialog" aria-labelledby="modal-form" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title">{{__('general.vault')}}</h5>
                <button type="button" class="close close-inherit" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">
                        <i class="bi bi-x-lg"></i>
                    </span>
                </button>
            </div>
            <div class="modal-body p-0 custom-scrollbar">
                <div class="card bg-white shadow border-0">

                    <div class="card-body">
                        <div class="position-relative display-none mb-2" id="searchVault">
                            <span class="my-sm-0 btn-new-msg">
                                <i class="fa fa-search"></i>
                            </span>

                            <input class="form-control input-new-msg rounded mb-3" id="vaultSearch" type="text"
                                name="q" autocomplete="off"
                                placeholder="{{ __('general.search') }}"
                                aria-label="Search">
                            </div>

                        <div class="w-100 text-center mt-3 display-none p-5" id="spinnerVault">
                            <span class="spinner-border align-middle text-primary"></span>
                        </div>

                        <div id="containerFiles" class="text-center"></div>

                    </div>
                </div>
            </div>
            <div class="modal-footer vault-footer display-none">
                <div class="vault-actions w-100 text-center">
                    <div class="vault-buttons">
                        <button class="btn btn-secondary cancel-button">{{ __('admin.cancel') }}</button>
                        <button class="btn btn-primary px-5 add-button" id="add-media-button" disabled>{{ __('general.add') }}</button>
                    </div>
                </div>
              </div>
        </div>
    </div>
</div><!-- End Modal new Message -->