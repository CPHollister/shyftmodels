jQuery( function() {

	jQuery(document).on( 'click', '#place_order', function(){

		var $form = jQuery("form.checkout, form#order_review");

		var token = $form.find('input.stripe_token');

		token.val('');

		var token_action = function( res ) {
			$form.find('input.stripe_token').remove();
			$form.append("<input type='hidden' class='stripe_token' name='stripe_token' value='" + res.id + "'/>");
			$form.submit();
		};

		StripeCheckout.open({
			key:         wc_stripe_connect_params.key,
			address:     false,
			amount:      jQuery(this).data( 'amount' ),
			name:        jQuery(this).data( 'name' ),
			description: jQuery(this).data( 'description' ),
			panelLabel:  jQuery(this).data( 'label' ),
		        currency:    jQuery(this).data( 'currency' ),
			token:       token_action
		});

		return false;
    });

} );