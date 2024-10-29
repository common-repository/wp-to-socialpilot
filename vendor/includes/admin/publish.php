<?php
/**
 * Post class
 * 
 * @package WP_To_Social_Pro
 * @author  Tim Carr
 * @version 3.0.0
 */
class WP_To_Social_Pro_Publish {

    /**
     * Holds the base class object.
     *
     * @since   3.2.4
     *
     * @var     object
     */
    public $base;

    /**
     * Holds all supported Tags and their Post data replacements.
     *
     * @since   3.7.8
     *
     * @var     array
     */
    private $all_possible_searches_replacements = false;

    /**
     * Holds searches and replacements for status messages.
     *
     * @since   3.7.8
     *
     * @var     array
     */
    private $searches_replacements = false;

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
        add_action( 'wp_loaded', array( $this, 'register_publish_hooks' ), 1 );
        add_action( $this->base->plugin->name, array( $this, 'publish' ), 1, 2 );

    }

    /**
     * Registers publish hooks against all public Post Types,
     *
     * @since   3.0.0
     */
    public function register_publish_hooks() {

        add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );

    }

    /**
     * Fired when a Post's status transitions.  Called by WordPress when wp_insert_post() is called.
     *
     * wp_insert_post() is called by WordPress and the REST API whenever creating or updating a Post.
     *
     * @since   3.1.6
     *
     * @param   string      $new_status     New Status
     * @param   string      $old_status     Old Status
     * @param   WP_Post     $post           Post
     */
    public function transition_post_status( $new_status, $old_status, $post ) {

        // Bail if the Post Type isn't public
        // This prevents the rest of this routine running on e.g. ACF Free, when saving Fields (which results in Field loss)
        $post_types = array_keys( $this->base->get_class( 'common' )->get_post_types() );
        if ( ! in_array( $post->post_type, $post_types ) ) {
            return;
        }

        // New Post Screen loading
        // Draft saved
        if ( $new_status == 'auto-draft' || $new_status == 'draft' || $new_status == 'inherit' || $new_status == 'trash' ) {
            return;
        }

        // Remove actions registered by this Plugin
        // This ensures that when Page Builders call publish or update events via AJAX, we don't run this multiple times
        remove_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );
        remove_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_publish' ), 10 );
        remove_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );
        remove_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_update' ), 10 );

        /**
         * = REST API =
         * If this is a REST API Request, we can't use the wp_insert_post action, because the metadata
         * is *not* included in the call to wp_insert_post().  Instead, we must use a late REST API action
         * that gives the REST API time to save metadata.
         * Note that the meta being supplied in the REST API Request must be registered with WordPress using
         * register_meta()
         *
         * = Gutenberg =
         * If Gutenberg is being used on the given Post Type, two requests are sent:
         * - a REST API request, comprising of Post Data and Metadata registered in Gutenberg,
         * - a standard request, comprising of Post Metadata registered outside of Gutenberg (i.e. add_meta_box() data)
         * The second request will be seen by transition_post_status() as an update.
         * Therefore, we set a meta flag on the first Gutenberg REST API request to defer publishing the status until
         * the second, standard request - at which point, all Post metadata will be available to the Plugin.
         *
         * = Classic Editor =
         * Metadata is included in the call to wp_insert_post(), meaning that it's saved to the Post before we use it.
         */

        // Flag to determine if the current Post is a Gutenberg Post
        $is_gutenberg_post = $this->is_gutenberg_post( $post );
        $is_rest_api_request = $this->is_rest_api_request();
        $this->base->get_class( 'log' )->add_to_debug_log( 'Post ID: #' . $post->ID );
        $this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg Post: ' . ( $is_gutenberg_post ? 'Yes' : 'No' ) );
        $this->base->get_class( 'log' )->add_to_debug_log( 'REST API Request: ' . ( $is_rest_api_request ? 'Yes' : 'No' ) );

        // If a previous request flagged that an 'update' request should be treated as a publish request (i.e.
        // we're using Gutenberg and request to post.php was made after the REST API), do this now.
        $needs_publishing = get_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_publishing', true );
        if ( $needs_publishing ) {
            $this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Needs Publishing' );

            // Run Publish Status Action now
            delete_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_publishing' );
            add_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );

            // Don't need to do anything else, so exit
            return;
        }

        // If a previous request flagged that an update request be deferred (i.e.
        // we're using Gutenberg and request to post.php was made after the REST API), do this now.
        $needs_updating = get_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_updating', true );
        if ( $needs_updating ) {
            $this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Needs Updating' );

            // Run Publish Status Action now
            delete_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_updating' );
            add_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );

            // Don't need to do anything else, so exit
            return;
        }

        // Publish
        if ( $new_status == 'publish' && $new_status != $old_status ) {
            /**
             * Classic Editor
             */
            if ( ! $is_rest_api_request ) {
                $this->base->get_class( 'log' )->add_to_debug_log( 'Classic Editor: Publish' );

                add_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );

                // Don't need to do anything else, so exit
                return;
            }

            /**
             * Gutenberg Editor
             * - Non-Gutenberg metaboxes are POSTed via a second, separate request to post.php, which appears
             * as an 'update'.  Define a meta key that we'll check on the separate request later.
             */
            if ( $is_gutenberg_post ) {
                $this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Defer Publish' );

                update_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_publishing', 1 );
                
                // Don't need to do anything else, so exit
                return;
            }

            /**
             * REST API
             */
            $this->base->get_class( 'log' )->add_to_debug_log( 'REST API: Publish' );
            add_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_publish' ), 10, 2 );

            // Don't need to do anything else, so exit
            return;
        }

        // Update
        if ( $new_status == 'publish' && $old_status == 'publish' ) {
            /**
             * Classic Editor
             */
            if ( ! $is_rest_api_request ) {
                $this->base->get_class( 'log' )->add_to_debug_log( 'Classic Editor: Update' );

                add_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );

                // Don't need to do anything else, so exit
                return;
            }

            /**
             * Gutenberg Editor
             * - Non-Gutenberg metaboxes are POSTed via a second, separate request to post.php, which appears
             * as an 'update'.  Define a meta key that we'll check on the separate request later.
             */
            if ( $is_gutenberg_post ) {
                $this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Defer Update' );

                update_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_updating', 1 );
                
                // Don't need to do anything else, so exit
                return;
            }

            /**
             * REST API
             */
            $this->base->get_class( 'log' )->add_to_debug_log( 'REST API: Update' );
            add_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_update' ), 10, 2 );

            // Don't need to do anything else, so exit
            return;
        }
    
    }

    /**
     * Helper function to determine if the request is a REST API request.
     *
     * @since   3.9.1
     *
     * @return  bool    Is REST API Request
     */
    private function is_rest_api_request() {

        if ( ! defined( 'REST_REQUEST' ) ) {
            return false;
        }

        if ( ! REST_REQUEST ) {
            return false;
        }

        return true;

    }

    /**
     * Helper function to determine if the Post can use, or has used, the Gutenberg Editor.
     *
     * It's never 100% reliable, because:
     * - Post Content may contain Block markup, even though the user reverted back to the Classic Editor,
     * - Just because a Post (i.e. a Post's Post Type) can use the Block Editor, doesn't mean it does!
     *
     * Should be used in conjunction with REST_REQUEST checks; if both are true, we're using Gutenberg.
     *
     * @since   3.6.8
     *
     * @param   WP_Post     $post   Post
     * @return  bool                Post uses Gutenberg Editor
     */
    private function is_gutenberg_post( $post ) {

        // If the Post's content contains Gutenberg block markup, we might be editing a Gutenberg Post
        if ( strpos( $post->post_content, '<!-- wp:' ) !== false ) {
            return true;
        }

        if ( ! post_type_exists( $post->post_type ) ) {
            return false;
        }

        if ( ! post_type_supports( $post->post_type, 'editor' ) ) {
            return false;
        }

        $post_type_object = get_post_type_object( $post->post_type );
        if ( $post_type_object && ! $post_type_object->show_in_rest ) {
            return false;
        }

        /**
         * Filter whether a post is able to be edited in the block editor.
         *
         * @since 5.0.0
         *
         * @param bool   $use_block_editor  Whether the post type can be edited or not. Default true.
         * @param string $post_type         The post type being checked.
         */
        return apply_filters( 'use_block_editor_for_post_type', true, $post->post_type );

    }

    /**
     * Helper function to determine if the Post contains Gutenberg Content.
     *
     * @since   3.9.1
     *
     * @param   WP_Post     $post   Post
     * @return  bool                Post Content contains Gutenberg Block Markup
     */
    private function is_gutenberg_post_content( $post ) {

        if ( strpos( $post->post_content, '<!-- wp:' ) !== false ) {
            return true;
        }

        return false;

    }

    /**
     * Called when a Post has been Published via the REST API
     *
     * @since   3.6.8
     *
     * @param   WP_Post             $post           Post
     * @param   WP_REST_Request     $request        Request Object
     */
    public function rest_api_post_publish( $post, $request ) {

        $this->wp_insert_post_publish( $post->ID );

    }

    /**
     * Called when a Post has been Published via the REST API
     *
     * @since   3.6.8
     *
     * @param   WP_Post             $post           Post
     * @param   WP_REST_Request     $request        Request Object
     */
    public function rest_api_post_update( $post, $request ) {

        $this->wp_insert_post_update( $post->ID );

    }

    /**
     * Called when a Post has been Published
     *
     * @since   3.6.2
     *
     * @param   int     $post_id    Post ID
     */
    public function wp_insert_post_publish( $post_id ) {

        // Call main function to publish status(es) to social media
        $results = $this->publish( $post_id, 'publish' );

        // If no result, bail
        if ( ! isset( $results ) ) {
            return;
        }

        // If no errors, return
        if ( ! is_wp_error( $results ) ) {
            return;
        }

        // If logging is disabled, return
        $log_enabled = $this->base->get_class( 'settings' )->get_option( 'log' );
        if ( ! $log_enabled ) {
            return;
        }

        // If logging is disabled, return
        $log_enabled = $this->base->get_class( 'settings' )->get_option( 'log', '[enabled]' );
        if ( ! $log_enabled ) {
            return;
        }

        // The result is a single warning caught before any statuses were sent to the API
        // Add the warning to the log so that the user can see why no statuses were sent to API
        $this->base->get_class( 'log' )->add( $post_id, array(
            'action'            => 'publish',
            'request_sent'      => date( 'Y-m-d H:i:s' ),
            'result'            => 'warning',
            'result_message'    => $results->get_error_message(),
        ) );

    }

    /**
     * Called when a Post has been Updated
     *
     * @since   3.6.2
     *
     * @param   int     $post_id    Post ID
     */
    public function wp_insert_post_update( $post_id ) {

        // If a status was last sent within 5 seconds, don't send it again
        // Prevents Page Builders that trigger wp_update_post() multiple times on Publish or Update from
        // causing statuses to send multiple times
        $last_sent = get_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_last_sent', true );
        if ( ! empty( $last_sent ) ) {
            $difference = ( current_time( 'timestamp' ) - $last_sent );
            if ( $difference < 5 ) {
                return;
            }
        }
        
        // Call main function to publish status(es) to social media
        $results = $this->publish( $post_id, 'update' );

        // If no result, bail
        if ( ! isset( $results ) ) {
            return;
        }

        // If no errors, return
        if ( ! is_wp_error( $results ) ) {
            return;
        }

        // If logging is disabled, return
        $log_enabled = $this->base->get_class( 'settings' )->get_option( 'log', '[enabled]' );
        if ( ! $log_enabled ) {
            return;
        }

        // The result is a single error caught before any statuses were sent to the API
        // Add the error to the log so that the user can see why no statuses were sent to API
        $this->base->get_class( 'log' )->add( $post_id, array(
            'action'            => 'update',
            'request_sent'      => date( 'Y-m-d H:i:s' ),
            'result'            => 'warning',
            'result_message'    => $results->get_error_message(),
        ) );

    }

    /**
     * Main function. Called when any Page, Post or CPT is published or updated
     *
     * @since   3.0.0
     *
     * @param   int         $post_id                Post ID
     * @param   string      $action                 Action (publish|update|repost|bulk_publish)
     * @param   bool        $test_mode              Test Mode (won't send to API)
     * @return  mixed                               WP_Error | API Results array
     */
    public function publish( $post_id, $action, $test_mode = false ) {

        // Bail if the action isn't supported
        $supported_actions = array_keys( $this->base->get_class( 'common' )->get_post_actions() );
        if ( ! in_array( $action, $supported_actions ) ) {
            return new WP_Error( 'wp_to_social_pro_publish_invalid_action', sprintf( __( 'The %s action is not supported.', $this->base->plugin->name ), $action ) );
        }

        // Clear any cached data that we have stored in this class
        $this->clear_search_replacements();

        // Get Post
        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'no_post', sprintf( __( 'No WordPress Post could be found for Post ID %s', $this->base->plugin->name ), $post_id ) );
        }

        // Determine post type
        $post_type = $post->post_type;

        // Use Plugin Settings
        $settings = $this->base->get_class( 'settings' )->get_settings( $post_type );

        // Check settings exist
        // If not, this means the CPT or Post-level settings have not been configured, so we
        // don't need to do anything
        if ( ! $settings ) {
            return false;
        }

        // Check a valid access token exists
        $access_token = $this->base->get_class( 'settings' )->get_access_token();
        $refresh_token = $this->base->get_class( 'settings' )->get_refresh_token();
        $expires = $this->base->get_class( 'settings' )->get_token_expires();
        if ( ! $access_token ) {
            return new WP_Error( 'no_access_token', sprintf( __( 'The Plugin has not been authorized with %s! Go to %s > Settings to setup the plugin.', $this->base->plugin->name ), $this->base->plugin->account, $this->base->plugin->displayName ) );
        }

        // Setup API
        $this->base->get_class( 'api' )->set_tokens( $access_token, $refresh_token, $expires );

        // Get Profiles
        $profiles = $this->base->get_class( 'api' )->profiles( false, $this->base->get_class( 'common' )->get_transient_expiration_time() );

        // Bail if the Profiles could not be fetched
        if ( is_wp_error( $profiles ) ) {
            return $profiles;
        }

        // Array for storing statuses we'll send to the API
        $statuses = array();

        // Iterate through each social media profile
        foreach ( $settings as $profile_id => $profile_settings ) {

            // Skip some setting keys that aren't related to profiles
            if ( in_array( $profile_id, array( 'featured_image', 'additional_images', 'override' ) ) ) {
                continue;
            }

            // Skip if the Profile ID does not exist in the $profiles array, it's been removed from the API
            if ( $profile_id != 'default' && ! isset( $profiles[ $profile_id ] ) ) {
                continue;
            }

            // If the Profile's ID belongs to a Google Social Media Profile, skip it, as this is no longer supported
            // as Google+ closed down.
            if ( $profile_id != 'default' && $profiles[ $profile_id ]['service'] == 'google' ) {
                continue;
            }

            // Get detailed settings from Post or Plugin
            // Use Plugin Settings
            $profile_enabled = $this->base->get_class( 'settings' )->get_setting( $post_type, '[' . $profile_id . '][enabled]', 0 );
            $profile_override = $this->base->get_class( 'settings' )->get_setting( $post_type, '[' . $profile_id . '][override]', 0 );

            // Either use override settings (or if Pinterest, always use override settings)
            if ( $profile_override || ( isset( $profiles[ $profile_id ] ) && $profiles[ $profile_id ]['service'] == 'pinterest' ) ) {
                $action_enabled = $this->base->get_class( 'settings' )->get_setting( $post_type, '[' . $profile_id . '][' . $action . '][enabled]', 0 );
                $status_settings = $this->base->get_class( 'settings' )->get_setting( $post_type, '[' . $profile_id . '][' . $action . '][status]', array() );
            } else {
                $action_enabled = $this->base->get_class( 'settings' )->get_setting( $post_type, '[default][' . $action . '][enabled]', 0 );
                $status_settings = $this->base->get_class( 'settings' )->get_setting( $post_type, '[default][' . $action . '][status]', array() );
            }
           
            // Check if this profile is enabled
            if ( ! $profile_enabled ) {
                continue;
            }

            // Check if this profile's action is enabled
            if ( ! $action_enabled ) {
                continue;
            }

            // Determine which social media service this profile ID belongs to
            foreach ( $profiles as $profile ) {
                if ( $profile['id'] == $profile_id ) {
                    $service = $profile['service'];
                    break;
                }
            }

            // Iterate through each Status
            foreach ( $status_settings as $index => $status ) {
                $statuses[] = $this->build_args( $post, $profile_id, $service, $status, $action );
            }

        }

        // Debugging
        $this->base->get_class( 'log' )->add_to_debug_log( $statuses );

        // Check if any statuses exist
        // If not, exit
        if ( count( $statuses ) == 0 ) {
            // Fetch Post Type object and Settings URL
            $post_type_object = get_post_type_object( $post->post_type );
            $plugin_url = admin_url( 'admin.php?page=' . $this->base->plugin->name . '-settings&tab=post&type=' . $post->post_type );
            $post_url = admin_url( 'post.php?post=' . $post_id );

            // Return an error, depending on why no statuses were found
            if ( isset( $conditions_met ) && ! $conditions_met ) {
                return new WP_Error( 
                    $this->base->plugin->filter_name . '_no_statuses_conditions', 
                    sprintf( 
                        __( 'Status(es) exist for sending this %s to %s when you %s a %s, but no status was sent because the %s did not meet the status conditions. If you want this %s to be sent to %s, navigate to <a href="%s" target="_blank">%s > Settings > %s Tab > %s Action Tab</a>, ensuring that no Conditions are set on the defined statuses.', $this->base->plugin->name ), 
                        $post_type_object->labels->singular_name,
                        $this->base->plugin->account, 
                        ucfirst( $action ),
                        $post_type_object->labels->singular_name,
                        $post_type_object->labels->singular_name,
                        
                        $post_type_object->labels->singular_name,
                        $this->base->plugin->account, 
                        $plugin_url,
                        $this->base->plugin->displayName,
                        $post_type_object->labels->name,
                        ucfirst( $action )
                    )
                );
            }
        }

        /**
         * Determine the statuses to send, just before they're sent. Statuses can be added, edited
         * and/or deleted as necessary here.
         *
         * @since   3.0.0
         *
         * @param   array   $statuses   Statuses to be sent to social media
         * @param   int     $post_id    Post ID
         * @param   string  $action     Action (publish, update, repost)
         */
        $statuses = apply_filters( $this->base->plugin->filter_name . '_publish_statuses', $statuses, $post_id, $action );

        // Send status messages to the API
        $results = $this->send( $statuses, $post_id, $action, $profiles, $test_mode );

        // If no results, we're finished
        if ( empty( $results ) || count( $results ) == 0 ) {
            return false;
        }

        // If here, all OK
        return $results;

    }

    /**
     * Helper method to build arguments and create a status via the API
     *
     * @since   3.0.0
     *
     * @param   obj     $post                       Post
     * @param   string  $profile_id                 Profile ID
     * @param   string  $service                    Service
     * @param   array   $status                     Status Settings
     * @param   string  $action                     Action (publish|update)
     * @return  bool                                Success
     */
    private function build_args( $post, $profile_id, $service, $status, $action ) {

        // Build each API argument
        // Profile ID
        $args = array(
            'profile_ids'   => array( $profile_id ),
        );

        // Get the character limit for the status text based on the profile's service
        $character_limit = $this->base->get_class( 'common' )->get_character_limit( $service );

        // Text
        $args['text'] = $this->parse_text( $post, $status['message'], $character_limit );

        // Shorten URLs
        $args['shorten'] = true;

        // Schedule
        switch( $status['schedule'] ) {

            case 'queue_bottom':
                // This is the default for the API, so nothing more to do here
                break;

            case 'queue_top':
                $args['top'] = true;
                break;

            case 'now':
                $args['now'] = true;
                break;

        }

        // Media
        // Get Featured Image, falling back to Image from Post Content if no Featured Image and we're
        // posting to Pinterest or Instagram (which would fail without an image)
        $use_image_from_content = ( ( $service == 'pinterest' || $service == 'instagram' ) ? true : false );
        $featured_image = $this->get_post_image( $post, $use_image_from_content );

        // Change the Featured Image setting if it's an invalid value for the service
        // This happens when e.g. Defaults are set, but per-service settings aren't
        switch ( $service ) {
            /**
             * Twitter
             * - Force Use Feat. Image, not Linked to Post if Use Feat. Image, Linked to Post chosen
             */
            case 'twitter':
                if ( $status['image'] == 1 ) {
                    $status['image'] = 2;
                }
                break;

            /**
             * Pinterest, Instagram
             * - Always force Use Feat. Image, not Linked to Post
             */
            case 'pinterest':
            case 'instagram':
                $status['image'] = 2;
                break;

        }

        // If we have a Featured Image, add it to the Status is required
        if ( $featured_image != false ) {
            switch ( $status['image'] ) {

                /**
                 * No Image
                 */
                case -1:
                    break;

                /**
                 * Use OpenGraph Settings
                 * - Don't specify Media, as the service will scrape the URL for OpenGraph image tags
                 */
                case 0:
                case '':
                    break;

                /**
                 * Use Feat. Image, Linked to Post
                 * - Facebook, LinkedIn
                 */
                case 1:
                    // Fetch Title and Excerpt
                    $title   = $this->get_title( $post );
                    $excerpt = $this->get_excerpt( $post );
              
                    $args['media'] = array(
                        'link'          => get_permalink( $post->ID ),
                        'description'   => $excerpt,
                        'title'         => $title,
                        'picture'       => $featured_image['image'],

                        // Dashboard Thumbnail
                        // Not supplied, as may results in cURL timeouts
                    );
                    break;

                /**
                 * Use Feat. Image, not Linked to Post
                 * - Facebook, LinkedIn, Twitter, Instagram, Pinterest
                 */
                case 2:
                    // Fetch Title and Excerpt
                    $title   = $this->get_title( $post );
                    $excerpt = $this->get_excerpt( $post );
                    
                    $args['media'] = array(
                        'description'   => $excerpt,
                        'title'         => $title,
                        'picture'       => $featured_image['image'],

                        // Dashboard Thumbnail
                        // Supplied, as required when specifying media with no link
                        // Using the smallest possible image to avoid cURL timeouts
                        'thumbnail'     => $featured_image['thumbnail'],
                    );
                    break;

            }
        }

        /**
         * Determine the standardised arguments array to send via the API for a status message's settings.
         *
         * @since   3.0.0
         *
         * @param   array       $args                       API standardised arguments.
         * @param   WP_Post     $post                       WordPress Post
         * @param   string      $profile_id                 Social Media Profile ID
         * @param   string      $service                    Social Media Service
         * @param   array       $status                     Parsed Status Message Settings
         */
        $args = apply_filters( $this->base->plugin->filter_name . '_publish_build_args', $args, $post, $profile_id, $service, $status );

        // Return args
        return $args;

    }

    /**
     * Attempts to fetch the given Post's Image, in the following order:
     * - Plugin's First (Featured) Image
     * - Post's Featured Image
     * - Post's first image in content (if $use_image_from_content = true)
     *
     * @since   3.9.8
     *
     * @param   WP_Post     $post                       Post ID
     * @param   bool        $use_image_from_content     Use Image from Content, if Featured Image not defined
     * @return  mixed                                   false | array
     */
    private function get_post_image( $post, $use_image_from_content = false ) {

        // Plugin's First (Featured) Image
        $image_id = $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, 'featured_image' );
        if ( $image_id > 0 ) {
            return $this->get_image_sources( $image_id, 'plugin' );
        }

        // Featured Image
        $image_id = get_post_thumbnail_id( $post->ID );
        if ( $image_id > 0 ) {
            return $this->get_image_sources( $image_id, 'featured_image' );
        }

        // If here, no Featured Image was found and we don't need to check the content for an image
        if ( ! $use_image_from_content ) {
            return false;
        }

        // Content's First Image
        $images = preg_match_all( '/<img.+?src=[\'"]([^\'"]+)[\'"].*?>/i', $post->post_content, $matches );
        if ( $images ) {
            return array(
                'image'     => $matches[1][0],
                'thumbnail' => $matches[1][0],
                'source'    => 'post_content',
            );
        }

        // If here, no image was found in the Post
        return false;

    }

    /**
     * Returns the large and thumbnail image sizes for the given Attachment ID
     *
     * @since   3.9.8
     *
     * @param   int     $image_id   Image ID
     * @param   string  $source     Source Image ID was derived from (plugin, featured_image, post_content)
     * @return  array               Image URLs
     */
    private function get_image_sources( $image_id, $source ) {

        // Get image sources
        $image = wp_get_attachment_image_src( $image_id, 'large' );
        $thumbnail = wp_get_attachment_image_src( $image_id, 'thumbnail' );

        // Return URLs only
        return array(
            'image'     => $image[0],
            'thumbnail' => $thumbnail[0],
            'source'    => $source,
        );

    }

    /**
     * Populates the status message by replacing tags with Post/Author data
     *
     * @since   3.0.0
     *
     * @param   WP_Post     $post               Post
     * @param   string      $message            Status Message to parse
     * @param   int         $character_limit    Character Limit
     * @return  string                          Parsed Status Message
     */
    public function parse_text( $post, $message, $character_limit = 0 ) {

        // Get Author
        $author = get_user_by( 'id', $post->post_author );

        // If we haven't yet populated the searches and replacements for this Post, do so now
        if ( ! $this->all_possible_searches_replacements ) {
            $this->all_possible_searches_replacements = $this->register_all_possible_searches_replacements( $post, $author );
        }

        // If no searches and replacements are defined, we can't parse anything
        if ( ! $this->all_possible_searches_replacements || count( $this->all_possible_searches_replacements ) == 0 ) {
            return $message;
        }

        // Extract all of the tags in the message
        preg_match_all( "|{(.+?)}|", $message, $matches );

        // If no tags exist in the message, there's nothing to parse
        if ( ! is_array( $matches ) ) {
            return $message;
        }
        if ( count( $matches[0] ) == 0 ) {
            return $message;
        }

        // Define return text
        $text = $message;

        // Iterate through extracted tags to build the search / replacement array
        foreach ( $matches[1] as $index => $tag ) {
            // Define some default attributes for this tag
            $tag_params = array(
                'tag_with_braces'       => $matches[0][ $index ],
                'tag_without_braces'    => $tag,
                'tag'                   => $tag,
                'character_limit'       => false,
                'word_limit'            => false,
            );

            // If we already have a replacement for this exact tag (i.e. from a previous status message),
            // we don't need to define the replacement again.
            if ( isset( $this->searches_replacements[ $tag_params['tag_with_braces'] ] ) ) {
                continue;
            }

            // If a word or character limit is defined, fetch it now
            if ( preg_match( "/(.*?)\((.*?)_words\)/", $tag, $word_limit_matches ) ) {
                $tag_params['tag'] = $word_limit_matches[1];
                $tag_params['word_limit'] = absint( $word_limit_matches[2] );
            } elseif ( preg_match( "/(.*?)\((.*?)\)/", $tag, $character_limit_matches ) ) {
                $tag_params['tag'] = $character_limit_matches[1];
                $tag_params['character_limit'] = absint( $character_limit_matches[2] );
            }

            // Fetch possible tag replacement value
            $replacement = ( isset( $this->all_possible_searches_replacements[ $tag_params['tag'] ] ) ? $this->all_possible_searches_replacements[ $tag_params['tag'] ] : '' );

            // If a word or character limit is defined, apply it now, provided it's not a tag that prevents character limiting
            $can_apply_limit_to_tag = $this->can_apply_character_limit_to_tag( $tag_params['tag'] );
            if ( $can_apply_limit_to_tag ) {
                if ( $tag_params['word_limit'] > 0 ) {
                    $replacement = $this->apply_word_limit( $replacement, $tag_params['word_limit'] );
                } elseif ( $tag_params['character_limit'] > 0 ) {
                    $replacement = $this->apply_character_limit( $replacement, $tag_params['character_limit'] );
                }
            }

            // Add the search and replacement to the array
            $this->searches_replacements[ $tag_params['tag_with_braces'] ] = trim( $replacement );

        } // Close foreach tag match in text

        // Search and Replace
        $text = str_replace( array_keys( $this->searches_replacements ), $this->searches_replacements, $text );

        // Execute any shortcodes in the text now
        $text = do_shortcode( $text );

        // Remove double spaces
        $text = str_replace( '  ', '', $text );

        /**
         * Filters the parsed status message text on a status.
         *
         * @since   3.0.0
         *
         * @param   string      $text                                       Parsed Text, no Tags
         * @param   string      $message                                    Unparsed Text with Tags
         * @param   array       $this->searches_replacements                Specific Tag Search and Replacements for the given Text
         * @param   array       $this->all_possible_searches_replacements   All Registered Tag Search and Replacements
         * @param   WP_Post     $post                                       WordPress Post
         * @param   WP_User     $author                                     WordPress User (Author)
         */
        $text = apply_filters( $this->base->plugin->filter_name . '_publish_parse_text', $text, $message, $this->searches_replacements, $this->all_possible_searches_replacements, $post, $author );

        // Return text
        return $text;

    }

    /**
     * Returns an array comprising of all supported tags and their Post / Author / Taxonomy data replacements.
     *
     * @since   3.7.8
     *
     * @param   WP_Post     $post       WordPress Post
     * @param   WP_User     $author     WordPress User (Author of the Post)
     * @return  array                   Search / Replacement Key / Value pairs
     */
    private function register_all_possible_searches_replacements( $post, $author ) {

        // Start with no searches or replacements
        $searches_replacements = array();

        // Register Post Tags and Replacements
        $searches_replacements = $this->register_post_searches_replacements( $searches_replacements, $post );

        /**
         * Registers any additional status message tags, and their Post data replacements, that are supported.
         *
         * @since   3.7.8
         *
         * @param   array       $searches_replacements  Registered Supported Tags and their Replacements
         * @param   WP_Post     $post                   WordPress Post
         * @param   WP_User     $author                 WordPress User (Author of the Post)
         */
        $searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_get_all_possible_searches_replacements', $searches_replacements, $post, $author );

        // Return filtered results
        return $searches_replacements;

    }

    /**
     * Registers status message tags and their data replacements for the given Post.
     *
     * @since   3.7.8
     *
     * @param   array       $searches_replacements  Registered Supported Tags and their Replacements
     * @param   WP_Post     $post                   WordPress Post
     */
    private function register_post_searches_replacements( $searches_replacements, $post ) {

        $searches_replacements['sitename']  = get_bloginfo( 'name' );
        $searches_replacements['title']     = $this->get_title( $post );
        $searches_replacements['excerpt']   = $this->get_excerpt( $post );
        $searches_replacements['content']   = $this->get_content( $post );
        $searches_replacements['date']      = date( 'dS F Y', strtotime( $post->post_date ) );
        $searches_replacements['url']       = rtrim( get_permalink( $post->ID ), '/' );
        $searches_replacements['id']        = absint( $post->ID );

        /**
         * Registers any additional status message tags, and their Post data replacements, that are supported
         * for the given Post.
         *
         * @since   3.7.8
         *
         * @param   array       $searches_replacements  Registered Supported Tags and their Replacements
         * @param   WP_Post     $post                   WordPress Post
         */
        $searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_register_post_searches_replacements', $searches_replacements, $post );

        // Return filtered results
        return $searches_replacements;

    }

    /**
     * Safely generate a title, stripping tags and shortcodes, and applying filters so that
     * third party plugins (such as translation plugins) can determine the final output.
     *
     * @since   3.7.3
     *
     * @param   WP_Post     $post               WordPress Post
     * @return  string                          Title
     */
    private function get_title( $post ) {

        // Define title
        $title = html_entity_decode( strip_tags( strip_shortcodes( get_the_title( $post ) ) ) );

        /**
         * Filters the dynamic {title} replacement, when a Post's status is being built.
         *
         * @since   3.7.3
         *
         * @param   string      $title      Post Title
         * @param   WP_Post     $post       WordPress Post
         */
        $title = apply_filters( $this->base->plugin->filter_name . '_publish_get_title', $title, $post );

        // Return
        return $title;

    }

    /**
     * Safely generate an excerpt, stripping tags, shortcodes, falling back 
     * to the content if the Post Type doesn't have excerpt support, and applying filters so that
     * third party plugins (such as translation plugins) can determine the final output.
     *
     * @since   3.7.3
     *
     * @param   WP_Post     $post               WordPress Post
     * @return  string                          Excerpt
     */
    private function get_excerpt( $post ) {

        // Fetch excerpt
        if ( empty( $post->post_excerpt ) ) {
            $excerpt = $post->post_content;
        } else {
            $excerpt = apply_filters( 'get_the_excerpt', $post->post_excerpt, $post );
        }

        // Strip shortcodes
        $excerpt = strip_shortcodes( $excerpt );

        // Strip HTML Tags
        $excerpt = strip_tags( $excerpt );

        // Decode excerpt to avoid encoding issues on status output
        $excerpt = html_entity_decode( $excerpt );

        // Finally, trim the output
        $excerpt = trim( $excerpt );

        /**
         * Filters the dynamic {excerpt} replacement, when a Post's status is being built.
         *
         * @since   3.7.3
         *
         * @param   string      $excerpt    Post Excerpt
         * @param   WP_Post     $post       WordPress Post
         */
        $excerpt = apply_filters( $this->base->plugin->filter_name . '_publish_get_excerpt', $excerpt, $post );

        // Return
        return $excerpt;

    }

    /**
     * Safely generate a title, stripping tags and shortcodes, and applying filters so that
     * third party plugins (such as translation plugins) can determine the final output.
     *
     * @since   3.7.3
     *
     * @param   WP_Post     $post               WordPress Post
     * @return  string                          Excerpt
     */
    private function get_content( $post ) {

        // Fetch content
        // get_the_content() only works for WordPress 5.2+, which added the $post param
        $content = $post->post_content;

        // Strip shortcodes
        $content = strip_shortcodes( $content );

        // Apply filters to get true output
        $content = apply_filters( 'the_content', $content );

        // If the content originates from Gutenberg, remove double newlines and convert breaklines
        // into newlines
        $is_gutenberg_post_content = $this->is_gutenberg_post_content( $post );
        if ( $is_gutenberg_post_content ) {
            // Remove double newlines, which may occur due to using Gutenberg blocks
            // (blocks are separated with HTML comments, stripped using apply_filters( 'the_content' ), which results in double, or even triple, breaklines)
            $content = preg_replace('/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n\n", $content );

            // Convert <br> and <br /> into newlines
            $content = preg_replace( '/<br(\s+)?\/?>/i', "\n", $content );
        }

        // Strip HTML Tags
        $content = strip_tags( $content );

        // Decode content to avoid encoding issues on status output
        $content = html_entity_decode( $content );

        // Finally, trim the output
        $content = trim( $content );

        /**
         * Filters the dynamic {content} replacement, when a Post's status is being built.
         *
         * @since   3.7.3
         *
         * @param   string      $content                    Post Content
         * @param   WP_Post     $post                       WordPress Post
         * @param   bool        $is_gutenberg_post_content  Is Gutenberg Post Content
         */
        $content = apply_filters( $this->base->plugin->filter_name . '_publish_get_content', $content, $post, $is_gutenberg_post_content );

        // Return
        return $content;

    }

    /**
     * Returns a flag denoting whether a character limit can safely be applied
     * to the given tag.
     *
     * @since   3.7.8
     *
     * @param   string  $tag    Tag
     * @return  bool            Can apply character limit
     */
    private function can_apply_character_limit_to_tag( $tag ) {

        // Get Tags
        $tags = $this->base->get_class( 'common' )->get_tags_excluded_from_character_limit();

        // If the tag is in the array of tags excluded from character limits, we
        // cannot apply a character limit to this tag
        if ( in_array( $tag, $tags ) ) {
            return false;
        }

        // Can apply character limit to tag
        return true;

    }

    /**
     * Applies the given word limit to the given text
     *
     * @since   3.8.9
     *
     * @param   string  $text          Text
     * @param   int     $word_limit    Word Limit
     * @return  string                 Text
     */
    private function apply_word_limit( $text, $word_limit = 0 ) {

        // Bail if the word limit is zero or false
        if ( ! $word_limit || $word_limit == 0 ) {
            return $text;
        }

        // Limit text
        $text = wp_trim_words( $text, $word_limit, '' );

        /**
         * Filters the character word text.
         *
         * @since   3.8.9
         *
         * @param   string      $text           Text, with word limit applied
         * @param   int         $word_limit     Word Limit used
         */
        $text = apply_filters( $this->base->plugin->filter_name . '_publish_apply_word_limit', $text, $word_limit );

        // Return
        return $text;

    }

    /**
     * Applies the given character limit to the given text
     *
     * @sine    3.7.3
     *
     * @param   string  $text               Text
     * @param   int     $character_limit    Character Limit
     * @return  string                      Text
     */
    private function apply_character_limit( $text, $character_limit = 0 ) {

        // Bail if the character limit is zero or false
        if ( ! $character_limit || $character_limit == 0 ) {
            return $text;
        }

        // Bail if the content isn't longer than the character limit
        if ( strlen( $text ) <= $character_limit ) {
            return $text;
        }

        // Limit text
        $text = substr( $text, 0, $character_limit );
        
        /**
         * Filters the character limited text.
         *
         * @since   3.7.3
         *
         * @param   string      $text               Text, with character limit applied
         * @param   int         $character_limit    Character Limit used
         */
        $text = apply_filters( $this->base->plugin->filter_name . '_publish_apply_character_limit', $text, $character_limit );

        // Return
        return $text;

    }

    /**
     * Helper method to iterate through statuses, sending each via a separate API call
     * to the API
     *
     * @since   3.0.0
     *
     * @param   array $ statuses    Statuses
     * @param   int     $post_id    Post ID
     * @param   string  $action     Action
     * @param   array   $profiles   All Enabled Profiles
     * @param   bool    $test_mode  Test Mode (won't send to API)
     * @return  array               API Result for each status
     */
    public function send( $statuses, $post_id, $action, $profiles, $test_mode = false ) {

        // Assume no errors
        $errors = false;

        // Setup API
        $this->base->get_class( 'api' )->set_tokens( 
            $this->base->get_class( 'settings' )->get_access_token(),
            $this->base->get_class( 'settings' )->get_refresh_token(),
            $this->base->get_class( 'settings' )->get_token_expires()
        );

        // Setup logging
        $logs = array();
        $log_error = array();
        $log_enabled = $this->base->get_class( 'settings' )->get_option( 'log', '[enabled]' );

        foreach ( $statuses as $index => $status ) {
            // If this is a test, add to the log array only
            if ( $test_mode ) {
                $logs[] = array(
                    'action'            => $action,
                    'request_sent'      => date( 'Y-m-d H:i:s' ),
                    'profile_id'        => $status['profile_ids'][0],
                    'profile_name'      => $profiles[ $status['profile_ids'][0] ]['formatted_service'] . ': ' . $profiles[ $status['profile_ids'][0] ]['formatted_username'],
                    'result'            => 'test_mode',
                    'result_message'    => '',
                    'status_text'       => $status['text'],
                );

                continue;
            }

            // Send request
            $result = $this->base->get_class( 'api' )->updates_create( $status );

            // Store result in log array
            if ( is_wp_error( $result ) ) {
                // Error
                $errors = true;
                $logs[] = array(
                    'action'            => $action,
                    'request_sent'      => date( 'Y-m-d H:i:s' ),
                    'profile_id'        => $status['profile_ids'][0],
                    'profile_name'      => $profiles[ $status['profile_ids'][0] ]['formatted_service'] . ': ' . $profiles[ $status['profile_ids'][0] ]['formatted_username'],
                    'result'            => 'error',
                    'result_message'    => $result->get_error_message(),
                    'status_text'       => $status['text'],
                );
                $log_error[] = ( $profiles[ $status['profile_ids'][0] ]['formatted_service'] . ': ' . $profiles[ $status['profile_ids'][0] ]['formatted_username'] . ': ' . $result->get_error_message() );
            } else {
                // OK
                $logs[] = array(
                    'action'            => $action,
                    'request_sent'      => date( 'Y-m-d H:i:s' ),
                    'profile_id'        => $result['profile_id'],
                    'profile_name'      => $profiles[ $status['profile_ids'][0] ]['formatted_service'] . ': ' . $profiles[ $status['profile_ids'][0] ]['formatted_username'],
                    'result'            => 'success',
                    'result_message'    => $result['message'],
                    'status_text'       => $result['status_text'],
                    'status_created_at' => date( 'Y-m-d H:i:s', $result['status_created_at'] ),
                    'status_due_at'     => date( 'Y-m-d H:i:s', $result['due_at'] ),
                );
            }
        }

        // Set the last sent timestamp, which we may use to prevent duplicate statuses
        update_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_last_sent', current_time( 'timestamp' ) );

        // If no errors were reported, set a meta key to show a success message
        // This triggers admin_notices() to tell the user what happened
        if ( ! $errors ) {
            update_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_success', 1 );
            delete_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_error' );
            delete_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_errors' );

            // If we're reposting, update the last reposted date against the Post
            if ( $action == 'repost' ) {
                $this->base->get_class( 'repost' )->update_last_reposted_date( $post_id );
            }

            // Request that the user review the plugin. Notification displayed later,
            // can be called multiple times and won't re-display the notification if dismissed.
            $this->base->dashboard->request_review();
        } else {
            update_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_success', 0 );
            update_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_error', 1 );
            update_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_errors', $log_error );
        }
          
        // Save the log, if logging is enabled
        if ( $log_enabled ) {
            foreach ( $logs as $log ) {
                $this->base->get_class( 'log' )->add( $post_id, $log );
            }
        }

        // Return log results
        return $logs;
        
    }

    /**
     * Clears any searches and replacements stored in this class.
     *
     * @since   3.8.0
     */
    private function clear_search_replacements() {

        $this->all_possible_searches_replacements = false;
        $this->searches_replacements = false;

    }

}