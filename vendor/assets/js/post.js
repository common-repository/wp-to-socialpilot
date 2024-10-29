var wp_to_social_pro_character_counting = false;

/**
 * Character Count
 *
 * @since 	3.9.6
 */
function wp_to_social_pro_character_count() {

	( function( $ ) {

		// If we're currently running an AJAX request, don't run another one
		if ( wp_to_social_pro_character_counting ) {
			return;
		}

        // Find the displayed panel
		$( 'div.wpzinc-nav-tabs-panel' ).each( function() {
			if ( $( this ).css( 'display' ) == 'block' ) {
				var active_panel = this,
					statuses = [];

				// Iterate through all textareas within the active panel, getting the status text for each
				$( 'div.status textarea', $( active_panel ) ).each( function() {
					statuses.push( $( this ).val() );
				} );

				// Set a flag so we know we're performing an AJAX request
				wp_to_social_pro_character_counting = true;

				// Send an AJAX request to fetch the parsed statuses and character counts for each status
				$.post( 
					wp_to_social_pro.ajax, 
					{
						'action': 						wp_to_social_pro.character_count_action,
						'post_id': 						wp_to_social_pro.post_id,
						'statuses': 					statuses,
						'nonce': 						wp_to_social_pro.character_count_nonce
					},
					function( response ) {

						// Iterate through the textareas again
						$( 'div.status textarea', $( active_panel ) ).each( function( i ) {
							// Update the character count for this textarea
							$( 'span.character-count', $( this ).parent() ).text( response.data.parsed_statuses[ i ].length );	
						} );

						// Reset the flag
						wp_to_social_pro_character_counting = false;
						
		            }
		        );
			}
		} );

	} )( jQuery );

}

jQuery( document ).ready( function( $ ) {

	/**
	 * Character Count Events
	 *
	 * @since 	3.0.0
	 */
	$( '.wpzinc-nav-tabs a', $( wp_to_social_pro.character_count_metabox ) ).on( 'click', function( e ) {
		wp_to_social_pro_character_count();
	} );
	$( 'input[type="checkbox"]', $( wp_to_social_pro.character_count_metabox ) ).on( 'change', function( e ) {
		wp_to_social_pro_character_count();
	} );
	$( 'div.status textarea', $( wp_to_social_pro.character_count_metabox ) ).on( 'change', function( e ) {
		wp_to_social_pro_character_count();
	} );
	$( 'a.button.add-status', $( wp_to_social_pro.character_count_metabox ) ).on( 'change', function( e ) {
		wp_to_social_pro_character_count();
	} );

	/**
	 * Clear Log
	 *
	 * @since 	3.0.0
	 */
	$( 'a.clear-log' ).on( 'click', function( e ) {

		// Prevent default action
		e.preventDefault();

		// Define button
		var button = $( this );

		// Bail if the button doesn't have an action and a target
		if ( typeof $( button ).data( 'action' ) === undefined || $( button ).data( 'target' ) === undefined ) {
			return;
		}

		// Bail if the user doesn't want to clear the log
		var result = confirm( wp_to_social_pro.clear_log_message );
		if ( ! result ) {
			return;
		}

		// Send AJAX request to clear log
		$.post( 
			wp_to_social_pro.ajax, 
			{
				'action': 		$( button ).data( 'action' ),
				'post': 		$( 'input[name=post_ID]' ).val(),
				'nonce': 		wp_to_social_pro.clear_log_nonce
			},
			function( response ) {

				if ( response.success ) {
					$( 'table.widefat tbody', $( $( button ).data( 'target' ) ) ).html( '<tr><td colspan="3">' + wp_to_social_pro.clear_log_completed + '</td></tr>' );	
				} else {
					alert( response.data );
				}

            }
        );
	} );

} );