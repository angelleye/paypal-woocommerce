jQuery(function($) {

	// wc_checkout_params is required to continue, ensure the object exists
	if (typeof wc_checkout_params === "undefined")
		return false;

	var updateTimer;
	var dirtyInput = false;
	var xhr;

	function update_checkout() {

		if (xhr) xhr.abort();

		var shipping_methods = [];

		$('select.shipping_method, input[name^=shipping_method][type=radio]:checked, input[name^=shipping_method][type=hidden]').each( function( index, input ) {
			shipping_methods[ $(this).data( 'index' ) ] = $(this).val();
		} );

		var payment_method 	= $('#order_review input[name=payment_method]:checked').val();
		var country 		= $('#billing_country').val();
		var state 			= $('#billing_state').val();
		var postcode 		= $('input#billing_postcode').val();
		var city	 		= $('input#billing_city').val();
		var address	 		= $('input#billing_address_1').val();
		var address_2	 	= $('input#billing_address_2').val();

		if ( $('#ship-to-different-address input').is(':checked') || $('#ship-to-different-address input').size() == 0 ) {
			var s_country 	= $('#shipping_country').val();
			var s_state 	= $('#shipping_state').val();
			var s_postcode 	= $('input#shipping_postcode').val();
			var s_city 		= $('input#shipping_city').val();
			var s_address 	= $('input#shipping_address_1').val();
			var s_address_2	= $('input#shipping_address_2').val();
		} else {
			var s_country 	= country;
			var s_state 	= state;
			var s_postcode 	= postcode;
			var s_city 		= city;
			var s_address 	= address;
			var s_address_2	= address_2;
		}

		$('#order_methods, #order_review').block({message: null, overlayCSS: {background: '#fff url(' + wc_checkout_params.ajax_loader_url + ') no-repeat center', backgroundSize: '16px 16px', opacity: 0.6}});

		var data = {
			action: 			'woocommerce_update_order_review',
			security: 			wc_checkout_params.update_order_review_nonce,
			shipping_method: 	shipping_methods,
			payment_method:		payment_method,
			country: 			country,
			state: 				state,
			postcode: 			postcode,
			city:				city,
			address:			address,
			address_2:			address_2,
			s_country: 			s_country,
			s_state: 			s_state,
			s_postcode: 		s_postcode,
			s_city:				s_city,
			s_address:			s_address,
			s_address_2:		s_address_2,
            pp_action:          'revieworder',
			post_data:			$('form.angelleye_checkout').serialize()
		};

		xhr = $.ajax({
			type: 		'POST',
			url: 		wc_checkout_params.ajax_url,
			data: 		data,
			success: 	function( response ) {
                // Remove old AJAX errors
                $( '.woocommerce-error-ajax' ).remove();

                // Check reponse
                if ( '-1' === response ) {
                    var $form = $( 'form.angelleye_checkout' );

                    $form.prepend( wc_checkout_params.session_expired_message );

                    // Scroll to top
                    $( 'html, body' ).animate( {
                        scrollTop: ( $( 'form.angelleye_checkout' ).offset().top - 100 )
                    }, 1000 );

                } else if ( response ) {

                    // Check the response result
                    if ( 'failure' == response.result ) {

                        // Form object
                        var $form = $( 'form.angelleye_checkout' );

                        if ( response.messages ) {
                            $form.prepend( response.messages );
                        } else {
                            $form.prepend( response );
                        }

                        // Lose focus for all fields
                        $form.find( '.input-text, select' ).blur();

                        // Scroll to top
                        $( 'html, body' ).animate( {
                            scrollTop: ( $( 'form.angelleye_checkout' ).offset().top - 100 )
                        }, 1000 );

                    }

                    $( '#order_review' ).html( $.trim( response.html ) );
                    $( '#order_review' ).find( 'input[name=payment_method]:checked' ).trigger( 'click' );
                    $( 'body' ).trigger( 'updated_checkout' );
                }
			}
		});

	}

	// Event for updating the checkout
	$('body').bind('update_checkout', function() {
		clearTimeout(updateTimer);
		update_checkout();
	});



	// Used for input change events below
	function input_changed() {
		var update_totals = true;

		if ( $(dirtyInput).size() ) {

			$required_siblings = $(dirtyInput).closest('.form-row').siblings('.address-field.validate-required');

			if ( $required_siblings.size() ) {
				 $required_siblings.each(function(){
					if ( $(this).find('input.input-text').val() == '' || $(this).find('input.input-text').val() == 'undefined' ) {
						update_totals = false;
					}
				 });
			}

		}

		if ( update_totals ) {
			dirtyInput = false;
			$('body').trigger('update_checkout');
		}
	}

	$('form.angelleye_checkout')

	/* Update totals/taxes/shipping */

	// Inputs/selects which update totals instantly
	.on( 'input change', 'select.shipping_method, input[name^=shipping_method], #ship-to-different-address input, .update_totals_on_change select', function(){
		clearTimeout( updateTimer );
		dirtyInput = false;
		$('body').trigger('update_checkout');
	})

});
