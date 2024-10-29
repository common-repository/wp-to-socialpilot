<?php
/**
 * Administration class
 * 
 * @package WP_To_Social_Pro
 * @author  Tim Carr
 * @version 3.0.0
 */
class WP_To_Social_Pro_Admin {

    /**
     * Holds the base class object.
     *
     * @since   3.2.0
     *
     * @var     object
     */
    public $base;

    /**
     * Holds the success and error messages
     *
     * @since   3.2.6
     *
     * @var     array
     */
    public $notices = array(
        'success'   => array(),
        'error'     => array(),
    );

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
        add_action( 'init', array( $this, 'oauth' ) );
        add_action( 'init', array( $this, 'check_plugin_setup' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_css' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'plugins_loaded', array( $this, 'load_language_files' ) );

    }

    /**
     * Stores the access token if supplied, showing a success message
     * Displays any errors from the oAuth process
     *
     * @since   3.3.3
     */
    public function oauth() {

        // Setup notices class
        $this->base->get_class( 'notices' )->set_key_prefix( $this->base->plugin->filter_name . wp_get_current_user()->ID );

        // If we've returned from the oAuth process and an error occured, add it to the notices
        if ( isset( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-error' ] ) ) {
            switch( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-error' ] ) {
                /**
                 * Access Denied
                 * - User denied our app access
                 */
                case 'access_denied':
                    $this->base->get_class( 'notices' )->add_error_notice( 
                        sprintf( 
                            __( 'You did not grant our Plugin access to your %s account. We are unable to post to %s until you do this. Please click on the Authorize Plugin button.', $this->base->plugin->name ), 
                            $this->base->plugin->account, 
                            $this->base->plugin->account
                        )
                    );
                    break;

                /**
                 * Invalid Grant
                 * - A parameter sent by the oAuth gateway is wrong
                 */
                case 'invalid_grant':
                    $this->base->get_class( 'notices' )->add_error_notice( 
                        sprintf( 
                            __( 'We were unable to complete authentication with %s.  Please try again, or <a href="%s" target="_blank">contact us for support</a>.', $this->base->plugin->name ), 
                            $this->base->plugin->account, 
                            $this->base->plugin->support_url
                        )
                    );
                    break;

                /**
                 * Expired Token
                 * - The oAuth gateway did not exchange the code for an access token within 30 seconds
                 */
                case 'expired_token':
                    $this->base->get_class( 'notices' )->add_error_notice( 
                        sprintf( __( 'The oAuth process has expired.  Please try again, or <a href="%s" target="_blank">contact us for support</a> if this issue persists.', $this->base->plugin->name ), 
                            $this->base->plugin->support_url
                        )
                    );
                    break;

                /**
                 * Other Error
                 */
                default:
                    $this->base->get_class( 'notices' )->add_error_notice( 
                        esc_html( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-error' ] )
                    );
                    break;
            }
        }

        // If an Access Token is included in the request, store it and show a success message
        if ( isset( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-access-token' ] ) ) {
            // Define expiry
            $expiry = sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-expires' ] );
            if ( $expiry > 0 ) {
                $expiry = strtotime( '+' . sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-expires' ] ) . ' seconds' );
            }
            // Setup API
            $this->base->get_class( 'api' )->set_tokens(
                sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-access-token' ] ),
                sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-refresh-token' ] ),
                $expiry
            );

            // Fetch Profiles
            $profiles = $this->base->get_class( 'api' )->profiles( true, $this->base->get_class( 'common' )->get_transient_expiration_time() );

            // If something went wrong, show an error
            if ( is_wp_error( $profiles ) ) {
                $this->base->get_class( 'notices' )->add_error_notice( $profiles->get_error_message() );
                return;
            }

            // Test worked! Save Tokens and Expiry
            $this->base->get_class( 'settings' )->update_tokens( 
                sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-access-token' ] ),
                sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-refresh-token' ] ),
                $expiry
            );

            // Get Settings for Posts
            $settings = $this->base->get_class( 'settings' )->get_settings( 'post' );

            // If any Profiles are enabled by defaut on the API service, enable them on Posts now
            foreach ( $profiles as $profile ) {
                // Skip if no default key set
                if ( ! isset( $profile['default'] ) ) {
                    continue;
                }

                // Skip if default is false
                if ( ! $profile['default'] ) {
                    continue;
                }

                // Enable this Profile on Posts
                if ( ! isset( $settings[ $profile['id'] ] ) ) {
                    $settings[ $profile['id'] ] = array();
                }
                $settings[ $profile['id'] ]['enabled'] = 1;
            }

            // Save Settings
            $this->base->get_class( 'settings' )->update_settings( 'post', $settings );

            // Show success message
            $this->base->get_class( 'notices' )->add_success_notice( 
                sprintf( 
                    __( 'Thanks! You\'ve authorized our Plugin access to post updates to your %s account.<br />Please now configure the Post Type(s) you want to send to your %s account below.', $this->base->plugin->name ), 
                    $this->base->plugin->account, 
                    $this->base->plugin->account
                )
            );
        }

    }

    /**
     * Checks the transient to see if any admin notices need to be output now.
     *
     * @since   3.9.6
     */
    public function admin_notices() {

        // Output notices
        $this->base->get_class( 'notices' )->set_key_prefix( $this->base->plugin->filter_name . '_' . wp_get_current_user()->ID );
        $this->base->get_class( 'notices' )->output_notices();

    }

    /**
     * Checks that the oAuth authorization flow has been completed, and that
     * at least one Post Type with one Social Media account has been enabled.
     *
     * Displays a dismissible WordPress notification if this has not been done.
     *
     * @since   1.0.0
     */
    public function check_plugin_setup() {

        // Check the API is connected
        if ( ! $this->base->get_class( 'validation' )->api_connected() ) {
            $this->base->get_class( 'notices' )->add_error_notice(
                sprintf( 
                    __( '%s needs to be authorized with %s before you can start sending Posts to %s.  <a href="%s">Click here to Authorize.</a>', $this->base->plugin->name ),
                    $this->base->plugin->displayName,
                    $this->base->plugin->account,
                    $this->base->plugin->account, 
                    $this->base->get_class( 'api' )->get_oauth_url()
                )
            );
        }

    }

/**
     * Register and enqueue any JS and CSS for the WordPress Administration
     *
     * @since 1.0.0
     */
    public function admin_scripts_css() {

        global $id, $post;

        // Get current screen
        $screen = $this->base->get_class( 'screen' )->get_current_screen();

        // CSS - always load
        // Menu Icon is inline, because when Gravity Forms no conflict mode is ON, it kills all enqueued styles,
        // which results in a large menu SVG icon displaying.
        ?>
        <style type="text/css">
            li.toplevel_page_<?php echo $this->base->plugin->settingsName; ?>-settings a div.wp-menu-image, 
            li.toplevel_page_<?php echo $this->base->plugin->settingsName; ?> a div.wp-menu-image, 
            li.toplevel_page_<?php echo $this->base->plugin->name; ?>-settings a div.wp-menu-image,
            li.toplevel_page_<?php echo $this->base->plugin->name; ?> a div.wp-menu-image {
                background: url(<?php echo $this->base->plugin->url; ?>/vendor/assets/images/icons/<?php echo strtolower( $this->base->plugin->account ); ?>-light.svg) center no-repeat;
                background-size: 16px 16px;
            }
            li.toplevel_page_<?php echo $this->base->plugin->settingsName; ?>-settings a div.wp-menu-image img, 
            li.toplevel_page_<?php echo $this->base->plugin->settingsName; ?> a div.wp-menu-image img, 
            li.toplevel_page_<?php echo $this->base->plugin->name; ?>-settings a div.wp-menu-image img,
            li.toplevel_page_<?php echo $this->base->plugin->name; ?> a div.wp-menu-image img {
                display: none;
            }
        </style>
        <?php
        wp_enqueue_style( $this->base->plugin->name, $this->base->plugin->url . 'vendor/assets/css/admin.css', array(), $this->base->plugin->version );

        // Don't load anything else if we're not on a Plugin or Post screen
        if ( ! $screen['screen'] ) {
            return;
        }

        // If SCRIPT_DEBUG is enabled, load unminified versions
        if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
            $ext = '';
        } else {
            $ext = 'min';
        }

        // Define JS and localization
        wp_register_script( $this->base->plugin->name . '-post', $this->base->plugin->url . 'vendor/assets/js/' . ( $ext ? 'min/' : '' ) . 'post' . ( $ext ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );
        wp_register_script( $this->base->plugin->name . '-settings', $this->base->plugin->url . 'vendor/assets/js/' . ( $ext ? 'min/' : '' ) . 'settings' . ( $ext ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );
        wp_register_script( $this->base->plugin->name . '-statuses', $this->base->plugin->url . 'vendor/assets/js/' . ( $ext ? 'min/' : '' ) . 'statuses' . ( $ext ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );
             
        $localization = array(
            'ajax'                      => admin_url( 'admin-ajax.php' ),
            'clear_log_message'         => __( 'Are you sure you want to clear the log file associated with this Post?', $this->base->plugin->name ),
            'clear_log_nonce'           => wp_create_nonce( $this->base->plugin->name . '-clear-log' ),
            'clear_log_completed'       => sprintf( __( 'No status updates have been sent to %s.', $this->base->plugin->name ), $this->base->plugin->account ),
            'post_id'                   => ( isset( $post->ID ) ? $post->ID : (int) $id ),
        );

        // If here, we're on a Plugin or Post screen
        // Conditionally load scripts and styles depending on which section of the Plugin we're loading
        switch ( $screen['screen'] ) {
            /**
             * Post
             */
            case 'post':
                switch ( $screen['section'] ) {
                    /**
                     * WP_List_Table
                     */
                    case 'wp_list_table':
                        break;

                    /**
                     * Add/Edit
                     */
                    case 'edit':
                        // Plugin JS
                        wp_enqueue_script( $this->base->plugin->name . '-post' );
                        wp_localize_script( $this->base->plugin->name . '-post', 'wp_to_social_pro', $localization );
                        break;
                }
                break;

            /**
             * Settings
             */
            case 'settings':
                // JS
                wp_enqueue_script( 'wpzinc-admin-conditional' );
                wp_enqueue_script( 'wpzinc-admin-tabs' );
                wp_enqueue_script( 'wpzinc-admin' );

                switch ( $screen['section'] ) {
                    /**
                     * General
                     */
                    case 'auth':
                        break;

                    /**
                     * Post Type
                     */
                    default:
                        // JS
                        wp_enqueue_script( 'wpzinc-admin-autosize' );

                        // Plugin JS
                        wp_enqueue_script( $this->base->plugin->name . '-statuses' );
                        wp_localize_script( $this->base->plugin->name . '-statuses', 'wp_to_social_pro', $localization );
                        break;
                }
                break;

            /**
             * Log
             */
            case 'log':
                break;
        }

    }
    
    /**
     * Add the Plugin to the WordPress Administration Menu
     *
     * @since   1.0.0
     */
    public function admin_menu() {

        // Menus
        add_menu_page( $this->base->plugin->displayName, $this->base->plugin->displayName, 'manage_options', $this->base->plugin->name . '-settings', array( $this, 'settings_screen' ), $this->base->plugin->url . 'vendor/assets/images/icons/' . strtolower( $this->base->plugin->account ) . '-light.svg' );

        // Register Submenu Pages
        $settings_page  = add_submenu_page( $this->base->plugin->name . '-settings', __( 'Settings', $this->base->plugin->name ), __( 'Settings', $this->base->plugin->name ), 'manage_options', $this->base->plugin->name . '-settings', array( $this, 'settings_screen' ) );
        $log_page       = add_submenu_page( $this->base->plugin->name . '-settings', __( 'Logs', $this->base->plugin->name ), __( 'Logs', $this->base->plugin->name ), 'manage_options', $this->base->plugin->name . '-log', array( $this, 'log_screen' ) );
        $upgrade_page   = add_submenu_page( $this->base->plugin->name . '-settings', __( 'Upgrade', $this->base->plugin->name ), __( 'Upgrade', $this->base->plugin->name ), 'manage_options', $this->base->plugin->name . '-upgrade', array( $this, 'upgrade_screen' ) );
        
    }

    /**
     * Upgrade Screen
     *
     * @since 3.2.5
     */
    public function upgrade_screen() {   
        // We never reach here, as we redirect earlier in the process
    }


    /**
     * Outputs the Settings Screen
     *
     * @since   3.0.0
     */
    public function settings_screen() {

// Setup notices class
        $this->base->get_class( 'notices' )->set_key_prefix( $this->base->plugin->filter_name . '_' . wp_get_current_user()->ID );

        // Maybe disconnect
        if ( isset( $_GET[ $this->base->plugin->name . '-disconnect' ] ) ) {
            $this->disconnect();
            $this->base->get_class( 'notices' )->add_success_notice(
                sprintf( 
                    __( '%s account disconnected successfully.', $this->base->plugin->name ),
                    $this->base->plugin->account
                )
            ); 
        }

        // Maybe save settings
        $result = $this->save_settings();
        if ( is_wp_error( $result ) ) {
            // Error notice
            $this->base->get_class( 'notices' )->add_error_notice( $result->get_error_message() );
        } elseif ( $result === true ) {
            // Success notice
            $this->base->get_class( 'notices' )->add_success_notice( __( 'Settings saved successfully.', $this->base->plugin->name ) );
        }

        // Get URL parameters
        $tab            = $this->get_tab();
        $post_type      = $this->get_post_type_tab();
        
        // Authentication
        $access_token = $this->base->get_class( 'settings' )->get_access_token();
        $refresh_token = $this->base->get_class( 'settings' )->get_refresh_token();
        $expires = $this->base->get_class( 'settings' )->get_token_expires();
        if ( ! empty( $access_token ) ) {
            $this->base->get_class( 'api' )->set_tokens( $access_token, $refresh_token, $expires );
        } else {
            $oauth_url = $this->base->get_class( 'api' )->get_oauth_url();
        }

        // Profiles
        $profiles = $this->base->get_class( 'api' )->profiles( true, $this->base->get_class( 'common' )->get_transient_expiration_time() );
        if ( is_wp_error( $profiles ) ) {
            // If the error is a 401, the user revoked access to the plugin
            // Disconnect the Plugin, and explain why this happened
            if ( $profiles->get_error_code() == 401 ) {
                // Disconnect the Plugin
                $this->disconnect();

                // Fetch a new oAuth URL
                $oauth_url = $this->base->get_class( 'api' )->get_oauth_url();

                // Error notice
                $this->base->get_class( 'notices' )->add_error_notice(
                    sprintf( 
                        __( 'Hmm, it looks like you revoked access to %s through your %s account  
                            This means we can no longer post updates to your social networks.  
                            To re-authorize, click the Authorize Plugin button.', $this->base->plugin->name ), 
                        $this->base->plugin->displayName, 
                        $this->base->plugin->account
                    )
                );
            } else {
                // Some other error
                $this->base->get_class( 'notices' )->add_error_notice( $profiles->get_error_message() );
            }
        } elseif ( is_array( $profiles ) && count( $profiles ) == 0 ) {
            // No profiles were returned, meaning the user hasn't connected a social media profile to the API service
            $this->base->get_class( 'notices' )->add_error_notice(
                sprintf( 
                    __( 'You must connect at least one social media account to %s for %s to send status updates. 
                        <a href="%s" target="_blank">Click here</a> to do this on %s', $this->base->plugin->name ), 
                    $this->base->plugin->account, 
                    $this->base->plugin->displayName,
                    $this->base->get_class( 'api' )->get_connect_profiles_url(),
                    $this->base->plugin->account
                )
            );
        }

        // Post Types
        $post_types = $this->base->get_class( 'common' )->get_post_types();

        // Depending on the screen we're on, load specific options
        switch ( $tab ) {
            /**
             * Settings
             */
            case 'auth':
                // Documentation URL
                $documentation_url = $this->base->plugin->documentation_url . '/authentication-settings';
                break;

            /**
             * Post Type
             */
            default:
                // Run profiles through role restriction
                $tags               = $this->base->get_class( 'common' )->get_tags( $post_type );
                $post_type_object   = get_post_type_object( $post_type );
                $actions_plural     = $this->base->get_class( 'common' )->get_post_actions_past_tense();
                $schedule           = $this->base->get_class( 'common' )->get_schedule_options( $post_type );
                $post_actions       = $this->base->get_class( 'common' )->get_post_actions();

                // Check we're able to save settings based on the max_input_vars limit
                // If not, show a warning
                $max_query_vars_valid = $this->base->get_class( 'validation' )->max_query_vars_valid( $post_type, $profiles, $post_actions );
                if ( is_wp_error( $max_query_vars_valid ) ) {
                    $this->notices['error'][] = $max_query_vars_valid->get_error_message();
                }

                // Documentation URL
                $documentation_url = $this->base->plugin->documentation_url . '/status-settings';
                break;
        }

        // Load View
        include_once( $this->base->plugin->folder . 'vendor/views/settings.php' ); 
        
    }

    /**
     * Outputs the Log Screen
     *
     * @since   3.9.6
     */
    public function log_screen() {

        // Init table
        $table = new WP_To_Social_Pro_Log_Table( $this->base );
        $table->prepare_items();

        // Load View
        include_once( $this->base->plugin->folder . 'vendor/views/log.php' ); 

    }

    /**
     * Helper method to get the setting value from the plugin settings
     *
     * @since   3.0.0
     *
     * @param   string    $type         Setting Type
     * @param   string    $keys         Setting Key(s)
     * @param   mixed     $default      Default Value if Setting does not exist
     * @return  mixed                   Value
     */
    public function get_setting( $type = '', $key = '', $default = '' ) {

        // Post Type Setting or Bulk Setting
        if ( post_type_exists( $type ) ) {
            return $this->base->get_class( 'settings' )->get_setting( $type, $key, $default );
        }

        // Access token
        if ( $key == 'access_token' ) {
            return $this->base->get_class( 'settings' )->get_access_token();
        }

        // Refresh token
        if ( $key == 'refresh_token' ) {
            return $this->base->get_class( 'settings' )->get_refresh_token();
        }

        // Depending on the type, return settings / options
        switch ( $type ) {
            case 'log':
            case 'hide_meta_box_by_roles':
            case 'roles':
            case 'custom_tags':
            case 'repost':
                return $this->base->get_class( 'settings' )->get_setting( $type, $key, $default );
                break;

            default:
                return $this->base->get_class( 'settings' )->get_option( $key, $default );
                break;
        }

    }

    /**
     * Disconnect by removing the access token
     *
     * @since   3.0.0
     *
     * @return  string Result
     */
    public function disconnect() {

        return $this->base->get_class( 'settings' )->delete_tokens();

    }

    /**
     * Helper method to save settings
     *
     * @since   3.0.0
     *
     * @return  mixed Error String on error, true on success
     */
    public function save_settings() {

        // Bail if security checks fail
        $result = $this->validate_nonce();
        if ( $result != true ) {
            return $result;
        }

        // Get URL parameters
        $tab            = $this->get_tab();
        $post_type      = $this->get_post_type_tab();

        switch ( $tab ) {
            /**
             * Authentication
             */
            case 'auth':
                // oAuth settings are now handled by this class' oauth() function
                // Save other Settings
                $this->base->get_class( 'settings' )->update_option( 'log', ( isset( $_POST['log'] ) ? $_POST['log'] : array() ) );
                
                // Done
                return true;

                break;

            /**
             * Post Type
             */
            default:
                // Save Settings for this Post Type
                return $this->base->get_class( 'settings' )->update_settings( $post_type, $_POST[ $this->base->plugin->name ] );

                break;
        }

    }

    /**
     * Returns the settings tab that the user has selected.
     *
     * @since   3.7.2
     *
     * @return  string  Tab
     */
    private function get_tab() {

        return ( isset( $_GET['tab'] ) ? sanitize_text_field(  $_GET['tab'] ) : 'auth' );

    }

    /**
     * Returns the Post Type tab that the user has selected.
     *
     * @since   3.7.2
     *
     * @return  string  Tab
     */
    private function get_post_type_tab() {

        return ( isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '' );

    }

    /**
     * Validates the nonce field on submitted POST data
     *
     * @since   3.4.7
     *
     * @return  mixed   WP_Error | boolean
     */
    private function validate_nonce() {

        // Check if a POST request was made
        if ( ! isset( $_POST['submit'] ) ) {
            return false;
        }

        // Missing nonce 
        if ( ! isset( $_POST[ $this->base->plugin->name . '_nonce' ] ) ) { 
            return __( 'Nonce field is missing. Settings NOT saved.', $this->base->plugin->name );
        }

        // Invalid nonce
        if ( ! wp_verify_nonce( $_POST[ $this->base->plugin->name . '_nonce' ], $this->base->plugin->name ) ) {
            return __('Invalid nonce specified. Settings NOT saved.', $this->base->plugin->name );
        }

        return true;

    }

    /**
     * Loads plugin textdomain
     *
     * @since   3.0.0
     */
    public function load_language_files() {

        load_plugin_textdomain( $this->base->plugin->name, false, $this->base->plugin->name . '/languages/' );

    }

}