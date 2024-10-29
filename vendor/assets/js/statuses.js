/**
 * Reinitializes autosize instances
 *
 * @since 	3.9.6
 */
function wp_to_social_pro_reinit_autosize() {

	( function( $ ) {

		autosize.destroy( $( '.autosize-js' ) );
		autosize( $( '.autosize-js' ) );

	} )( jQuery );

}

/**
 * Reinitializes status tag instances
 *
 * @since 	3.9.6
 */
function wp_to_social_pro_reinit_tags() {

	( function( $ ) {

		$( 'select.tags' ).each( function() {
			$( this ).unbind( 'change.wp-to-social-pro' ).on( 'change.wp-to-social-pro', function( e ) {
				// Insert tag into required textarea
				var tag 	= $( this ).val(),
					option  = $( 'option:selected', $( this ) ),
					status 	= $( this ).closest( 'div.status' ),
					sel 	= $( 'textarea', $( status ) ),
					val 	= $( sel ).val();			

				// If the selected option contains data attributes, we need to show a prompt to fetch an input
				// before inserting the tag
				if ( typeof $( option ).data( 'question' ) !== 'undefined' ) {
					// Prompt question
					var tag_replacement = prompt( $( option ).data( 'question' ), $( option ).data( 'default-value' ) );
					
					// If no answer was given, use the default
					if ( tag_replacement.length == 0 ) {
						tag_replacement = $( option ).data( 'default-value' );
					}

					// Replace the replacement string with the input
					tag = tag.replace( $( option ).data( 'replace' ), tag_replacement );
				}

				console.log( tag );

				// Insert the tag
				$( sel ).val( val += ' ' + tag ).trigger( 'change' );
			} );
		} );

	} )( jQuery );

}

/**
 * Reindexes statuses
 *
 * @since 	3.9.6
 */
function wp_to_social_pro_reindex_statuses( statuses_container ) {

	( function( $ ) {

		// Find all sortable options in the status container (these are individual statuses)
		// and reindex them from 1
		$( 'div.option.sortable', $( statuses_container ) ).each( function( i ) {
			// Display the index number
			$( 'div.number a.count ', $( this ) ).html( '#' + ( i + 1 ) );

			// Change the field index of any custom fields belonging to this status, so they
			// remain associated with the correct status
			$( 'table td input, table td select', $( this ) ).each( function( j ) {
				$( this ).attr( 'name', $( this ).data( 'name' ).replace( 'index', i ) );
			} );

			// Set 'first' class
			if ( i == 0 ) {
				$( this ).addClass( 'first' );
			} else {
				$( this ).removeClass( 'first' );
			}
		} );

	} )( jQuery );

}

/**
 * Show/hide schedule options based on the chosen schedule
 *
 * @since 	3.9.6
 */
function wp_to_social_pro_update_schedule_options() {

	( function( $ ) {

		// Bail if no schedule dropdowns
		if ( $( 'select.schedule' ).length == 0 ) {
			return;
		}

		// Iterate through each, showing / hiding relative fields
		$( 'select.schedule' ).each( function( i ) {
			switch ( $( this ).val() ) {
				case 'custom':
					$( 'span.schedule', $( this ).parent() ).show();
					$( 'span.hours_mins_secs', $( this ).parent() ).show();
					$( 'span.relative', $( this ).parent() ).hide();
					$( 'span.custom', $( this ).parent() ).show();
					$( 'span.custom_field', $( this ).parent() ).hide();
					$( 'span.the_events_calendar', $( this ).parent() ).hide();
					$( 'span.specific', $( this ).parent() ).hide();
					break;

				case 'custom_relative':
					$( 'span.schedule', $( this ).parent() ).show();
					$( 'span.hours_mins_secs', $( this ).parent() ).hide();
					$( 'span.relative', $( this ).parent() ).show();
					$( 'span.custom', $( this ).parent() ).show();
					$( 'span.custom_field', $( this ).parent() ).hide();
					$( 'span.the_events_calendar', $( this ).parent() ).hide();
					$( 'span.specific', $( this ).parent() ).hide();
					break;

				case 'custom_field':
					$( 'span.schedule', $( this ).parent() ).show();
					$( 'span.hours_mins_secs', $( this ).parent() ).show();
					$( 'span.relative', $( this ).parent() ).hide();
					$( 'span.custom', $( this ).parent() ).hide();
					$( 'span.custom_field', $( this ).parent() ).show();
					$( 'span.the_events_calendar', $( this ).parent() ).hide();
					$( 'span.specific', $( this ).parent() ).hide();
					break;

				case '_EventStartDate':
				case '_EventEndDate':
					$( 'span.schedule', $( this ).parent() ).show();
					$( 'span.hours_mins_secs', $( this ).parent() ).show();
					$( 'span.relative', $( this ).parent() ).hide();
					$( 'span.custom', $( this ).parent() ).hide();
					$( 'span.custom_field', $( this ).parent() ).hide();
					$( 'span.the_events_calendar', $( this ).parent() ).show();
					$( 'span.specific', $( this ).parent() ).hide();
					break;

				case 'specific':
					$( 'span.schedule', $( this ).parent() ).show();
					$( 'span.hours_mins_secs', $( this ).parent() ).hide();
					$( 'span.relative', $( this ).parent() ).hide();
					$( 'span.custom', $( this ).parent() ).hide();
					$( 'span.custom_field', $( this ).parent() ).hide();
					$( 'span.the_events_calendar', $( this ).parent() ).hide();
					$( 'span.specific', $( this ).parent() ).show();
					break;

				default:
					// Hide additonal schedule options
					$( 'span.schedule', $( this ).parent() ).hide();
					$( 'span.hours_mins_secs', $( this ).parent() ).hide();
					$( 'span.relative', $( this ).parent() ).hide();
					$( 'span.custom', $( this ).parent() ).hide();
					$( 'span.custom_field', $( this ).parent() ).hide();
					$( 'span.the_events_calendar', $( this ).parent() ).hide();
					$( 'span.specific', $( this ).parent() ).hide();
					break;
			}
		} );

	} )( jQuery );

}

jQuery( document ).ready( function( $ ) {

	/**
	 * Reinitialize Status Tags
	 */
	wp_to_social_pro_reinit_tags();

	/**
	 * Tab click
	 */
	$( '.wpzinc-js-tabs' ).on( 'change', function() {

		wp_to_social_pro_reinit_autosize();

	} );
	
	/**
	 * Add Status Update
	 */
	$( 'body' ).on( 'click', 'a.button.add-status', function( e ) {

		e.preventDefault();

		// Setup vars
		var button 				= $( this ),
			button_container 	= $( button ).parent(),
			statuses_container 	= $( button ).closest( 'div.statuses' );

		// Clone status element, removing the existing selectize instance
		var status = $( button_container ).prev().clone();
		status.find( 'div.wpzinc-selectize' ).remove();

		// Add cloned status
		$( button_container ).before( '<div class="option sortable">' + $( status ).html() + '</div>' );

		// Reindex statuses
		wp_to_social_pro_reindex_statuses( $( statuses_container ) );

		// Reload sortable
		$( statuses_container ).sortable( 'refresh' );
		
		// Reload conditionals
		$( 'input,select', $( statuses_container ) ).conditional();

		// Reload tag selector
		wp_to_social_pro_reinit_tags();

		// Reload autosize
		wp_to_social_pro_reinit_autosize();

		// Reinit selectize on the statuses
		wp_to_social_pro_reinit_selectize( $( 'div.status', $( statuses_container ) ) );

    } );

	/**
	 * Reorder Status Updates
	 */
	if ( typeof sortable !== 'undefined' ) {
		$( 'div.statuses' ).sortable( {
			containment: 'parent',
			items: '.sortable',
			stop: function( e, ui ) {
				// Get status and container
				var status 				= $( ui.item ),
					statuses_container 	= $( status ).closest( 'div.statuses' );

				// Reindex statuses
				wp_to_social_pro_reindex_statuses( $( statuses_container ) );
			}
		} );
	}
	
	/**
	 * Force focus on inputs, so they can be accessed on mobile.
	 * For some reason using jQuery UI sortable prevents us accessing textareas on mobile
	 * See http://bugs.jqueryui.com/ticket/4429
	 */
	$( 'div.statuses' ).bind( 'click.sortable mousedown.sortable', function( e ) {

		e.target.focus();

	} );

	/**
	 * Delete Status Update
	 */
	$( 'div.statuses' ).on( 'click', 'a.delete', function( e ) {

		e.preventDefault();

		// Confirm deletion
		var result = confirm( wp_to_social_pro.delete_status_message );
		if ( ! result ) {
			return;
		}

		// Get status and container
		var status 				= $( this ).closest( 'div.option' ),
			statuses_container 	= $( status ).closest( 'div.statuses' ),
			sub_panel 			= $( statuses_container ).closest( 'div.wpzinc-nav-tabs-panel' );

		// Delete status
		$( status ).remove();

		// Reindex statuses
		wp_to_social_pro_reindex_statuses( $( statuses_container ) );

	} );

	/**
	 * Add Table Row
	 */
	$( 'a.button.add-table-row' ).on( 'click', function( e ) {

		e.preventDefault();

		// Setup vars
		var button 				= $( this ),
			table 				= $( button ).closest( 'table' ),
			row 				= $( 'tbody tr:first-child', $( table ) );

		// Clone row
		$( 'tbody tr:last-child', $( table ) ).after( '<tr>' + $( row ).html() + '</tr>' );

    } );

    /**
	 * Delete Table Row
	 */
	$( document ).on( 'click', 'a.button.delete-table-row', function( e ) {

		e.preventDefault();

		// Setup vars
		var button 				= $( this ),
			row 				= $( this ).closest( 'tr' );

		// Remove row
		$( row ).remove();

    } );

	/**
	 * Settings: Initialize selectize instances
	 * Post Settings Override: Initialize selectize instances
	 */
	if ( $( '#profiles-container' ).length > 0 && typeof wp_to_social_pro_reinit_selectize != 'undefined' ) {

		wp_to_social_pro_reinit_selectize( '#profiles-container' );

	}

	/**
	 * Schedule Options
	 */
	$( 'body' ).on( 'change', 'select.schedule', function( e ) {

		wp_to_social_pro_update_schedule_options();

	} );
	wp_to_social_pro_update_schedule_options();

} );