<?php
/**
 * Post class
 * 
 * @package WP_To_Social_Pro
 * @author  Tim Carr
 * @version 3.0.0
 */
class WP_To_Social_Pro_Post {

    /**
     * Holds the base class object.
     *
     * @since 3.2.0
     *
     * @var object
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

        // Admin Notices
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );

    }

    /**
     * Outputs a notice if the user is editing a Post, which has a meta key indicating
     * that status(es) were published to the API
     *
     * @since   3.0.0
     */
    public function admin_notices() {

        // Check we can get the current screen the user is viewing
        $screen = get_current_screen();
        if ( ! $screen || ! isset( $screen->base ) || ! isset( $screen->parent_base ) ) {
            return;
        }

        // Check we are on a Post based screen (includes Pages + CPTs)
        if ( $screen->base != 'post' ) {
            return;
        }

        // Check we are editing a Post, Page or CPT
        if ( $screen->parent_base != 'edit' ) {
            return;
        }

        // Check we have a Post ID
        if ( ! isset( $_GET['post'] ) ) {
            return;
        }
        $post_id = absint( $_GET['post'] );

        // Check if this Post has a success or error meta key set by this plugin
        $success= get_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_success', true );
        $error  = get_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_error', true );
        $errors = get_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_errors', true );

        // Check for success
        if ( $success ) {
            // Show notice and clear meta key, so we don't display this notice again
            delete_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_success' );
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php echo sprintf( __( '%s: Post successfully added to %s.', $this->base->plugin->name ), $this->base->plugin->displayName, $this->base->plugin->account ); ?> 
                </p>
            </div>
            <?php
        }

        // Check for error
        if ( $error ) {
            // Show notice and clear meta key, so we don't display this notice again
            delete_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_error' );
            delete_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_errors' );
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <?php
                    echo sprintf( 
                        __( '%s: Some status(es) could not be sent to %s:<br />%s', $this->base->plugin->name ), 
                        $this->base->plugin->displayName, 
                        $this->base->plugin->account,
                        implode( '<br />', $errors )
                    );
                    ?> 
                </p>
            </div>
            <?php
        }

    }

    /**
     * Returns an array of images that may have been chosen for use when status(es)
     * are sent to the social network for this Post.
     *
     * @since   3.7.8
     *
     * @param   int     $post_id    Post ID
     * @return  array               Images
     */
    private function get_post_images( $post_id ) {

        // If additional images are supported by the calling Plugin, allow 4 images
        // in total to be defined.  Otherwise, only allow a single Featured Image
        $supported_images_total = ( $this->base->supports( 'additional_images' ) ? 4 : 1 );

        // Fetch existing images that might have been assigned to this Post
        $images = array();
        for ( $i = 0; $i < $supported_images_total; $i++ ) {
            switch ( $i ) {
                case 0:
                    // For backward compat, the first image is stored in the featured_image key
                    // This ensures that any installations < 3.7.8 that have a Plugin Featured Image
                    // set will honor this setting on a status update / repost.
                    $images[ $i ] = array(
                        'id'            => $this->get_setting_by_post_id( $post_id, 'featured_image', false ),
                        'thumbnail_url' => false,
                    );
                    break;

                default:
                    // Additional images are stored in the additional_images key
                    $images[ $i ] = array(
                        'id'            => $this->get_setting_by_post_id( $post_id, '[additional_images][' . ( $i - 1 ) . ']', false ),
                        'thumbnail_url' => false,
                    );
                    break;
            }
        }

        // Iterate through the images, fetching their thumbnails if an image ID is specified
        foreach ( $images as $i => $image ) {
            if ( ! $image['id'] ) {
                continue;
            }

            // Get attachment
            $attachment = wp_get_attachment_image_src( $image['id'], 'thumbnail' );
            
            // Skip if attachment didn't return anything
            if ( ! $attachment ) {
                continue;
            }

            // Add thumbnail URL to array
            $images[ $i ]['thumbnail_url'] = $attachment[0];
        }

        return $images;

    }

    /**
     * Retrieves a setting from the Post meta, falling back to the Settings data
     * if this Post has never been saved before (this allows Settings to act as defaults
     * for new Posts)
     *
     * Safely checks if the key(s) exist before returning the default
     * or the value.
     *
     * This function exists so that views/ files, which call $this->get_setting() in both Post
     * and Setting contexts, works correctly, meaning we don't need to duplicate our views.
     *
     * @since   3.0.0
     *
     * @param   string  $post_type  Post Type
     * @param   string  $key        Setting key value to retrieve
     * @param   string  $default    Default Value
     * @return  string              Value/Default Value
     */
    public function get_setting( $post_type = '', $key, $default = '' ) {

        // Get Post ID
        global $post;
        $post_id = $post->ID;

        // Check if the override value exists
        $has_post_level_settings = $this->has_post_level_settings( $post_id );
        if ( ! $has_post_level_settings ) {
            // No settings exist for this Post - populate form with defaults
            return $this->base->get_class( 'settings' )->get_setting( $post_type, $key, $default );
        }

        // If here, the Post has Settings, so fetch data from the Post
        return $this->get_setting_by_post_id( $post_id, $key, $default );

    }

    /**
     * Determines if the given Post ID has Post level settings defined.
     *
     * @since   3.7.8
     *
     * @param   int     $post_id    Post ID
     * @return  bool                Has Post Level Settings
     */
    public function has_post_level_settings( $post_id ) {

        // The 'default' key will exist if Post level settings have been saved,
        // which happens when the User has (at some point) enabled the Override option.
        return $this->get_setting_by_post_id( $post_id, '[default]', false );

    }

    /**
     * Retrieves a setting from the Post meta by a Post ID.
     *
     * Safely checks if the key(s) exist before returning the default
     * or the value.
     *
     * @since   3.0.3
     *
     * @param   mixed   $type       Post Type or ID
     * @param   string  $key        Setting key value to retrieve
     * @param   string  $default    Default Value
     * @return  string              Value/Default Value
     */
    public function get_setting_by_post_id( $post_id, $key, $default = '' ) {

        // Get settings
        $settings = $this->get_settings( $post_id );
        
        // Convert string to keys
        $keys = explode( '][', $key );

        foreach ( $keys as $count => $key ) {
            // Cleanup key
            $key = trim( $key, '[]' );

            // Check if key exists
            if ( ! isset( $settings[ $key ] ) ) {
                return $default;
            }

            // Key exists - make settings the value (which could be an array or the final value)
            // of this key
            $settings = $settings[ $key ];
        }

        // If here, setting exists
        return $settings; // This will be a non-array value

    }

    /**
     * Returns the settings for the given Post
     *
     * @since   3.0.0
     *
     * @param   int     $post_id    Post ID
     * @return  array               Settings
     */
    public function get_settings( $post_id ) {

        // Get current settings
        $settings = get_post_meta( $post_id, $this->base->plugin->name, true );

        /**
         * Filters Status Settings for a specific Post.
         *
         * @since   3.0.0
         *
         * @param   array   $settings   Post Settings
         * @param   int     $post_id    Post ID
         */
        $settings = apply_filters( $this->base->plugin->filter_name . '_get_post_meta', $settings, $post_id );

        // Return result
        return $settings;

    }

}