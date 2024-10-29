<?php
/**
 * Settings class
 * 
 * @package WP_To_Social_Pro
 * @author  Tim Carr
 * @version 3.0.0
 */
class WP_To_Social_Pro_Settings {

    /**
     * Holds the class object.
     *
     * @since   3.1.4
     *
     * @var     object
     */
    public static $instance;

    /**
     * Constructor
     *
     * @since   3.4.7
     *
     * @param   object $base    Base Plugin Class
     */
    public function __construct( $base ) {

        // Store base class
        $this->base = $base;

    }

    /**
     * Migrates settings from the free version or Pro version 2.x
     *
     * @since   3.0.0
     */
    public function migrate_settings() {

        // Define old settings key
        $old_settings_key = str_replace( '-pro', '', $this->base->plugin->settingsName );

        // Check if we have any old settings
        $old_settings = get_option( $old_settings_key );

        // If old settings are empty, bail
        if ( ! $old_settings ) {
            return;
        }

        // Store the old settings in a backup option key, just in case
        update_option( $old_settings_key . '-v2', $old_settings );

        // Migrate into new settings
        // Access token
        if ( ! empty( $old_settings['accessToken'] ) ) {
            $this->update_access_token( $old_settings['accessToken'] );
        }
        if ( ! empty( $old_settings['refreshToken'] ) ) {
            $this->update_refresh_token( $old_settings['refreshToken'] );
        }

        // Get Profiles
        $this->base->get_class( 'api' )->set_tokens( 
            $this->get_access_token(),
            $this->get_refresh_token(),
            $this->get_token_expires()
        );
        $profiles = $this->base->get_class( 'api' )->profiles( true, $this->base->get_class( 'common' )->get_transient_expiration_time() );

        // Get Actions
        $actions = $this->base->get_class( 'common' )->get_post_actions();

        // Iterate through each Post Type
        foreach ( $old_settings['enabled'] as $post_type => $old_actions ) {
            $new_settings = array();

            // Default profile
            $new_settings['default'] = array();
            
            // Default profile: actions
            foreach ( $actions as $action => $action_label ) {
                if ( $action == 'conditions' ) {
                    /**
                    * Conditions (was Filters)
                    */
                    $new_settings['default']['conditions'] = array();
                    $new_settings['default']['conditions']['enabled'] = ( isset( $old_settings['filter'][ $post_type ] ) ? $old_settings['filter'][ $post_type ] : 0 );
                    if ( $new_settings['default']['conditions']['enabled'] ) {
                        foreach ( $old_settings['tax'][ $post_type ] as $taxonomy => $items ) {
                            $new_settings['default']['conditions'][ $taxonomy ] = $items;
                        }
                    }
                } else {
                    /**
                    * Publish/Update
                    */
                    $new_settings['default'][ $action ] = array();
                    $new_settings['default'][ $action ]['enabled'] = ( isset( $old_settings['enabled'][ $post_type ][ $action ] ) ? $old_settings['enabled'][ $post_type ][ $action ] : 0 );
                    $new_settings['default'][ $action ]['status'] = array();
                    $new_settings['default'][ $action ]['status'][] = array(
                        'image'         => ( isset( $old_settings['image'][ $post_type ][ $action ] ) ? $old_settings['image'][ $post_type ][ $action ]: 0 ),
                        'sub_profile'   => 0, // Pinterest not supported in free or v2.x
                        'message'       => ( isset( $old_settings['message'][ $post_type ][ $action ] ) ? $old_settings['message'][ $post_type ][ $action ] : '' ),
                        'schedule'      => ( ( isset( $old_settings['enabled'][ $post_type ]['instant'] ) && $old_settings['enabled'][ $post_type ]['instant'] == 1 ) ? 'now' : 'queue_bottom' ),
                        'days'          => 0,
                        'hours'         => 0,
                        'minutes'       => 0,
                    );
                    if ( $old_settings['number'][ $post_type ][ $action ] == 2 ) {
                        // Alternate status
                        $new_settings['default'][ $action ]['status'][] = array(
                            'image'         => ( isset( $old_settings['image'][ $post_type ][ $action ] ) ? $old_settings['image'][ $post_type ][ $action ]: 0 ),
                            'sub_profile'   => 0, // Pinterest not supported in free or v2.x
                            'message'       => ( isset( $old_settings['alternateMessage'][ $post_type ][ $action ] ) ? $old_settings['alternateMessage'][ $post_type ][ $action ] : '' ),
                            'schedule'      => ( ( isset( $old_settings['enabled'][ $post_type ]['instant'] ) && $old_settings['enabled'][ $post_type ]['instant'] == 1 ) ? 'now' : 'queue_bottom' ),
                            'days'          => 0,
                            'hours'         => 0,
                            'minutes'       => 0,
                        );
                    }
                    if ( $old_settings['number'][ $post_type ][ $action ] == 3 ) {
                        // Original status, again
                        $new_settings['default'][ $action ]['status'][] = array(
                            'image'         => ( isset( $old_settings['image'][ $post_type ][ $action ] ) ? $old_settings['image'][ $post_type ][ $action ]: 0 ),
                            'sub_profile'   => 0, // Pinterest not supported in free or v2.x
                            'message'       => ( isset( $old_settings['message'][ $post_type ][ $action ] ) ? $old_settings['message'][ $post_type ][ $action ] : '' ),
                            'schedule'      => ( ( isset( $old_settings['enabled'][ $post_type ]['instant'] ) && $old_settings['enabled'][ $post_type ]['instant'] == 1 ) ? 'now' : 'queue_bottom' ),
                            'days'          => 0,
                            'hours'         => 0,
                            'minutes'       => 0,
                        );
                    }
                }
            }

            // Iterate through Profiles
            foreach ( $profiles as $profile_id => $profile ) {        
                // Default profile
                $new_settings[ $profile_id ] = array();
                
                // Default profile: actions
                foreach ( $actions as $action => $action_label ) {
                    if ( $action == 'conditions' ) {
                        /**
                        * Conditions (was Filters)
                        */
                        $new_settings[ $profile_id ]['conditions'] = array();
                        $new_settings[ $profile_id ]['conditions']['enabled'] = ( isset( $old_settings[ $profile_id ]['filter'][ $post_type ] ) ? $old_settings[ $profile_id ]['filter'][ $post_type ] : 0 );
                        if ( $new_settings[ $profile_id ]['conditions']['enabled'] ) {
                            foreach ( $old_settings[ $profile_id ]['tax'][ $post_type ] as $taxonomy => $items ) {
                                $new_settings[ $profile_id ]['conditions'][ $taxonomy ] = $items;
                            }
                        }
                    } else {
                        /**
                        * Publish/Update
                        */

                        // Profile enabled + overriding?
                        $new_settings[ $profile_id ]['enabled'] = ( isset( $old_settings['ids'][ $post_type ][ $profile_id ] ) ? 1 : 0 );
                        $new_settings[ $profile_id ]['override'] = ( isset( $old_settings['override'][ $post_type ][ $profile_id ] ) ? 1 : 0 );
                        
                        // Profile action
                        $new_settings[ $profile_id ][ $action ] = array();
                        $new_settings[ $profile_id ][ $action ]['enabled'] = ( isset( $old_settings[ $profile_id ]['enabled'][ $post_type ][ $action ] ) ? 1 : 0 );
                        $new_settings[ $profile_id ][ $action ]['status'] = array();
                        $new_settings[ $profile_id ][ $action ]['status'][] = array(
                            'image'         => ( isset( $old_settings[ $profile_id ]['image'][ $post_type ][ $action ] ) ? $old_settings[ $profile_id ]['image'][ $post_type ][ $action ]: 0 ),
                            'sub_profile'   => 0, // Pinterest not supported in free or v2.x
                            'message'       => ( isset( $old_settings[ $profile_id ]['message'][ $post_type ][ $action ] ) ? $old_settings[ $profile_id ]['message'][ $post_type ][ $action ] : '' ),
                            'schedule'      => ( ( isset( $old_settings[ $profile_id ]['enabled'][ $post_type ]['instant'] ) && $old_settings[ $profile_id ]['enabled'][ $post_type ]['instant'] == 1 ) ? 'now' : 'queue_bottom' ),
                            'days'          => 0,
                            'hours'         => 0,
                            'minutes'       => 0,
                        );
                        if ( $old_settings['number'][ $post_type ][ $action ] == 2 ) {
                            // Alternate status
                            $new_settings[ $profile_id ][ $action ]['status'][] = array(
                                'image'         => ( isset( $old_settings[ $profile_id ]['image'][ $post_type ][ $action ] ) ? $old_settings[ $profile_id ]['image'][ $post_type ][ $action ]: 0 ),
                                'sub_profile'   => 0, // Pinterest not supported in free or v2.x
                                'message'       => ( isset( $old_settings[ $profile_id ]['alternateMessage'][ $post_type ][ $action ] ) ? $old_settings[ $profile_id ]['alternateMessage'][ $post_type ][ $action ] : '' ),
                                'schedule'      => ( ( isset( $old_settings[ $profile_id ]['enabled'][ $post_type ]['instant'] ) && $old_settings[ $profile_id ]['enabled'][ $post_type ]['instant'] == 1 ) ? 'now' : 'queue_bottom' ),
                                'days'          => 0,
                                'hours'         => 0,
                                'minutes'       => 0,
                            );
                        }
                        if ( $old_settings['number'][ $post_type ][ $action ] == 3 ) {
                            // Original status, again
                            $new_settings[ $profile_id ][ $action ]['status'][] = array(
                                'image'         => ( isset( $old_settings[ $profile_id ]['image'][ $post_type ][ $action ] ) ? $old_settings[ $profile_id ]['image'][ $post_type ][ $action ]: 0 ),
                                'sub_profile'   => 0, // Pinterest not supported in free or v2.x
                                'message'       => ( isset( $old_settings[ $profile_id ]['message'][ $post_type ][ $action ] ) ? $old_settings[ $profile_id ]['message'][ $post_type ][ $action ] : '' ),
                                'schedule'      => ( ( isset( $old_settings[ $profile_id ]['enabled'][ $post_type ]['instant'] ) && $old_settings[ $profile_id ]['enabled'][ $post_type ]['instant'] == 1 ) ? 'now' : 'queue_bottom' ),
                                'days'          => 0,
                                'hours'         => 0,
                                'minutes'       => 0,
                            );
                        }
                    }
                }
            }

            // We now have a new settings array that's v3 compatible
            update_option( $this->base->plugin->name . '-' . $post_type, $new_settings );
        } // Close post type

        // Clear old settings
        delete_option( $old_settings_key );

    }

    /**
     * Retrieves a setting from the options table.
     *
     * Safely checks if the key(s) exist before returning the default
     * or the value.
     *
     * @since   3.0.0
     *
     * @param   string  $type       Setting Type
     * @param   string  $key        Setting key value to retrieve
     * @param   string  $default    Default Value
     * @return  string              Value/Default Value
     */
    public function get_setting( $type, $key, $default = '' ) {

        // Get settings
        $settings = $this->get_settings( $type );

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
     * Returns the settings for the given Post Type
     *
     * @since   3.0.0
     *
     * @param   string  $type   Type
     * @return  array           Settings
     */
    public function get_settings( $type ) {

        // Get current settings
        $settings = get_option( $this->base->plugin->settingsName . '-' . $type );

        // Allow devs to filter before returning
        $settings = apply_filters( $this->base->plugin->filter_name . '_get_settings', $settings, $type );

        // Return result
        return $settings;

    }

    /**
     * Stores the given settings for the given Post Type into the options table
     *
     * @since   3.0.0
     *
     * @param   string  $type       Type
     * @param   array   $settings   Settings
     * @return  bool                Success
     */
    public function update_settings( $type, $settings ) {

        // Makes the given $settings statuses associative
        $settings = $this->make_statuses_associative( $settings );

        /**
         * Filters Post Type Settings before they are saved.
         *
         * @since   3.0.0
         *
         * @param   array   $settings   Settings
         * @param   string  $type       Post Type
         */
        $settings = apply_filters( $this->base->plugin->filter_name . '_update_settings', $settings, $type );

        // Save
        $this->update_option( $type, $settings );

        // Check for duplicate statuses
        $duplicates = $this->base->get_class( 'validation' )->check_for_duplicates( $settings );
        if ( is_array( $duplicates ) ) {
            // Fetch Post Type Name, Profile Name and Action Name
            $post_type_object = get_post_type_object( $type );
            if ( $duplicates['profile_id'] == 'default' ) {
                $profile = __( 'Defaults', $this->base->plugin->name );
            } elseif ( isset( $profiles[ $profile_id ] ) ) {
                $profile = $profiles[ $profile_id ]['formatted_service'] . ': ' . $profiles[ $profile_id ]['formatted_username'];
            }
            $post_actions = $this->base->get_class( 'common' )->get_post_actions();
            $action = $post_actions[ $duplicates['action'] ];

            // Return error object
            return new WP_Error( $this->base->plugin->filter_name . '_settings_update_settings_duplicates', sprintf( 
                __( 'Two or more statuses defined in %s > %s > %s are the same. 
                    Please correct this to ensure each status update is unique, otherwise your status updates will NOT publish to %s 
                    as they will be seen as duplicates, which violate Facebook and Twitter\'s Terms of Service.', $this->base->plugin->name ),
                $post_type_object->label,
                $profile,
                $action,
                $this->base->plugin->account
            ) );
        }

        // No duplicate statuses found
        return true;

    }

    /**
     * Returns an array of default settings for a new installation.
     *
     * @since   3.4.0
     *
     * @return  array   Settings
     */
    public function default_installation_settings() {

        // Load function if required
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        // Fetch OpenGraph supported SEO Plugins and Fetured Image Options
        $featured_image_options = array_keys( $this->base->get_class( 'common' )->get_featured_image_options() );
        $seo_plugins = $this->base->get_class( 'common' )->get_opengraph_seo_plugins();

        // If the Plugin only offers "Use OpenGraph Settings", no need to check for SEO Plugin availability
        if ( count( $featured_image_options ) == 1 && ! $featured_image_options[0] ) {
            $image = 0;
        } else {
            // Detect if an SEO Plugin that outputs OpenGraph data exists
            $image = 2;
            
            foreach ( $seo_plugins as $seo_plugin ) {
                // If plugin active, use OpenGraph for images
                if ( is_plugin_active( $seo_plugin ) ) {
                    $image = 0;
                    break;
                }
            }
        }

        // Define default settings
        $settings = array(
            'default' => array(
                'publish' => array(
                    'enabled'       => 1,
                    'status' => array(
                        'image'     => $image,
                        'message'   => array( '{title} {url}' ),
                        'schedule'  => array( 'queue_bottom' ),
                    ),
                ),
                'update' => array(
                    'enabled'       => 1,
                    'status' => array(
                        'image'     => $image,
                        'message'   => array( '{title} {url}' ),
                        'schedule'  => array( 'queue_bottom' ),
                    ),
                ),
            ),
        );

        // Allow devs to filter
        $settings = apply_filters( $this->base->plugin->filter_name . '_default_installation_settings', $settings );

        // Return
        return $settings;

    }

    /**
     * Helper method to determine whether the given Post Type has at least
     * one social media account enabled, and there is a publish or update
     * action enabled in the Defaults for the Post Type or the Social Media account.
     *
     * @since   3.4.0
     *
     * @param   string  $post_type  Post Type
     * @return  bool                Enabled
     */
    public function is_post_type_enabled( $post_type ) {

        // Get Settings for Post Type
        $settings = $this->get_settings( $post_type );

        // If no settings, bail
        if ( ! $settings ) {
            return false;
        }

        /**
         * Default Publish or Update enabled
         * 1+ Profiles enabled without override
         */
        $default_publish_action_enabled = $this->get_setting( $post_type, '[default][publish][enabled]', 0 );
        $default_update_action_enabled  = $this->get_setting( $post_type, '[default][update][enabled]', 0 );
        if ( $default_publish_action_enabled || $default_update_action_enabled ) {
            foreach ( $settings as $profile_id => $profile_settings ) {
                // Skip defaults
                if ( $profile_id == 'default' ) {
                    continue;
                }

                // Profile enabled, no override
                if ( isset( $profile_settings['enabled'] ) && $profile_settings['enabled'] ) {
                    if ( ! isset( $profile_settings['override'] ) || ! $profile_settings['override'] ) {
                        // Post Type is enabled with Defaults + 1+ Profile not using override settings
                        return true;
                    }     
                } 
            }
        }

        /**
         * 1+ Profiles enabled with override and publish / update enabled
         */
        foreach ( $settings as $profile_id => $profile_settings ) {
            // Skip defaults
            if ( $profile_id == 'default' ) {
                continue;
            }

            // Skip if profile not enabled
            if ( ! isset( $profile_settings['enabled'] ) || ! $profile_settings['enabled'] ) {
                continue;
            }

            // Skip if override not enabled
            if ( ! isset( $profile_settings['override'] ) || ! $profile_settings['override'] ) {
                continue;
            }

            // Profile action enabled
            if ( isset( $profile_settings['publish']['enabled'] ) && $profile_settings['publish']['enabled'] == '1' ) {
                // Post Type is enabled with 1+ Profile with override and publish enabled
                return true;
            }
            if ( isset( $profile_settings['update']['enabled'] ) && $profile_settings['update']['enabled'] == '1' ) {
                // Post Type is enabled with 1+ Profile with override and update enabled
                return true;
            }
        }

        // If here, Post Type can't be sent to the API
        return false;
       
    }

/**
     * Makes the given $settings statuses associative e.g.
     * $settings[profile_id][publish][status][message][0] --> $settings[profile_id][publish][status][0][message]
     *
     * @since   3.0.0
     *
     * @param   array   $settings   Settings
     * @return  array               Associative Settings
     */
    public function make_statuses_associative( $settings ) {

        // Get available actions
        $actions = $this->base->get_class( 'common' )->get_post_actions();

        // Iterate through settings, updatning statuses so they are are associative
        foreach ( $settings as $profile_id => $profile_settings ) {
            // Iterate through actions for each profile
            foreach ( $actions as $action => $action_label ) {
                // Check some statuses are specified for this action
                if ( ! isset( $profile_settings[ $action ] ) ) {
                    continue;
                }
                if ( ! isset( $profile_settings[ $action ]['status'] ) ) {
                    continue;
                }
                if ( ! isset( $profile_settings[ $action ]['status']['message'] ) ) {
                    continue;
                }

                // Iterate through each status, to build the associative array
                $statuses = array();
                $status_count = 0;
                foreach ( $profile_settings[ $action ]['status']['message'] as $index => $message ) {
                    $statuses[ $status_count ] = array(
                        'image'                         => ( isset( $profile_settings[ $action ]['status']['image'][ $index ] ) ? $profile_settings[ $action ]['status']['image'][ $index ] : 0 ),
                        'sub_profile'                   => ( isset( $profile_settings[ $action ]['status']['sub_profile'][ $index ] ) ? $profile_settings[ $action ]['status']['sub_profile'][ $index ] : 0 ),
                        'message'                       => stripslashes( ( isset( $profile_settings[ $action ]['status']['message'][ $index ] ) ? $profile_settings[ $action ]['status']['message'][ $index ] : '' ) ),
                        'schedule'                      => ( isset( $profile_settings[ $action ]['status']['schedule'][ $index ] ) ? $profile_settings[ $action ]['status']['schedule'][ $index ] : '' ),
                        'days'                          => ( isset( $profile_settings[ $action ]['status']['days'][ $index ] ) ? $profile_settings[ $action ]['status']['days'][ $index ] : 0 ),
                        'hours'                         => ( isset( $profile_settings[ $action ]['status']['hours'][ $index ] ) ? $profile_settings[ $action ]['status']['hours'][ $index ] : 0 ),
                        'minutes'                       => ( isset( $profile_settings[ $action ]['status']['minutes'][ $index ] ) ? $profile_settings[ $action ]['status']['minutes'][ $index ] : 0 ), 
                        'schedule_relative_day'         => ( isset( $profile_settings[ $action ]['status']['schedule_relative_day'][ $index ] ) ? $profile_settings[ $action ]['status']['schedule_relative_day'][ $index ] : '' ), 
                        'schedule_relative_time'        => ( isset( $profile_settings[ $action ]['status']['schedule_relative_time'][ $index ] ) ? $profile_settings[ $action ]['status']['schedule_relative_time'][ $index ] : '00:00:00' ), 
                        'schedule_custom_field_name'    => ( isset( $profile_settings[ $action ]['status']['schedule_custom_field_name'][ $index ] ) ? $profile_settings[ $action ]['status']['schedule_custom_field_name'][ $index ] : 0 ), 
                        'schedule_custom_field_relation'=> ( isset( $profile_settings[ $action ]['status']['schedule_custom_field_relation'][ $index ] ) ? $profile_settings[ $action ]['status']['schedule_custom_field_relation'][ $index ] : 'after' ),
                        'schedule_tec_relation'         => ( isset( $profile_settings[ $action ]['status']['schedule_tec_relation'][ $index ] ) ? $profile_settings[ $action ]['status']['schedule_tec_relation'][ $index ] : 'after' ),
                        'schedule_specific'             => ( isset( $profile_settings[ $action ]['status']['schedule_specific'][ $index ] ) ? $profile_settings[ $action ]['status']['schedule_specific'][ $index ] : '' ),
                        'start_date'                    => array(
                            'day'   => ( isset( $profile_settings[ $action ]['status']['start_date']['day'][ $index ] ) ? $profile_settings[ $action ]['status']['start_date']['day'][ $index ] : '' ),
                            'month' => ( isset( $profile_settings[ $action ]['status']['start_date']['month'][ $index ] ) ? $profile_settings[ $action ]['status']['start_date']['month'][ $index ] : '' ), 
                        ),
                        'end_date'                      => array(
                            'day'   => ( isset( $profile_settings[ $action ]['status']['end_date']['day'][ $index ] ) ? $profile_settings[ $action ]['status']['end_date']['day'][ $index ] : '' ),
                            'month' => ( isset( $profile_settings[ $action ]['status']['end_date']['month'][ $index ] ) ? $profile_settings[ $action ]['status']['end_date']['month'][ $index ] : '' ), 
                        ),
                        'conditions'                    => array(),
                        'terms'                         => array(),
                        'custom_fields'                 => array(),
                    );

                    // Authors
                    $statuses[ $status_count ]['authors'] = false;
                    if ( isset( $profile_settings[ $action ]['status']['authors'][ $index ] ) && ! empty( $profile_settings[ $action ]['status']['authors'][ $index ] ) ) {
                        $statuses[ $status_count ]['authors'] = explode( ',', $profile_settings[ $action ]['status']['authors'][ $index ] );
                    }

                    // Iterate through conditions to get taxonomies
                    if ( isset( $profile_settings[ $action ]['status']['conditions'] ) && count( $profile_settings[ $action ]['status']['conditions'] ) > 0 ) {
                        foreach ( $profile_settings[ $action ]['status']['conditions'] as $taxonomy => $taxonomy_conditions ) {
                            $statuses[ $status_count ]['conditions'][ $taxonomy ] = $taxonomy_conditions[ $index ];
                        }
                    }

                    // Iterate through terms to get taxonomies
                    if ( isset( $profile_settings[ $action ]['status']['terms'] ) && count( $profile_settings[ $action ]['status']['terms'] ) > 0 ) {
                        foreach ( $profile_settings[ $action ]['status']['terms'] as $taxonomy => $term_ids ) {
                            $statuses[ $status_count ]['terms'][ $taxonomy ] = explode( ',', $term_ids[ $index ] );
                        }
                    }

                    // Iterate through custom fields
                    if ( isset( $profile_settings[ $action ]['status']['custom_fields'][ $index ]['key'] ) && count( $profile_settings[ $action ]['status']['custom_fields'][ $index ]['key'] ) > 0 ) {
                        foreach ( $profile_settings[ $action ]['status']['custom_fields'][ $index ]['key'] as $custom_field_index => $key ) {
                            // Skip if the key or compare is blank
                            if ( empty( $profile_settings[ $action ]['status']['custom_fields'][ $index ]['key'][ $custom_field_index ] ) ) {
                                continue;
                            }

                            $statuses[ $status_count ]['custom_fields'][] = array(
                                'key'       => $profile_settings[ $action ]['status']['custom_fields'][ $index ]['key'][ $custom_field_index ],
                                'compare'   => $profile_settings[ $action ]['status']['custom_fields'][ $index ]['compare'][ $custom_field_index ],
                                'value'     => $profile_settings[ $action ]['status']['custom_fields'][ $index ]['value'][ $custom_field_index ],
                            );
                        }
                    }

                    // Run status through validation
                    $statuses[ $status_count ] = $this->validate_status( $statuses[ $status_count ] );

                    // Increment array index
                    $status_count++;
                }

                // Assign statuses back to status key
                $settings[ $profile_id ][ $action ]['status'] = $statuses;
                
            }
        }

        return $settings;

    }

    /**
     * Runs the given individual status settings through validation - for example,
     * ensuring that a custom time is at least 5 minutes when using Hootsuite,
     * to ensure compatibility with the API.
     *
     * @since   3.7.3
     *
     * @param   array   $status     Status Message Settings
     * @return  array               Status Message Settings
     */
    private function validate_status( $status ) {

        // If we're using Hootsuite, with a custom time, it must be set to at least 5 minutes.
        if ( class_exists( 'WP_To_Hootsuite' ) || class_exists( 'WP_To_Hootsuite_Pro' ) ) {
            if ( $status['schedule'] == 'custom' && ! $status['days'] && ! $status['hours'] ) {
                if ( $status['minutes'] < 5 ) {
                    $status['minutes'] = 5;
                }
            }
        }

        /**
         * Filters status settings during validation, allowing them to be changed.
         *
         * @since   3.7.3
         *
         * @param   array   $status     Status
         */
        $status = apply_filters( $this->base->plugin->filter_name . '_settings_validate_status', $status );

        // Return
        return $status;
        
    }

    /**
     * Stores the given access token and refresh token into the options table.
     *
     * @since   3.5.0
     *
     * @param   string  $access_token    Access Token
     * @param   string  $refresh_token   Refresh Token
     * @param   mixed   $token_expires   Token Expires (false | timestamp)
     */
    public function update_tokens( $access_token = '', $refresh_token = '', $token_expires = false ) {

        $this->update_access_token( $access_token );
        $this->update_refresh_token( $refresh_token );
        $this->update_token_expires( $token_expires );

    }

    /**
     * Deletes the access, refresh and toke expiry values from the options table.
     *
     * @since   3.5.0
     */
    public function delete_tokens() {

        $this->delete_access_token();
        $this->delete_refresh_token();
        $this->delete_token_expires();

    }

    /**
     * Retrieves the access token from the options table
     *
     * @since   3.0.0
     *
     * @return  string  Access Token
     */
    public function get_access_token() {

        return get_option( $this->base->plugin->settingsName . '-access-token' );

    }

    /**
     * Stores the given access token into the options table
     *
     * @since   3.0.0
     *
     * @param   string  $access_token   Access Token
     * @return  bool                    Success
     */
    public function update_access_token( $access_token ) {

        // Allow devs to filter before saving
        $access_token = apply_filters( $this->base->plugin->filter_name . '_update_access_token', $access_token );

        // Return result
        return update_option( $this->base->plugin->settingsName . '-access-token', $access_token );

    }

    /**
     * Deletes the access token from the options table
     *
     * @since   3.4.7
     *
     * @return  bool    Success
     */
    public function delete_access_token() {

        // Return result
        return delete_option( $this->base->plugin->settingsName . '-access-token' );

    }

    /**
     * Retrieves the refresh token from the options table
     *
     * @since   3.0.0
     *
     * @return  string  Access Token
     */
    public function get_refresh_token() {

        return get_option( $this->base->plugin->settingsName . '-refresh-token' );

    }

    /**
     * Stores the given refresh token into the options table
     *
     * @since   3.0.0
     *
     * @param   string  $refresh_token  Refresh Token
     * @return  bool                    Success
     */
    public function update_refresh_token( $refresh_token ) {

        // Allow devs to filter before saving
        $refresh_token = apply_filters( $this->base->plugin->filter_name . '_update_refresh_token', $refresh_token );

        // Return result
        return update_option( $this->base->plugin->settingsName . '-refresh-token', $refresh_token );

    }

    /**
     * Deletes the access token from the options table
     *
     * @since   3.4.7
     *
     * @return  bool    Success
     */
    public function delete_refresh_token() {

        // Return result
        return delete_option( $this->base->plugin->settingsName . '-refresh-token' );

    }

    /**
     * Retrieves the token expiry timestamp from the options table
     *
     * @since   3.5.0
     *
     * @return  mixed   false | Token Expiry Timestamp
     */
    public function get_token_expires() {

        return get_option( $this->base->plugin->settingsName . '-token-expires' );

    }

    /**
     * Stores the given token expiry timestamp into the options table
     *
     * @since   3.5.0
     *
     * @param   mixed   $token_expires      Token Expires (false | timestamp)
     * @return  bool                        Success
     */
    public function update_token_expires( $token_expires ) {

        // Allow devs to filter before saving
        $token_expires = apply_filters( $this->base->plugin->filter_name . '_update_token_expires', $token_expires );

        // Return result
        return update_option( $this->base->plugin->settingsName . '-token-expires', $token_expires );

    }

    /**
     * Deletes the token expiry timestamp from the options table
     *
     * @since   3.5.0
     *
     * @return  bool    Success
     */
    public function delete_token_expires() {

        // Return result
        return delete_option( $this->base->plugin->settingsName . '-token-expires' );

    }

    /**
     * Helper method to get a value from the options table
     *
     * @since   3.0.0
     *
     * @return  string  Access Token
     */
    public function get_option( $key, $default = '' ) {

        $result = get_option( $this->base->plugin->settingsName . '-' . $key );
        if ( ! $result ) {
            return $default;
        }

        return $result;

    }

    /**
     * Helper method to store a value to the options table
     *
     * @since   3.0.0
     *
     * @param   string  $key    Key
     * @param   string  $value  Value
     * @return  bool            Success
     */
    public function update_option( $key, $value ) {

        // Allow devs to filter before saving
        $value = apply_filters( $this->base->plugin->filter_name . '_update_option', $value, $key );

        // Update
        update_option( $this->base->plugin->settingsName . '-' . $key, $value );

        return true;

    }

}