/**
 * Handles the Insert and Cancel events on TinyMCE Modals
 *
 * @since   1.0.0
 */
jQuery( document ).ready( function( $ ) {
    
    // Cancel
    $( 'body' ).on( 'click', 'form.wpzinc-tinymce-popup button.close', function( e ) {

        if ( $( '.mce-container-body' ).length > 0 ) {
            // Visual Editor
            tinymce.activeEditor.windowManager.close();
        } else {
            // Text Editor
            parent.tb_remove();
        }

    } );

    // Insert
    $( 'body' ).on( 'click', 'form.wpzinc-tinymce-popup input[type=submit]', function( e ) {

        // Prevent default action
        e.preventDefault();

        // Get containing form
        var form = $( this ).closest( 'form.wpzinc-tinymce-popup' );

        // Build Shortcode
        var shortcode = '[' + $( 'input[name=shortcode]', $( form ) ).val();

        $( 'input, select', $( form ) ).each( function( i ) {
            // Skip if no data-shortcode attribute
            if ( typeof $( this ).data( 'shortcode' ) === 'undefined' ) {
                return true;
            }

            // Skip if the value is empty
            if ( ! $( this ).val() ) {
                return true;
            }
            if ( $( this ).val().length == 0 ) {
                return true;
            }

            // Get shortcode attribute
            var key = $( this ).data( 'shortcode' ),
                val = $( this ).val();

            // If the shortcode attribute is within curly braces, the shortcode attribute
            // is the value of another field
            if ( key.search( '}' ) > -1 && key.search( '{' ) > -1 ) {
                // Remove curly braces
                key = key.replace( /{|}/gi, function ( x ) {
                    return '';
                } );

                // Get value of input/select, which will form our attribute
                key = $( key, $( this ).parent().parent() ).val();
            }

            // If a prepend is specified, prepend the key with it now
            if ( typeof ( $( this ).data( 'shortcode-prepend' ) ) !== 'undefined' ) {
                key = $( this ).data( 'shortcode-prepend' ) + key;
            }

            // If the value is an array (i.e. from selectize), implode it now
            if ( Array.isArray( val ) ) {
                val = val.join( ',' );
            }

            // Append attribute and value to shortcode string
            shortcode += ' ' + key.trim() + '="' + val.trim() + '"';
        } );

        // Close Shortcode
        shortcode += ']';

        /**
         * Finish building the link, and insert it, depending on whether we were initialized from
         * the Visual Editor or not.
         */
        if ( $( '.mce-container-body' ).length > 0 ) {
            // TinyMCE
            // tinyMCE.activeEditor will give you the TinyMCE editor instance that's being used.
            
            // Insert into editor
            tinyMCE.activeEditor.execCommand( 'mceReplaceContent', false, shortcode );

            // Close modal
            tinyMCE.activeEditor.windowManager.close();

            // Done
            return;
        } else {
            // Insert into text editor
            editor.value += shortcode;

            // Trigger a change event for scripts that watch for changes to the Text editor content
            $( editor ).trigger( 'change' ); 

            // Close popup
            parent.tb_remove();
        }

    } );

} );