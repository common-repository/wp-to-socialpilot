<?php
/**
 * Common class
 * 
 * @package WP_To_Social_Pro
 * @author  Tim Carr
 * @version 3.0.0
 */
class WP_To_Social_Pro_Common {

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
     * @since   3.4.7
     *
     * @param   object $base    Base Plugin Class
     */
    public function __construct( $base ) {

        // Store base class
        $this->base = $base;
        
    }

    /**
     * Helper method to retrieve schedule options
     *
     * @since   3.0.0
     *
     * @param   mixed   $post_type          Post Type (false | string)
     * @param   bool    $is_post_screen     Displaying the Post Screen
     * @return  array                       Schedule Options
     */
    public function get_schedule_options( $post_type = false, $is_post_screen = false ) {

        // Build schedule options, depending on the Plugin
        switch ( $this->base->plugin->name ) {

            case 'wp-to-buffer':
                $schedule = array(
                    'queue_bottom'  => sprintf( __( 'Add to End of %s Queue', $this->base->plugin->name ), $this->base->plugin->account ),
                );
                break;

            case 'wp-to-buffer-pro':
                $schedule = array(
                    'queue_bottom'  => sprintf( __( 'Add to End of %s Queue', $this->base->plugin->name ), $this->base->plugin->account ),
                    'queue_top'     => sprintf( __( 'Add to Start of %s Queue', $this->base->plugin->name ), $this->base->plugin->account ),
                    'now'           => __( 'Post Immediately', $this->base->plugin->name ),
                    'custom'        => __( 'Custom Time', $this->base->plugin->name ),
                    'custom_field'  => __( 'Custom Time (based on Custom Field / Post Meta Value)', $this->base->plugin->name ),
                );

                // If The Events Calendar is installed and we're returning Schedule Options for Events, add an option
                if ( class_exists( 'Tribe__Events__Main' ) && $post_type == 'tribe_events' ) {
                    $schedule['_EventStartDate'] = __( 'The Events Calendar: Relative to Event Start Date', $this->base->plugin->name );
                    $schedule['_EventEndDate'] = __( 'The Events Calendar: Relative to Event End Date', $this->base->plugin->name );
                }

                // If we're on the Post Screen, add a specific option now
                if ( $is_post_screen ) {
                    $schedule['specific'] = __( 'Specific Date and Time', $this->base->plugin->name );
                }
                break;

            case 'wp-to-hootsuite':
                $schedule = array(
                    'now'           => __( 'Post Immediately', $this->base->plugin->name ),
                );
                break;

            case 'wp-to-hootsuite-pro':
                $schedule = array(
                    'now'           => __( 'Post Immediately', $this->base->plugin->name ),
                    'custom'        => __( 'Custom Time', $this->base->plugin->name ),
                    'custom_field'  => __( 'Custom Time (based on Custom Field / Post Meta Value)', $this->base->plugin->name ),
                );

                // If The Events Calendar is installed and we're returning Schedule Options for Events, add an option
                if ( class_exists( 'Tribe__Events__Main' ) && $post_type == 'tribe_events' ) {
                    $schedule['_EventStartDate'] = __( 'The Events Calendar: Relative to Event Start Date', $this->base->plugin->name );
                    $schedule['_EventEndDate'] = __( 'The Events Calendar: Relative to Event End Date', $this->base->plugin->name );
                }

                // If we're on the Post Screen, add a specific option now
                if ( $is_post_screen ) {
                    $schedule['specific'] = __( 'Specific Date and Time', $this->base->plugin->name );
                }
                break;

            case 'wp-to-socialpilot':
                $schedule = array(
                    'queue_bottom'  => sprintf( __( 'Add to End of %s Queue', $this->base->plugin->name ), $this->base->plugin->account ),
                );
                break;

            case 'wp-to-socialpilot-pro':
                $schedule = array(
                    'queue_bottom'  => sprintf( __( 'Add to End of %s Queue', $this->base->plugin->name ), $this->base->plugin->account ),
                    'now'           => __( 'Post Immediately', $this->base->plugin->name ),
                    'custom'        => __( 'Custom Time', $this->base->plugin->name ),
                    'custom_field'  => __( 'Custom Time (based on Custom Field / Post Meta Value)', $this->base->plugin->name ),
                );

                // If The Events Calendar is installed and we're returning Schedule Options for Events, add an option
                if ( class_exists( 'Tribe__Events__Main' ) && $post_type == 'tribe_events' ) {
                    $schedule['_EventStartDate'] = __( 'The Events Calendar: Relative to Event Start Date', $this->base->plugin->name );
                    $schedule['_EventEndDate'] = __( 'The Events Calendar: Relative to Event End Date', $this->base->plugin->name );
                }

                // If we're on the Post Screen, add a specific option now
                if ( $is_post_screen ) {
                    $schedule['specific'] = __( 'Specific Date and Time', $this->base->plugin->name );
                }
                break;
                
        }

        /**
         * Defines the available schedule options for each individual status.
         *
         * @since   3.0.0
         *
         * @param   array   $schedule   Schedule Options
         */
        $schedule = apply_filters( $this->base->plugin->filter_name . '_get_schedule_options', $schedule );

        // Return filtered results
        return $schedule;

    }

    /**
     * Helper method to retrieve public Post Types
     *
     * @since   3.0.0
     *
     * @return  array   Public Post Types
     */
    public function get_post_types() {

        // Get public Post Types
        $types = get_post_types( array(
            'public' => true,
        ), 'objects' );

        // Filter out excluded post types
        $excluded_types = $this->get_excluded_post_types();
        if ( is_array( $excluded_types ) ) {
            foreach ( $excluded_types as $excluded_type ) {
                unset( $types[ $excluded_type ] );
            }
        }

        /**
         * Defines the available Post Type Objects that can have statues defined and be sent to social media.
         *
         * @since   3.0.0
         *
         * @param   array   $types  Post Types
         */
        $types = apply_filters( $this->base->plugin->filter_name . '_get_post_types', $types );

        // Return filtered results
        return $types;

    }

    /**
     * Helper method to retrieve excluded Post Types, which should not send
     * statuses to the API
     *
     * @since   3.0.0
     *
     * @return  array   Excluded Post Types
     */
    public function get_excluded_post_types() {

        // Get excluded Post Types
        $types = array(
            'attachment',
            'revision',
            'elementor_library',
        );

        /**
         * Defines the Post Type Objects that cannot have statues defined and not be sent to social media.
         *
         * @since   3.0.0
         *
         * @param   array   $types  Post Types
         */
        $types = apply_filters( $this->base->plugin->filter_name . '_get_excluded_post_types', $types );

        // Return filtered results
        return $types;

    }

    /**
     * Helper method to retrieve available tags for status updates
     *
     * @since   3.0.0
     *
     * @param   string  $post_type  Post Type
     * @return  array               Tags
     */
    public function get_tags( $post_type ) {

        // Get post type
        $post_types = $this->get_post_types();

        // Build tags array
        $tags = array(
            'post' => array(
                '{sitename}'            => __( 'Site Name', $this->base->plugin->name ),
                '{title}'               => __( 'Post Title', $this->base->plugin->name ),
                '{excerpt}'             => __( 'Post Excerpt (Full)', $this->base->plugin->name ),
                '{excerpt(?)}'           => array(
                    'question'      => __( 'Enter the maximum number of characters the Post Excerpt should display.', $this->base->plugin->name ),
                    'default_value' => '150',
                    'replace'       => '?',
                    'label'         => __( 'Post Excerpt (Character Limited)', $this->base->plugin->name ),
                ),
                '{excerpt(?_words)}'     => array(
                    'question'      => __( 'Enter the maximum number of words the Post Excerpt should display.', $this->base->plugin->name ),
                    'default_value' => '55',
                    'replace'       => '?',
                    'label'         => __( 'Post Excerpt (Word Limited)', $this->base->plugin->name ),
                ),
                '{content}'             => __( 'Post Content (Full)', $this->base->plugin->name ),
                '{content(?)}'           => array(
                    'question'      => __( 'Enter the maximum number of characters the Post Content should display.', $this->base->plugin->name ),
                    'default_value' => '150',
                    'replace'       => '?',
                    'label'         => __( 'Post Content (Character Limited)', $this->base->plugin->name ),
                ),
                '{content(?_words)}'     => array(
                    'question'      => __( 'Enter the maximum number of words the Post Content should display.', $this->base->plugin->name ),
                    'default_value' => '55',
                    'replace'       => '?',
                    'label'         => __( 'Post Content (Word Limited)', $this->base->plugin->name ),
                ),
                '{date}'                => __( 'Post Date', $this->base->plugin->name ),
                '{url}'                 => __( 'Post URL', $this->base->plugin->name ),
                '{id}'                  => __( 'Post ID', $this->base->plugin->name ),
            ),
        );

        /**
         * Defines Dynamic Status Tags that can be inserted into status(es) for the given Post Type.
         * These tags are also added to any 'Insert Tag' dropdowns.
         *
         * @since   3.0.0
         *
         * @param   array   $tags       Dynamic Status Tags
         * @param   string  $post_type  Post Type
         */
        $tags = apply_filters( $this->base->plugin->filter_name . '_get_tags', $tags, $post_type );

        // Return filtered results
        return $tags;

    }


    /**
     * Helper method to retrieve Post actions
     *
     * @since   3.0.0
     *
     * @return  array           Post Actions
     */
    public function get_post_actions() {

        // Build post actions
        $actions = array(
            'publish'   => __( 'Publish', $this->base->plugin->name ),
            'update'    => __( 'Update', $this->base->plugin->name ),
        );

        /**
         * Defines the Post actions which trigger status(es) to be sent to social media.
         *
         * @since   3.0.0
         *
         * @param   array   $actions    Post Actions
         */
        $actions = apply_filters( $this->base->plugin->filter_name . '_get_post_actions', $actions );

        // Return filtered results
        return $actions;

    }

    /**
     * Helper method to retrieve Post actions, with labels in the past tense.
     *
     * @since   3.7.2
     *
     * @return  array           Post Actions
     */
    public function get_post_actions_past_tense() {

        // Build post actions
        $actions = array(
            'publish'   => __( 'Published', $this->base->plugin->name ),
            'update'    => __( 'Updated', $this->base->plugin->name ),
        );

        /**
         * Defines the Post actions which trigger status(es) to be sent to social media,
         * with labels set to the past tense.
         *
         * @since   3.0.0
         *
         * @param   array   $actions    Post Actions
         */
        $actions = apply_filters( $this->base->plugin->filter_name . '_get_post_actions_past_tense', $actions );

        // Return filtered results
        return $actions;

    }

    /**
     * Helper method to retrieve Featured Image Options
     *
     * @since   3.4.3
     *
     * @param   bool    $network    Network (false = defaults)
     * @param   string  $post_type  Post Type
     * @return  array               Featured Image Options
     */
    public function get_featured_image_options( $network = false, $post_type = false ) {

        // If a Post Type has been specified, get its featured_image label
        $label = __( 'Feat. Image', $this->base->plugin->name );
        if ( $post_type != false && $post_type != 'bulk' ) {
            $post_type_object = get_post_type_object( $post_type );
            $label = $post_type_object->labels->featured_image;
        }

        // Build featured image options, depending on the Plugin
        switch ( $this->base->plugin->name ) {

            case 'wp-to-buffer':
                $options = array(
                    0 => __( 'Use OpenGraph Settings', $this->base->plugin->name ),
                    2 => sprintf( __( 'Use %s, not Linked to Post', $this->base->plugin->name ), $label ),
                );
                break;

            case 'wp-to-buffer-pro':
                $options = array(
                    0 => __( 'Use OpenGraph Settings', $this->base->plugin->name ),
                    1 => sprintf( __( 'Use %s, Linked to Post', $this->base->plugin->name ), $label ),
                    2 => sprintf( __( 'Use %s, not Linked to Post', $this->base->plugin->name ), $label ),
                );
                break;

            case 'wp-to-hootsuite':
                $options = array(
                    -1  => __( 'No Image', $this->base->plugin->name ),
                    2   => sprintf( __( 'Use %s, not Linked to Post', $this->base->plugin->name ), $label ),
                );
                break;

            case 'wp-to-hootsuite-pro':
                $options = array(
                    -1  => __( 'No Image', $this->base->plugin->name ),
                    2   => sprintf( __( 'Use %s, not Linked to Post', $this->base->plugin->name ), $label ),
                );
                break;

            case 'wp-to-socialpilot':
                $options = array(
                    0 => __( 'Use OpenGraph Settings', $this->base->plugin->name ),
                    2 => sprintf( __( 'Use %s, not Linked to Post', $this->base->plugin->name ), $label ),
                );
                break;

            case 'wp-to-socialpilot-pro':
                $options = array(
                    0 => __( 'Use OpenGraph Settings', $this->base->plugin->name ),
                    1 => sprintf( __( 'Use %s, Linked to Post', $this->base->plugin->name ), $label ),
                    2 => sprintf( __( 'Use %s, not Linked to Post', $this->base->plugin->name ), $label ),
                );
                break;
                
        }

        // Depending on the network, remove some options that aren't supported
        switch ( $network ) {
            /**
             * Twitter
             * - Remove "Use Feat. Image, Linked to Post"
             */
            case 'twitter':
                unset( $options[1] );
                break;

            /**
             * Instagram, Pinterest
             * - Remove all options excluding "Use Feat. Image, not Linked to Post"
             */
            case 'instagram':
            case 'pinterest':
                unset( $options[0], $options[1] );
                break;
        }

        /**
         * Defines the available Featured Image select dropdown options on a status, depending
         * on the Plugin and Social Network the status message is for.
         *
         * @since   3.4.3
         *
         * @param   array   $options    Featured Image Dropdown Options
         * @param   string  $network    Social Network
         */
        $options = apply_filters( $this->base->plugin->filter_name . '_get_featured_image_options', $options, $network );

        // Return filtered results
        return $options;

    }

    /**
     * Helper method to return template tags that cannot have a character limit applied to them.
     *
     * @since   3.7.8
     *
     * @return  array   Tags
     */
    public function get_tags_excluded_from_character_limit() {

        $tags = array(
            'date',
            'url',
            'id',
            'author_user_email',
            'author_user_url',
        );

        /**
         * Defines the tags that cannot have a character limit applied to them, as doing so would
         * wrongly concatenate data (e.g. a URL would become malformed).
         *
         * @since   3.7.8
         *
         * @param   array   $tags   Tags
         */
        $tags = apply_filters( $this->base->plugin->filter_name . '_get_tags_excluded_from_character_limit', $tags );

        // Return filtered results
        return $tags;

    }

    /**
     * Helper method to retrieve character limits, depending on the social media network
     *
     * @since   3.4.2
     *
     * @return  array   Character Limits
     */
    public function get_character_limits() {

        $character_limits = array(
            'twitter'   => 280,
            'pinterest' => 500,
            'instagram' => 2200,
            'facebook'  => 5000,
            'linkedin'  => 700,
            'google'    => 5000
        );

        /**
         * Defines the character limits for status messages for each social network.
         *
         * @since   3.4.2
         *
         * @param   array   $character_limits   Character Limits
         */
        $character_limits = apply_filters( $this->base->plugin->filter_name . '_get_character_limits', $character_limits );

        // Return filtered results
        return $character_limits;

    }

    /**
     * Helper method to retrieve the character limit for the given service.
     *
     * @since   3.4.2
     *
     * @param   string  $service    Social Media Service
     * @return  int                 Character Limit
     */
    public function get_character_limit( $service ) {

        // Assume there is no limit
        $character_limit = 0;

        // Get character limits for all social networks
        $character_limits = $this->get_character_limits();

        // Bail if the service doesn't have a character limit defined
        if ( ! isset( $character_limits[ $service ] ) ) {
            return $character_limit;
        }

        // Cast as an integer
        $character_limit = absint( $character_limits[ $service ] );

        /**
         * Defines the character limit for the given social media service.
         *
         * @since   3.4.2
         *
         * @param   int     $character_limit    Character Limit
         * @param   string  $service            Social Media Service
         */
        $character_limit = apply_filters( $this->base->plugin->filter_name . '_get_character_limit', $character_limit, $service );

        // Return filtered result
        return $character_limit;

    }

    /**
     * Helper method to retrieve transient expiration time
     *
     * @since   3.0.0
     *
     * @return  int     Expiration Time (seconds)
     */
    public function get_transient_expiration_time() {

        // Set expiration time for all transients = 12 hours
        $expiration_time = ( 12 * HOUR_IN_SECONDS );

        /**
         * Defines the number of seconds before expiring transients.
         *
         * @since   3.0.0
         *
         * @param   int     $expiration_time    Transient Expiration Time, in seconds
         */
        $expiration_time = apply_filters( $this->base->plugin->filter_name . '_get_transient_expiration_time', $expiration_time );

        // Return filtered results
        return $expiration_time;

    }

    /**
     * Helper method to return an array of Plugins that output OpenGraph data
     * which can be used by this Plugin for sharing the Featured Image
     *
     * @since   3.7.9
     *
     * @return  array   Plugins
     */
    public function get_opengraph_seo_plugins() {

        // Define Plugins
        $plugins = array(
            'all-in-one-seo-pack/all_in_one_seo_pack.php',
            'wordpress-seo/wp-seo.php',
        );

        /**
         * Defines the Plugins that output OpenGraph metadata on Posts, Pages
         * and Custom Post Types.
         *
         * @since   3.7.9
         *
         * @param   array   $plugins    Plugins
         */
        $plugins = apply_filters( $this->base->plugin->filter_name . '_get_opengraph_seo_plugins', $plugins );

        // Return filtered results
        return $plugins;

    }

    /**
     * Defines the registered filters that can be used on the Log WP_List_Table
     *
     * @since   3.9.8
     *
     * @return  array   Filters
     */
    public function get_log_filters() {

        // Define filters
        $filters = array( 
            'action',
            'profile_id',
            'result',
            'request_sent_start_date',
            'request_sent_end_date',
            'orderby',
            'order',
        );

        /**
         * Defines the registered filters that can be used on the Log WP_List_Tables.
         *
         * @since   3.9.8
         *
         * @param   array   $filters    Filters
         */
        $filters = apply_filters( $this->base->plugin->filter_name . '_get_log_filters', $filters );

        // Return filtered results
        return $filters;

    }

    /**
     * Converts WordPress' GMT Offset (e.g. -5, +3.3) to an offset value compatible with
     * WordPress' DateTime object (e.g. -0500, +0330)
     *
     * @since   3.6.2
     *
     * @param   float   $gmt_offset     GMT Offset
     * @return  string                  GMT Offset Value
     */
    public function convert_wordpress_gmt_offset_to_offset_value( $gmt_offset ) {

        // Don't do anything if the offset is zero
        if ( $gmt_offset == 0 ) {
            return '+0000';
        }

        // Define the GMT offset string e.g. +0100, -0300 etc.
        if ( $gmt_offset > 0 ) {
            if ( $gmt_offset < 10 ) {
                $gmt_offset = '0' . abs( $gmt_offset );
            } else {
                $gmt_offset = abs( $gmt_offset );
            }

            $gmt_offset = '+' . $gmt_offset;
        } elseif ( $gmt_offset < 0 ) {
            if ( $gmt_offset > -10 ) {
                $gmt_offset = '0' . abs( $gmt_offset );
            } else {
                $gmt_offset = abs( $gmt_offset );
            }

            $gmt_offset = '-' . $gmt_offset;
        }

        // If the GMT offset contains .5, change this to :30
        // Otherwise pad the GMT offset
        if ( strpos( $gmt_offset, '.5' ) !== false ) {
            $gmt_offset = str_replace( '.5', ':30', $gmt_offset );
        } else {
            $gmt_offset .= '00';
        }

        /**
         * Converts WordPress' GMT Offset (e.g. -5, +3.3) to an offset value compatible with
         * WordPress' DateTime object (e.g. -0500, +0330)
         *
         * @since   3.6.2
         *
         * @param   string      $text   Parsed Text
         */
        $gmt_offset = apply_filters( $this->base->plugin->filter_name . '_common_convert_wordpress_gmt_offset_to_offset_value', $gmt_offset );

        // Return
        return $gmt_offset;

    }

}