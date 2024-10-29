<?php
/**
 * AJAX class
 * 
 * @package WP_To_Social_Pro
 * @author  Tim Carr
 * @version 3.0.0
 */
class WP_To_Social_Pro_Ajax {

    /**
     * Holds the base class object.
     *
     * @since   3.4.7
     *
     * @var     object
     */
    public $base;

    /**
     * Constructor
     *
     * @since   3.0.0
     *
     * @param   object $base    Base Plugin Class
     */
    public function __construct( $base ) {

        // Store base class
        $this->base = $base;

        // Actions
        add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_clear_log', array( $this, 'clear_log' ) );

    }

    /**
     * Clears the plugin log for the given Post ID
     *
     * @since   3.0.0
     */
    public function clear_log() {

        // Run a security check first.
        check_ajax_referer( $this->base->plugin->name . '-clear-log', 'nonce' );

        // Get Post ID
        $post_id = absint( $_REQUEST['post'] );

        // Clear log
        $result = $this->base->get_class( 'log' )->delete_by_post_id( $post_id );

        // Return result
        if ( ! $result ) {
            wp_send_json_error( __( 'Unable to clear log', $this->base->plugin->name ) );
        }

        wp_send_json_success();

    }

}