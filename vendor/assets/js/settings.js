jQuery( document ).ready( function( $ ) {

	/**
	 * Settings: Custom Tags: Add
	 *
	 * @since 	3.6.2
	 */
	$( 'a.add-custom-tag' ).click( function( e ) {
		
		e.preventDefault();
		
		// Copy hidden element
		var element = $( 'tbody tr.hidden', $( this ).closest( 'table' ) );
		$( 'tbody', $( this ).closest( 'table' ) ).append( '<tr>' + $( element ).html() + '</tr>' );

	} );

	/**
	 * Settings: Custom Tags: Delete
	 *
	 * @since 	3.6.2
	 */
	$( document ).on( 'click', 'a.delete-custom-tag', function( e ) {
		
		e.preventDefault();
		
		// Delete row
		$( this ).closest( 'tr' ).remove();

	} );

} );