<div class="modal fade modalReport" id="reportModalReel" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-danger modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title font-weight-light" id="modal-title-default"><i class="bi-flag mr-2"></i>
                    {{__('admin.report')}}</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <!-- form start -->
            <div class="modal-body">
                <!-- Start Form Group -->
                <div class="form-group">
                    <label>{{__('admin.please_reason')}}</label>
                    <select name="reason" id="reportReason" class="form-control custom-select">
                        <option value="spoofing">{{__('admin.spoofing')}}</option>
                        <option value="copyright">{{__('admin.copyright')}}</option>
                        <option value="privacy_issue">{{__('admin.privacy_issue')}}</option>
                        <option value="violent_sexual">{{__('admin.violent_sexual_content')}}</option>
                        <option value="spam">{{__('general.spam')}}</option>
                        <option value="fraud">{{__('general.fraud')}}</option>
                    </select>
                </div><!-- /.form-group-->
            </div><!-- Modal body -->

            <div class="text-center pb-4">
                <button type="submit" class="btn btn-xs btn-white reportReel ml-auto" id="reportReel"><i></i>
                    {{__('admin.report')}}</button>
                <button type="button" class="btn border text-white" data-dismiss="modal">{{__('admin.cancel')}}</button>
            </div>
        </div><!-- Modal content -->
    </div><!-- Modal dialog -->
</div>