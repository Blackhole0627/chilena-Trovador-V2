<script src="{{ asset('public/js/core.min.js') }}?v={{$settings->version}}"></script>
<script src="{{ asset('public/js/bootstrap.bundle.min.js') }}"></script>

<script type="text/javascript">
  // Bootstrap 4 to Bootstrap 5 Compatibility Bridging Shim
  (function($) {
    if (typeof bootstrap !== 'undefined') {
      
      // 1. Map data-attributes to data-bs-attributes for legacy HTML markup compatibility
      function mapLegacyAttributes(context) {
        var $elms = context ? $(context).find('[data-toggle], [data-dismiss]') : $('[data-toggle], [data-dismiss]');
        if (context && ($(context).is('[data-toggle]') || $(context).is('[data-dismiss]'))) {
          $elms = $elms.add(context);
        }
        
        $elms.each(function() {
          var $this = $(this);
          
          var toggle = $this.attr('data-toggle');
          if (toggle && !$this.attr('data-bs-toggle')) {
            $this.attr('data-bs-toggle', toggle);
          }
          
          var target = $this.attr('data-target');
          if (target && !$this.attr('data-bs-target')) {
            $this.attr('data-bs-target', target);
          }
          
          var dismiss = $this.attr('data-dismiss');
          if (dismiss && !$this.attr('data-bs-dismiss')) {
            $this.attr('data-bs-dismiss', dismiss);
          }
        });
      }

      // Run on DOM ready
      $(function() {
        mapLegacyAttributes();
      });

      // Run on any AJAX requests completed (for dynamic updates)
      $(document).ajaxComplete(function(event, xhr, settings) {
        mapLegacyAttributes();
      });

      // 2. jQuery Method Shims for Javascript invocations
      
      // $.fn.modal
      if ($.fn.modal === undefined) {
        $.fn.modal = function(action, option) {
          return this.each(function() {
            var modalEl = this;
            var instance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            if (action === 'show') {
              instance.show();
            } else if (action === 'hide') {
              instance.hide();
            } else if (action === 'toggle') {
              instance.toggle();
            } else if (action === 'handleUpdate') {
              instance.handleUpdate();
            } else if (typeof action === 'object') {
              // Option re-initialization
              var options = $.extend({}, action);
              var newInstance = new bootstrap.Modal(modalEl, options);
              if (option === 'show') {
                newInstance.show();
              }
            }
          });
        };
      }
      if ($.fn.modal) {
        $.fn.modal.Constructor = bootstrap.Modal;
      }

      // $.fn.collapse
      if ($.fn.collapse === undefined) {
        $.fn.collapse = function(action) {
          return this.each(function() {
            var collapseEl = this;
            var instance = bootstrap.Collapse.getInstance(collapseEl) || new bootstrap.Collapse(collapseEl);
            if (action === 'show') {
              instance.show();
            } else if (action === 'hide') {
              instance.hide();
            } else if (action === 'toggle') {
              instance.toggle();
            }
          });
        };
      }
      if ($.fn.collapse) {
        $.fn.collapse.Constructor = bootstrap.Collapse;
      }

      // $.fn.tooltip
      if ($.fn.tooltip === undefined) {
        $.fn.tooltip = function(action) {
          return this.each(function() {
            var tooltipEl = this;
            var instance = bootstrap.Tooltip.getInstance(tooltipEl) || new bootstrap.Tooltip(tooltipEl);
            if (action === 'show') {
              instance.show();
            } else if (action === 'hide') {
              instance.hide();
            } else if (action === 'toggle') {
              instance.toggle();
            } else if (action === 'dispose') {
              instance.dispose();
            } else if (action === 'enable') {
              instance.enable();
            } else if (action === 'disable') {
              instance.disable();
            }
          });
        };
      }
      if ($.fn.tooltip) {
        $.fn.tooltip.Constructor = bootstrap.Tooltip;
      }

      // $.fn.popover
      if ($.fn.popover === undefined) {
        $.fn.popover = function(action) {
          return this.each(function() {
            var popoverEl = this;
            var instance = bootstrap.Popover.getInstance(popoverEl) || new bootstrap.Popover(popoverEl);
            if (action === 'show') {
              instance.show();
            } else if (action === 'hide') {
              instance.hide();
            } else if (action === 'toggle') {
              instance.toggle();
            } else if (action === 'dispose') {
              instance.dispose();
            } else if (action === 'enable') {
              instance.enable();
            } else if (action === 'disable') {
              instance.disable();
            }
          });
        };
      }
      if ($.fn.popover) {
        $.fn.popover.Constructor = bootstrap.Popover;
      }

    }
  })(jQuery);
</script>
<script src="{{ asset('public/js/jqueryTimeago_'.Lang::locale().'.js') }}"></script>
<script src="{{ asset('public/js/lazysizes.min.js') }}" async=""></script>
<script src="{{ asset('public/js/plyr/plyr.min.js') }}?v={{$settings->version}}"></script>
<script src="{{ asset('public/js/plyr/plyr.polyfilled.min.js') }}?v={{$settings->version}}"></script>
<script src="{{ asset('public/js/app-functions.js') }}?v={{$settings->version}}"></script>

@if (request()->routeIs('reels.section.*') || request()->routeIs('profile') && request('media') == 'reels')
<script src="{{ asset('public/js/reels/reels.js') }}?v={{$settings->version}}"></script>
<script src="{{ asset('public/js/reels/comments-reels.js') }}?v={{$settings->version}}"></script>
@endif

@if (! request()->is('live/*'))
<script src="{{ asset('public/js/install-app.js') }}?v={{$settings->version}}"></script>
@endif

@auth
  <script src="{{ asset('public/js/fileuploader/jquery.fileuploader.min.js') }}"></script>
  <script src="{{ asset('public/js/fileuploader/fileuploader-post.js') }}?v={{$settings->version}}"></script>
  <script src="{{ asset('public/js/jquery-ui/jquery-ui.min.js') }}"></script>
  <script src="{{ asset('public/js/vault.js') }}?v={{$settings->version}}"></script>
  @if (request()->path() == '/' 
  		&& auth()->user()->verified_id == 'yes' 
		|| request()->routeIs('profile') 
		&& request()->path() == auth()->user()->username  
		&& auth()->user()->verified_id == 'yes'
		)
  <script src="{{ asset('public/js/jquery-ui/mentions.js') }}"></script>
@endif

@if ($settings->story_status)
<script src="{{ asset('public/js/story/zuck.min.js') }}?v={{$settings->version}}"></script>
@endif

@if ($settings->video_call_status)
<script src="{{ asset('public/js/calls.js') }}?v={{$settings->version}}"></script>
@endif

<script src="https://js.stripe.com/v3/"></script>
<script src='https://checkout.razorpay.com/v1/checkout.js'></script>
<script src='https://js.paystack.co/v1/inline.js'></script>
@if (request()->is('my/wallet'))
<script src="{{ asset('public/js/add-funds.js') }}?v={{$settings->version}}"></script>
@else
<script src="{{ asset('public/js/payment.js') }}?v={{$settings->version}}"></script>
<script src="{{ asset('public/js/payments-ppv.js') }}?v={{$settings->version}}"></script>
@endif
<script src="{{ asset('public/js/send-gift.js') }}?v={{$settings->version}}"></script>
@endauth

@if ($settings->custom_js)
  <script type="text/javascript">
  {!! $settings->custom_js !!}
  </script>
@endif

<script type="text/javascript">
const lightbox = GLightbox({
    touchNavigation: true,
    loop: false,
    closeEffect: 'fade',
    videosWidth: '90vw'
});

@auth
$('.btnMultipleUpload').on('click', function() {
  $('.fileuploader').toggleClass('d-block');
});

	@if (request()->routeIs('post.edit') && $preloadedFile)
	$(document).ready(function() {
		$('.fileuploader').addClass('d-block');
	});
	@endif

@endauth
</script>

@if (auth()->guest() && $settings->age_verification_status && $settings->show_modal_age_verification && !request()->is(['login', 'signup', 'password/reset*']))
<script>
	$('#alertAgeVerification').modal({
		backdrop: 'static',
		keyboard: false
	});
	$('#alertAgeVerification').modal('show');
</script>
@endif

@if (auth()->guest()
    && ! request()->is('password/reset')
    && ! request()->is('password/reset/*')
    && ! request()->is('contact')
    )
<script type="text/javascript">
	//<---------------- Login Register ----------->>>>
	onSubmitformLoginRegister = function() {
		  sendFormLoginRegister();
		}

	if (! captcha) {
	    $(document).on('click','#btnLoginRegister',function(s) {
 		 s.preventDefault();
		 sendFormLoginRegister();
 	 });//<<<-------- * END FUNCTION CLICK * ---->>>>
	}

	function sendFormLoginRegister() {
		var element = $(this);
		$('#btnLoginRegister').attr({'disabled' : 'true'});
		$('#btnLoginRegister').find('i').addClass('spinner-border spinner-border-sm align-middle mr-1');

		(function(){
			 $("#formLoginRegister").ajaxForm({
			 dataType : 'json',
			 success:  function(result) {

         if (result.actionRequired) {
           $('#modal2fa').modal({
    				    backdrop: 'static',
    				    keyboard: false
    				});
           $('#modal2fa').modal('show');

            $('#loginFormModal').modal('hide');
           return false;
         }

				 // Success
				 if (result.success) {

           if (result.isModal && result.isLoginRegister) {
             window.location.reload();
           }

					 if (result.url_return && ! result.isModal) {
					 	window.location.href = result.url_return;
					 }

					 if (result.check_account) {
					 	$('#checkAccount').html(result.check_account).fadeIn(500);

						$('#btnLoginRegister').removeAttr('disabled');
						$('#btnLoginRegister').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
						$('#errorLogin').fadeOut(100);
						$("#formLoginRegister").reset();
					 }

				 }  else {

					 if (result.errors) {
						 var error = '';
						 var $key = '';

					for ($key in result.errors) {
							 error += '<li><i class="far fa-times-circle"></i> ' + result.errors[$key] + '</li>';
						 }

						 $('#showErrorsLogin').html(error);
						 $('#errorLogin').fadeIn(500);
						 $('#btnLoginRegister').removeAttr('disabled');
						 $('#btnLoginRegister').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');

						 if (captcha) {
							grecaptcha.reset();
						 }
					 }
				 }
				},

				statusCode: {
						419: function() {
							window.location.reload();
						}
					},
				error: function(responseText, statusText, xhr, $form) {
						// error
						$('#btnLoginRegister').removeAttr('disabled');
						$('#btnLoginRegister').find('i').removeClass('spinner-border spinner-border-sm align-middle mr-1');
						swal({
								type: 'error',
								title: error_oops,
								text: error_occurred+' ('+xhr+')',
							});
							
						if (captcha) {
							grecaptcha.reset();
						 }
				}
			}).submit();
		})(); //<--- FUNCTION %
	}

  document.addEventListener('contextmenu',function(e){e.preventDefault()}); document.addEventListener('keydown',function(e){ if(e.keyCode===123||(e.ctrlKey&&(e.keyCode===85||e.keyCode===83||(e.shiftKey&&e.keyCode===73))))e.preventDefault(); }); document.querySelectorAll('video').forEach(function(v){ v.setAttribute('controlsList','nodownload'); v.ondragstart=function(){return false}; v.style.webkitTouchCallout='ninguno'; v.style.userSelect='ninguno'; v.addEventListener('contextmenu',function(e){e.preventDefault()}); });
</script>
@endif


