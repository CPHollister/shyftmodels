/*
 * Zendesk for WordPress
 *
 * This javascript is enqueued on the Zendesk settings page, has
 * support for placeholders (for anti-HTML5 browsers), child settings
 * hiding and more.
 *
 */

// Fire upon document ready
jQuery(document).ready(function($) {

	// HTML5 placeholders fallback
	if ( !hasPlaceholderSupport() ) {
		$('input[type="text"]').each(function() {
			var input = this;

			// Loop through only those with placeholders
			if ($(input).attr('placeholder')) {

				// Onblur
				$(input).blur(function() {
					if ( $(this).val() == '' ) {
						$(this).val( $(this).attr('placeholder') );
					}
				});

				// Onfocus
				$(input).focus(function() {
					if ( $(this).val() == $(this).attr('placeholder') )
						$(this).val('');
				});

				// Show placeholders for all those with no value
				if ( $(input).val() == '' )
					$(input).val( $(input).attr('placeholder') );

			}
		});
	}

	// Settings page contact form anonymous options.
	$('#zendesk_contact_form_anonymous').change(function() {
		var checked = $(this).attr('checked');
		if ( checked )
			$('#zendesk_contact_form_anonymous_user').closest('tr').fadeIn();
		else
			$('#zendesk_contact_form_anonymous_user').closest('tr').fadeOut();
	});

	if ( ! $('#zendesk_contact_form_anonymous').attr('checked'))
		$('#zendesk_contact_form_anonymous_user').closest('tr').hide();

	// Settings page dropbox options.
	$('#zendesk_dropbox_display').change(function() {
	  if ( $(this).val() != 'none' )
			$('#zendesk_dropbox_code').closest('tr').fadeIn();
		else
			$('#zendesk_dropbox_code').closest('tr').fadeOut();
	});

	if ( $('#zendesk_dropbox_display').val() == 'none' )
		$('#zendesk_dropbox_code').closest('tr').hide();

	// Account string change
	$('#zendesk_account_change').click(function() {
		$('#zendesk_account_string').hide();
		$('#zendesk_account').show().focus();
		return false;
	});

});


// Checks whether the browser has placeholder support.
function hasPlaceholderSupport() {
  var input = document.createElement('input');
  return ('placeholder' in input);
}
