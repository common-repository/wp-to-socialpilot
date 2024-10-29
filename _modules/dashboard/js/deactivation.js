var wpzinc_deactivation_url;

jQuery( document ).ready( function( $ ) {

	/**
	 * Show deactivation modal if the user is deactivating our plugin
	 */
	$( 'span.deactivate a' ).on( 'click', function( e ) {

		// If the link slug doesn't exist, let the request through
		var plugin_name = $( this ).closest( 'tr' ).data( 'slug' );
		if ( typeof plugin_name === 'undefined' ) {
			return true;
		}

		// If the Plugin being deactivated isn't our one, let the request through
		if ( plugin_name != wpzinc_dashboard.plugin.name ) {
			return true;
		}

		// If here, we're deactivating our plugin
		e.preventDefault();

		// Store the target URL
		wpzinc_deactivation_url = $( this ).attr( 'href' );

		// Show the modal
		$( '#wpzinc-modal, #wpzinc-modal-overlay' ).show();

	} );

	/**
	 * Show optional fields depending on the answer chosen
	 */
	$( 'body' ).on( 'change', 'form#wpzinc-modal-form input[name=reason]', function() {

		switch ( $( this ).val() ) {
			case 'not_working':
			case 'better_alternative':
			case 'other':
				$( '#wpzinc-modal' ).outerHeight( 480 );
				$( 'form#wpzinc-modal-form div.additional-information' ).show();

				break;

			default:
				$( '#wpzinc-modal' ).outerHeight( 325 );
				$( 'form#wpzinc-modal-form div.additional-information' ).hide();
				break;
		}

	} );

	/**
	 * Send the result of the deactivation modal when the submit button is clicked,
	 * and load the deactivation URL so that the plugin gets deactivated.
	 */
	$( 'form#wpzinc-modal-form' ).on( 'submit', function( e ) {

		e.preventDefault();

		var wpzinc_dashboard_deactivation_reason = $( 'input[name=reason]:checked', $( this ) ).val(),
			wpzinc_dashboard_deactivation_reason_text = $( 'input[name=reason_text]', $( this ) ).val(),
			wpzinc_dashboard_deactivation_reason_email = $( 'input[name=reason_email]', $( this ) ).val();

		// Submit the form via AJAX if a reason was given
		if ( typeof wpzinc_dashboard_deactivation_reason !== 'undefined' ) {
			$.ajax( {
		        url: 		ajaxurl,
		        type: 		'POST',
		        async:    	true,
		        data: 		{
		        	action: 	'wpzinc_dashboard_deactivation_modal_submit',
		        	product: 		wpzinc_dashboard.plugin.name,
		        	reason: 		wpzinc_dashboard_deactivation_reason,
		        	reason_text: 	wpzinc_dashboard_deactivation_reason_text,
		        	reason_email: 	wpzinc_dashboard_deactivation_reason_email
		        },
		        error: function( a, b, c ) {
		        },
		        success: function( result ) {
		        }
		    } );
		}

	    // Hide the modal
	    $( '#wpzinc-modal, #wpzinc-modal-overlay' ).hide();

		// Load the deactivation URL
		window.location.href = wpzinc_deactivation_url;

	} );

} );