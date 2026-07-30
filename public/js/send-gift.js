(function($) {
	"use strict";
    
    $(document).on('click','.giftBtn',function(s) {

	let isValid = this.form.checkValidity();

	if (isValid) {
	 s.preventDefault();
	 let element = $(this);
	 element.attr({'disabled' : 'true'});
	 element.find('i').addClass('spinner-border spinner-border-sm align-middle mr-1');

	 (function(){
			$('#formSendGift').ajaxForm({
			dataType : 'json',
			success:  function(result) {
				// Wallet
				if (result.success) {
					swal({
	 				 title: thanks,
	 				 text: gift_sent_success,
	 				 type: "success",
	 				 confirmButtonText: ok
	 				 });
	 				 $('#giftsForm').modal('hide');
	 				 $('#formSendGift').trigger("reset");
	 				 $('.btn-radio').removeClass('active');
	 				 $('.giftBtn').removeAttr('disabled');
	 				 $('.giftBtn').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
					  if (result.wallet) {
						$('.balanceWallet').html(result.wallet);
					}
				}  else {

					if (result.errors) {

						let error = '';
						let $key = '';

						for($key in result.errors) {
							error += '<li><i class="far fa-times-circle"></i> ' + result.errors[$key] + '</li>';
						}

						$('#showErrorsGift').html(error);
						$('#errorGift').show();
						element.removeAttr('disabled');
						element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
					}
				}

			 },
			 error: function(responseText, statusText, xhr, $form) {
					 // error
					 element.removeAttr('disabled');
					 element.find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
					 swal({
							 type: 'error',
							 title: error_oops,
							 text: error_occurred+' ('+xhr+')',
						 });
			 }
		 }).submit();
	 })(); //<--- FUNCTION %
	 }// isValid
 });


 $('#giftsForm').on('hidden.bs.modal', function (e) {
  $('#errorGift').hide();
  $('.btn-radio').removeClass('active');
  $('#formSendGift').trigger("reset");
});

})(jQuery);