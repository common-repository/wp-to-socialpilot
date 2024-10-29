<?php
/**
 * SocialPilot API class
 * 
 * @package WP_To_Social_Pro
 * @author  Tim Carr
 * @version 1.0.0
 */
class WP_To_Social_Pro_SocialPilot_API {

    /**
     * Holds the base class object.
     *
     * @since   1.0.0
     *
     * @var     object
     */
    public $base;

    /**
     * Holds the SocialPilot Application's Client ID
     *
     * @since   1.0.0
     *
     * @var     string
     */
    private $client_id = 'ia1tqrh4quq7kafb2rl34ugpjwou7r9p1ajr20ws';

    /**
     * Holds the oAuth Gateway endpoint, used to exchange a code for an access token
     *
     * @since   1.0.0
     *
     * @var     string
     */
    private $oauth_gateway_endpoint = 'https://www.wpzinc.com/?oauth=socialpilot';

    /**
     * Holds the API endpoint
     *
     * @since   1.0.0
     *
     * @var     string
     */
    private $api_endpoint = 'https://panel.socialpilot.co/oauth';
    
    /**
     * Access Token
     *
     * @since   1.0.0
     *
     * @var     string
     */
    public $access_token = '';

    /**
     * Refresh Token
     *
     * @since   1.0.0
     *
     * @var     string
     */
    public $refresh_token = '';

    /**
     * Token Expiry Timestamp
     *
     * @since   1.0.0
     *
     * @var     int
     */
    public $token_expires = false;

    /**
     * Constructor
     *
     * @since   1.0.0
     *
     * @param   object $base    Base Plugin Class
     */
    public function __construct( $base ) {

        // Store base class
        $this->base = $base;

    }

    /**
     * Returns the oAuth 2 URL used to begin the oAuth process
     *
     * @since   1.0.0
     *
     * @return  string  oAuth URL
     */
    public function get_oauth_url() {

        // Return oAuth URL
        return $this->oauth_gateway_endpoint . '&state=' . urlencode( admin_url( 'admin.php?page=' . $this->base->plugin->name . '-settings' ) );

    }

    /**
     * Returns the SocialPilot URL where the user can connect their social media accounts
     * to SocialPilot
     *
     * @since   1.0.0
     *
     * @return  string  URL
     */
    public function get_connect_profiles_url() {

        // Return Connect Profiles URL
        return 'https://panel.socialpilot.co/accounts/create';

    }

    /**
     * Returns the SocialPilot URL where the user can change the timezone for the
     * given profile ID.
     *
     * @since   1.0.0
     *
     * @param   string  $profile_id     Profile ID
     * @return  string                  Timezone Settings URL
     */
    public function get_timezone_settings_url( $profile_id ) {

        // @TODO
        return 'https://socialpilot.com/app/profile/' . $profile_id . '/schedule';

    }

    /**
     * Sets this class' access and refresh tokens
     *
     * @since   1.0.0
     *
     * @param   string  $access_token    Access Token
     * @param   string  $refresh_token   Refresh Token
     * @param   mixed   $token_expires   Token Expires (false | timestamp)
     */
    public function set_tokens( $access_token = '', $refresh_token = '', $token_expires = false ) {

        $this->access_token = $access_token;
        $this->refresh_token = $refresh_token;
        $this->token_expires = $token_expires;

    }

    /**
     * Checks if an access token was set.  Called by any function which 
     * performs a call to the API
     *
     * @since   1.0.0
     *
     * @return  bool    Token Exists
     */
    private function check_access_token_exists() {

        if ( empty( $this->access_token ) ) {
            return false;
        }

        return true;

    }

    /**
     * Checks if a refresh token was set.  Called by any function which 
     * performs a call to the API
     *
     * @since   1.0.0
     *
     * @return  bool    Token Exists
     */
    private function check_refresh_token_exists() {

        if ( empty( $this->refresh_token ) ) {
            return false;
        }

        return true;

    }

    /**
     * Returns the User object
     *
     * @since   1.0.0
     *
     * @return  mixed   WP_Error | User object
     */
    public function user() {

        // Check access token
        if ( ! $this->check_access_token_exists() ) {
            return false;
        }

        return $this->get( 'user.json' );

    }

    /**
     * Returns a list of Social Media Profiles attached to the SocialPilot Account.
     *
     * @since   1.0.0
     *
     * @param   bool    $force                      Force API call (false = use WordPress transient)
     * @param   int     $transient_expiration_time  Transient Expiration Time
     * @return  mixed                               WP_Error | Profiles object
     */
    public function profiles( $force = false, $transient_expiration_time ) {

        // Check access token
        if ( ! $this->check_access_token_exists() ) {
            return false;
        }

        // Setup profiles array
        $profiles = array();

        // Check if our WordPress transient already has this data.
        // This reduces the number of times we query the API
        if ( $force || false === ( $profiles = get_transient( $this->base->plugin->name . '_socialpilot_api_profiles' ) ) ) {
            // Get profiles
            $results = $this->get( 'accounts' );

            // Check for errors
            if ( is_wp_error( $results ) ) {
                return $results;
            }

            // Check data is valid
            foreach ( $results as $result ) {
                // We don't support Instagram or Pinterest in the Free version
                if ( class_exists( 'WP_To_SocialPilot' ) ) {
                    $service = $this->get_service( $result->account_type );
                    if ( in_array( $service, array( 'instagram', 'pinterest', 'google', 'tumblr', 'vk' ) ) ) {
                        continue;
                    }
                }

                // Add profile to array
                $profiles[ $result->id ] = array(
                    'id'                => $result->id,                                             // SocialPilot ID
                    'social_network_id' => $result->unique_id,                                      // Social Network (e.g. FB, Twitter) ID
                    'formatted_service' => $this->get_formatted_service( $result->account_type ),
                    'formatted_username'=> $result->nickname,                                       // Account Name
                    'service'           => $this->get_service( $result->account_type ),
                    'timezone'          => false,
                );

                // Twitter's 2019 Developer Policies mean that the formatted username and profile image are no longer returned.
                // In turn, SocialPilot cannot provide this information, so we must directly query for it through the Twitter API.
                if ( $result->account_type == 'twitter' && empty( $profiles[ $result->id ]['formatted_username'] ) ) {
                    // Fetch Twitter username from the API
                    // The API class will check the transient first and use cached results if available
                    $twitter_username = $this->base->get_class( 'twitter_api' )->get_username_by_id( $profiles[ $result->id ]['social_network_id'], $transient_expiration_time );
                    if ( is_wp_error( $twitter_username ) ) {
                        continue;
                    }

                    // Add username to results
                    $profiles[ $result->id ]['formatted_username'] = $twitter_username;
                }

                // Pinterest: add subprofiles
                if ( isset( $result->subprofiles ) && count( $result->subprofiles ) > 0 ) {
                    $profiles[ $result->id ]['subprofiles'] = array();
                    foreach ( $result->subprofiles as $sub_profile ) {
                        $profiles[ $result->id ]['subprofiles'][ $sub_profile->id ] = array(
                            'id'        => $sub_profile->id,
                            'name'      => $sub_profile->name,
                            'service'   => $sub_profile->service,
                        );
                    }
                }
            }
            
            // Store profiles in transient
            set_transient( $this->base->plugin->name . '_socialpilot_api_profiles', $profiles, $transient_expiration_time );
        }

        // Return results
        return $profiles;

    }

    /**
     * Depending on the social media profile type, return the formatted service name.
     *
     * @since   1.0.0
     *
     * @param   string  $type   Social Media Profile Type
     * @return  string          Formatted Social Media Profile Service Name
     */
    private function get_formatted_service( $type ) {

        switch ( $type ) {

            case 'twitter':
                return __( 'Twitter', $this->base->plugin->name );
                break;

            case 'instagram':
                return __( 'Instagram', $this->base->plugin->name );
                break;

            case 'facebook-official':
                return __( 'Facebook Page', $this->base->plugin->name );
                break;

            case 'users':
                return __( 'Facebook Group', $this->base->plugin->name );
                break;
                
            case 'suitcase':
                return __( 'LinkedIn Page', $this->base->plugin->name );
                break;

            case 'linkedin':
                return __( 'LinkedIn', $this->base->plugin->name );
                break;

            case 'briefcase':
                return __( 'Google My Business', $this->base->plugin->name );
                break;

            case 'pinterest-p':
                return __( 'Pinterest', $this->base->plugin->name );
                break;

            case 'tumblr':
                return __( 'Tumblr', $this->base->plugin->name );
                break;

            default:
                return '';

        }

    }

    /**
     * Depending on the social media profile type, return the service name.
     *
     * @since   1.0.0
     *
     * @param   string  $type   Social Media Profile Type
     * @return  string          Social Media Profile Service Name
     */
    private function get_service( $type ) {

        switch ( $type ) {

            case 'facebook-official':
            case 'users':
                return 'facebook';
                break;

            case 'twitter':
                return 'twitter';
                break;

            case 'instagram':
                return 'instagram';
                break;

            case 'linkedin':
            case 'suitcase':
                return 'linkedin';
                break;

            case 'briefcase':
                return 'google';
                break;

            case 'pinterest-p':
                return 'pinterest';
                break;

            case 'tumblr':
                return 'tumblr';
                break;

            default:
                return '';

        }

    }

    /**
     * Creates an Update
     *
     * @since   1.0.0
     *
     * @return  mixed   WP_Error | Update object
     */
    public function updates_create( $params ) {

        // Check access token
        if ( ! $this->check_access_token_exists() ) {
            return false;
        }

        // Assume we'll use the post/update endpoint
        $endpoint = 'post/update';

        // Convert parameters into SocialPilot API compatible params
        $status = array(
            'post_content'  => $params['text'],
            'account_id'    => $params['profile_ids'],
        );

        // Scheduling
        if ( isset( $params['now'] ) && $params['now'] ) {
            $status['now'] = $params['now'];
        } elseif ( isset( $params['scheduled_at'] ) ) {
            $status['schedule_date'] = $params['scheduled_at'];
        }

        /**
         * Media
         * SocialPilot has two different endpoints depending on the status setting:
         * Use Feat. Image, Linked to Post: []
         * Use Feat. Image, not Linked to Post: []
         */
        if ( isset( $params['media'] ) && isset( $params['media']['link'] ) ) {
            // Use Feat. Image, Linked to Post
            $status['preview'] = array(
                'title'         => $params['media']['title'],
                'url'           => $params['media']['link'],
                'image'         => $params['media']['picture'],
                'description'   => $params['media']['description'],
            );
        } elseif ( isset( $params['media'] ) && isset( $params['media']['picture'] ) ) {
            // Use Feat. Image, not Linked to Post
            $status['image_url'] = $params['media']['picture'];

            // Change the endpoint to send this status to
            $endpoint = 'post/updatewithimage';
        }

        // Send request
        $result = $this->post( $endpoint, $status );

        // Bail if the result is an error
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Return array of just the data we need to send to the Plugin
        return array(
            'profile_id'        => $status['account_id'][0],
            'message'           => $result->msg,
            'status_text'       => $status['post_content'],
            'status_created_at' => current_time( 'timestamp' ),
            'due_at'            => ( isset( $status['schedule_date'] ) ? strtotime( $status['schedule_date'] ) : false ),
        );

    }

    /**
     * Private function to perform a GET request
     *
     * @since  1.0.0
     *
     * @param  string  $cmd        Command (required)
     * @param  array   $params     Params (optional)
     * @return mixed               WP_Error | object
     */
    private function get( $cmd, $params = array() ) {

        return $this->request( $cmd, 'get', $params );

    }

    /**
     * Private function to perform a POST request
     *
     * @since  1.0.0
     *
     * @param  string  $cmd        Command (required)
     * @param  array   $params     Params (optional)
     * @return mixed               WP_Error | object
     */
    private function post( $cmd, $params = array() ) {

        return $this->request( $cmd, 'post', $params );

    }

    /**
     * Main function which handles sending requests to the SocialPilot API
     *
     * @since   1.0.0
     *
     * @param   string  $cmd        Command
     * @param   string  $method     Method (get|post)
     * @param   array   $params     Parameters (optional)
     * @return  mixed               WP_Error | object
     */
    private function request( $cmd, $method = 'get', $params = array() ) {

        // Check required parameters exist
        if ( empty( $this->access_token ) ) {
            return new WP_Error( 'missing_access_token', __( 'No access token was specified' ) );
        }

        // Add access token to command, depending on the command's format
        if ( strpos ( $cmd, '?' ) !== false ) {
            $cmd .= '&access_token=' . $this->access_token;
        } else {
            $cmd .= '?access_token=' . $this->access_token;
        }

        // Build endpoint URL
        $url = $this->api_endpoint . '/' . $cmd;

        // Define the timeout
        $timeout = 20;

        /**
         * Defines the number of seconds before timing out a request to the SocialPilot API.
         *
         * @since   1.0.0
         *
         * @param   int     $timeout    Timeout, in seconds
         */
        $timeout = apply_filters( $this->base->plugin->name . '_socialpilot_api_request', $timeout );

        // Request via WordPress functions
        $result = $this->request_wordpress( $url, $method, $params, $timeout );

        // Request via cURL if WordPress functions failed
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
            if ( is_wp_error( $result ) ) {
                $result = $this->request_curl( $url, $method, $params, $timeout );
            }
        }

        // Result will be WP_Error or the data we expect
        return $result;

    }

    /**
     * Performs POST and GET requests through WordPress wp_remote_post() and
     * wp_remote_get() functions
     *
     * @since   1.0.0
     *
     * @param   string  $url        URL
     * @param   string  $method     Method (post|get)
     * @param   array   $params     Parameters
     * @param   int     $timeout    Timeout, in seconds (default: 10)
     * @return  mixed               WP_Error | object
     */
    private function request_wordpress( $url, $method, $params, $timeout = 20 ) {

        // Send request
        switch ( $method ) {
            /**
             * GET
             */
            case 'get':
                $result = wp_remote_get( $url, array(
                    'body'      => $params,
                    'timeout'   => $timeout,
                ) );
                break;
            
            /**
             * POST
             */
            case 'post':
                $result = wp_remote_post( $url, array(
                    'body'      => $params,
                    'timeout'   => $timeout,
                ) );
                break;
        }

        // If an error occured, return it now
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Fetch response code and body
        $http_code = wp_remote_retrieve_response_code( $result );
        $body = json_decode( wp_remote_retrieve_body( $result ) );

        // If the HTTP code isn't 200, something went wrong
        if ( $http_code != 200 ) {
            // Define the error message
            $message = array();
            if ( isset( $body->msg ) ) {
                $message[] = $body->error;
            }

            // Return WP_Error
            return new WP_Error( 
                $http_code, 
                'SocialPilot API Error: HTTP Code ' . $http_code . '. ' . implode( "\n", $message ) 
            );
        }

        // All OK, return response
        return $body;

    }

    /**
     * Performs POST and GET requests through PHP's curl_exec() function.
     *
     * If this function is called, request_wordpress() failed, most likely
     * due to a DNS lookup failure or CloudFlare failing to respond.
     *
     * We therefore use CURLOPT_RESOLVE, to tell cURL the IP address for the domain.
     *
     * @since   1.0.0
     *
     * @param   string  $url        URL
     * @param   string  $method     Method (post|get)
     * @param   array   $params     Parameters
     * @param   int     $timeout    Timeout, in seconds (default: 20)
     * @return  mixed               WP_Error | object
     */
    private function request_curl( $url, $method, $params, $timeout = 20 ) {

        // Init
        $ch = curl_init();

        // Set request specific options
        switch ( $method ) {
            /**
             * GET
             */
            case 'get':
                curl_setopt_array( $ch, array(
                    CURLOPT_URL             => $url . '&' . http_build_query( $params ),
                    /*
                    CURLOPT_RESOLVE         => array( 
                        str_replace( 'https://', '', $this->api_endpoint ) . ':443:104.16.97.40',
                        str_replace( 'https://', '', $this->api_endpoint ) . ':443:104.16.98.40',
                    ),
                    */
                ) );
                break;

            /**
             * POST
             */
            case 'post':
                curl_setopt_array( $ch, array(
                    CURLOPT_URL             => $url,
                    CURLOPT_POST            => true,
                    CURLOPT_POSTFIELDS      => http_build_query( $params ),
                    /*
                    CURLOPT_RESOLVE         => array( 
                        str_replace( 'https://', '', $this->api_endpoint ) . ':443:104.16.97.40',
                        str_replace( 'https://', '', $this->api_endpoint ) . ':443:104.16.98.40',
                    ),
                    */
                ) );
                break;
        }

        // Set shared options
        curl_setopt_array( $ch, array(
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_CONNECTTIMEOUT  => $timeout,
            CURLOPT_TIMEOUT         => $timeout,
        ) );

        // Execute
        $result     = curl_exec( $ch );
        $http_code  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $error      = curl_error( $ch );
        curl_close( $ch );

        // If our error string isn't empty, something went wrong
        if ( ! empty( $error ) ) {
            return new WP_Error( $this->base->plugin->name . '_api_request_curl', $error );
        }

        // If HTTP code isn't 200, something went wrong
        if ( $http_code != 200 ) {
            // Decode error message
            $result = json_decode( $result );

            // Return basic WP_Error if we don't have any more information
            if ( is_null( $result ) ) {
                return new WP_Error(
                    $http_code,
                    'SocialPilot API Error: HTTP Code ' . $http_code . '. Sorry, we don\'t have any more information about this error. Please try again.'
                );
            }

            // Define the error message
            $message = array();
            if ( isset( $result->msg ) ) {
                $message[] = $result->msg;
            }

            // Return WP_Error
            return new WP_Error( $http_code, 'SocialPilot API Error: HTTP Code ' . $http_code . '. ' . implode( "\n", $message )  );
        }
        
        // All OK, return response
        return json_decode( $result );

    }

}