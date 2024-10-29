<?php
/**
 * Logging class
 * 
 * @package WP_To_Social_Pro
 * @author  Tim Carr
 * @version 3.0.0
 */
class WP_To_Social_Pro_Log {

    /**
     * Holds the base class object.
     *
     * @since   3.2.0
     *
     * @var     object
     */
    public $base;

    /**
     * Holds the DB table name
     *
     * @since   3.9.6
     *
     * @var     string
     */
    private $table;

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

        // Define the database table name
        $this->table = 'to_' . strtolower( $this->base->plugin->account ) . '_log';

        // Actions
        add_action( 'current_screen', array( $this, 'run_log_table_bulk_actions' ) );
        add_action( 'current_screen', array( $this, 'run_log_table_filters' ) );
        add_action( 'admin_menu', array( $this, 'admin_meta_boxes' ) );
        add_action( 'wp_loaded', array( $this, 'export' ) );

    }

    /**
     * Activation routines for this Model
     *
     * @since   3.9.6
     *
     * @global  $wpdb   WordPress DB Object
     */
    public function activate() {

        global $wpdb;

        // Enable error output if WP_DEBUG is enabled.
        $wpdb->show_errors = true;

        // Create database tables
        $wpdb->query( " CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . $this->table . " (
                            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                            `post_id` int(11) NOT NULL,
                            `action` enum('publish','update','repost','bulk_publish') DEFAULT NULL,
                            `request_sent` datetime NOT NULL,
                            `profile_id` varchar(191) NOT NULL,
                            `profile_name` varchar(191) NOT NULL DEFAULT '',
                            `result` enum('success','test','pending','warning','error') NOT NULL DEFAULT 'success',
                            `result_message` text,
                            `status_text` text,
                            `status_created_at` datetime DEFAULT NULL,
                            `status_due_at` datetime DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            KEY `post_id` (`post_id`),
                            KEY `action` (`action`),
                            KEY `result` (`result`),
                            KEY `profile_id` (`profile_id`)
                        ) DEFAULT CHARSET=" . ( ! empty( $wpdb->charset ) ? $wpdb->charset : 'utf8mb4' ) . " AUTO_INCREMENT=1" ); 

    }

    /**
     * Run any bulk actions on the Log WP_List_Table
     *
     * @since   3.9.6
     */
    public function run_log_table_bulk_actions() {

        // Get screen
        $screen = $this->base->get_class( 'screen' )->get_current_screen();

        // Bail if we're not on the Log screen
        if ( $screen['screen'] != 'log' ) {
            return;
        }

        // Get bulk action from the fields that might contain it
        $bulk_action = array_values( array_filter( array(
            ( isset( $_REQUEST['bulk_action'] ) && $_REQUEST['bulk_action'] != -1 ? sanitize_text_field( $_REQUEST['bulk_action'] ) : '' ),
            ( isset( $_REQUEST['bulk_action2'] ) && $_REQUEST['bulk_action2'] != -1 ? sanitize_text_field( $_REQUEST['bulk_action2'] ) : '' ),
            ( isset( $_REQUEST['bulk_action3'] ) && ! empty( $_REQUEST['bulk_action3'] ) ? sanitize_text_field( $_REQUEST['bulk_action3'] ) : '' ),
        ) ) );

        // Bail if no bulk action
        if ( ! is_array( $bulk_action ) ) {
            return;
        }
        if ( ! count( $bulk_action ) ) {
            return;
        }

        // Setup notices class, enabling persistent storage
        $this->base->get_class( 'notices' )->enable_store();
        $this->base->get_class( 'notices' )->set_key_prefix( $this->base->plugin->filter_name . '_' . wp_get_current_user()->ID );

        // Perform Bulk Action
        switch ( $bulk_action[0] ) {
            /**
             * Delete Logs
             */
            case 'delete':
                // Get Post IDs
                if ( ! isset( $_REQUEST['ids'] ) ) {
                    $this->base->get_class( 'notices' )->add_error_notice(
                        __( 'No logs were selected for deletion.', $this->base->plugin->name )
                    );
                    break;
                }

                // Delete Logs by IDs
                $this->delete_by_ids( array_values( $_REQUEST['ids'] ) );

                // Add success notice
                $this->base->get_class( 'notices' )->add_success_notice(
                    sprintf(
                        __( '%s Logs deleted.', $this->base->plugin->name ),
                        count( $_REQUEST['ids'] )
                    )
                );
                break;

            /**
             * Delete All Logs
             */
            case 'delete_all':
                // Delete Logs
                $this->delete_all();

                // Add success notice
                $this->base->get_class( 'notices' )->add_success_notice(
                    __( 'All Logs deleted.', $this->base->plugin->name )
                );
                break;

        }

        // Redirect
        wp_redirect( 'admin.php?page=' . $this->base->plugin->name . '-' . $screen['screen'] );
        die();

    }

    /**
     * Redirect POST filters to a GET URL
     *
     * @since   3.9.6
     */
    public function run_log_table_filters() {

        // Get screen
        $screen = $this->base->get_class( 'screen' )->get_current_screen();

        // Bail if we're not on the Log screen
        if ( $screen['screen'] != 'log' ) {
            return;
        }

        $params = array();
        foreach ( $this->base->get_class( 'common' )->get_log_filters() as $filter ) {
            if ( ! isset( $_POST[ $filter ] ) ) {
                continue;
            }
            if ( empty( $_POST[ $filter ] ) ) {
                continue;
            }

            $params[ $filter ] = esc_html( $_POST[ $filter ] );
        }


        // If params don't exist, exit
        if ( ! count( $params ) ) {
            return;
        }

        // Redirect
        wp_redirect( 'admin.php?page=' . $this->base->plugin->name . '-' . $screen['screen'] . '&' . http_build_query( $params ) );
        die();

    }

    /**
     * Adds Metaboxes to Post Edit Screens
     *
     * @since   3.0.0
     */
    public function admin_meta_boxes() {

        // Only load if Logging is enabled and Displayed on Posts
        $log_enabled = $this->base->get_class( 'settings' )->get_setting( 'log', '[enabled]' );
        $log_display_on_posts = $this->base->get_class( 'settings' )->get_setting( 'log', '[display_on_posts]' );
        
        if ( ! $log_enabled ) {
            return;
        }
        if ( ! $log_display_on_posts ) {
            return;
        }

        // Check if we need to hide the meta box by the logged in User's role
        $hide_meta_box = $this->base->get_class( 'settings' )->get_setting( 'hide_meta_box_by_roles', '[' . wp_get_current_user()->roles[0] . ']' );

        // Bail if we're hiding the meta boxes for the logged in User's role
        if ( $hide_meta_box ) {
            return;
        }

        // Get Post Types
        $post_types = $this->base->get_class( 'common' )->get_post_types();
        
        // Add meta boxes for each Post Type
        foreach ( $post_types as $post_type => $post_type_obj ) {
            add_meta_box( $this->base->plugin->name . '-log', sprintf( __( '%s Log', $this->base->plugin->name ), $this->base->plugin->displayName ), array( $this, 'output_post_log' ), $post_type, 'normal', 'low' );   
        }

    }

    /**
     * Outputs the plugin's log of existing status update calls made to the API
     *
     * @since   3.0.0
     *
     * @param   WP_Post     $post   Post
     */
    public function output_post_log( $post ) {

        // Get log
        $log = $this->get( $post->ID );

        // Load View
        include_once( $this->base->plugin->folder . 'vendor/views/post-log.php' ); 

    }

    /**
     * Exports a Post's API log file in JSON format
     *
     * @since   3.0.0
     */
    public function export() {

        // Check the user requested a log
        if ( ! isset( $_GET[ $this->base->plugin->name . '-export-log' ] ) ) {
            return;
        }

        // Get log
        $log = $this->get( absint( $_GET['post'] ) );

        // Build JSON
        $json = json_encode( $log );
        
        // Export
        header( "Content-type: application/x-msdownload" );
        header( "Content-Disposition: attachment; filename=log.json" );
        header( "Pragma: no-cache" );
        header( "Expires: 0" );
        echo $json;
        exit();
                
    }

    /**
     * Adds a log entry for the given Post ID
     *
     * @since   3.9.6
     *
     * @param   int     $post_id    Post ID
     * @param   array   $log        Log
     *      enum            $action             Action (publish,update,repost,bulk_publish)
     *      datetime        $request_sent       Request Sent to API
     *      string          $profile_id         Profile ID
     *      string          $profile_name       Profile Name
     *      enum            $result             Result (success,test_mode,pending,error)
     *      string          $result_message     Result Message
     *      string          $status_text        Status Text
     *      datetime        $status_created_at  Status Created At on API
     *      datetime        $status_due_at      Status Scheduled for Publication to Profile
     */
    public function add( $post_id, $log ) {

        global $wpdb;

        // Enable error output if WP_DEBUG is enabled.
        $wpdb->show_errors();

        // Add Post ID to log
        $log['post_id'] = absint( $post_id );

        // Insert Log
        $result = $wpdb->insert( 
            $wpdb->prefix . $this->table,
            $log
        );

    }

    /**
     * Retrieves the log for the given Post ID
     *
     * @since   3.0.0
     *
     * @param   int     $post_id    Post ID
     * @return  array               Log
     */
    public function get( $post_id ) {

        global $wpdb;

        // Get log
        $query = $wpdb->prepare("   SELECT *
                                    FROM " . $wpdb->prefix . $this->table . "
                                    WHERE post_id = %d
                                    ORDER BY id DESC",
                                    absint( $post_id ) ); 
        $log = $wpdb->get_results( $query, ARRAY_A );

        /**
         * Filters the log entries before output.
         *
         * @since   3.0.0
         *
         * @param   array   $log        Post Log
         * @param   int     $post_id    Post ID
         */
        $log = apply_filters( $this->base->plugin->filter_name . '_get_log', $log, $post_id );

        // Return
        return $log;

    }

    /**
     * Returns key/value Profile ID and Name pairs based on all unique
     * Profile IDs in the Log table
     *
     * @since   3.9.6
     *
     * @return  array
     */
    public function get_profile_id_names() {

        global $wpdb;

        $results = $wpdb->get_results( "SELECT profile_id, profile_name
                                        FROM " . $wpdb->prefix . $this->table . "
                                        GROUP BY profile_id
                                        ORDER BY profile_name DESC", ARRAY_A );

        if ( ! $results || ! count( $results ) ) {
            return array();
        }

        $profiles = array();
        foreach ( $results as $result ) {
            if ( empty( $result['profile_id'] ) ) {
                continue;
            }
            $profiles[ $result['profile_id'] ] = ( empty( $result['profile_name'] ) ? __( 'Unknown', $this->base->plugin->name ) : $result['profile_name'] );
        }

        return $profiles;

    }

    /**
     * Searches logs by the given key/value pairs
     *
     * @since   3.9.6
     *
     * @param   string      $order_by   Order Results By
     * @param   string      $order      Order (asc|desc)
     * @param   int         $page       Pagination Offset (default: 0)
     * @param   int         $per_page   Number of Results to Return (default: 20)
     * @param   mixed       $params     Query Parameters (false = all records)
     */
    public function search( $order_by, $order, $page = 0, $per_page = 20, $params = false ) {
   
        global $wpdb;

        // Build where clauses
        $where = $this->build_where_clause( $params );

        // Prepare query
        $query = "  SELECT * FROM " . $wpdb->prefix . $this->table . "
                    LEFT JOIN " . $wpdb->posts . "
                    ON " . $wpdb->prefix . $this->table . ".post_id = " . $wpdb->posts . ".ID";

        // Add where clauses
        if ( $where != false ) {
            $query .= " WHERE " . $where;
        }

        // Order and limit
        $query .= $wpdb->prepare( " ORDER BY " . $order_by . " " . $order . " 
                                    LIMIT %d, %d",
                                    ( ( $page - 1 ) * $per_page ), $per_page );

        // Run and return query results
        return $wpdb->get_results( $query, ARRAY_A );

    }

    /**
     * Gets the number of log records found for the given query parameters
     *
     * @since   3.9.6
     *
     * @param   mixed   $params     Query Parameters (false = all records)
     * @return  int                 Total Records
     */
    public function total( $params = false ) {

        global $wpdb;

        // Build where clauses
        $where = $this->build_where_clause( $params );

        // Prepare query
        $query = "  SELECT COUNT(" . $wpdb->prefix . $this->table . ".id) FROM " . $wpdb->prefix . $this->table . "
                    LEFT JOIN " . $wpdb->posts . "
                    ON " . $wpdb->prefix . $this->table . ".post_id = " . $wpdb->posts . ".ID";

        // Add where clauses
        if ( $where != false ) {
            $query .= " WHERE " . $where;
        }

        // Run and return total records found
        return $wpdb->get_var( $query );

    }

    /**
     * Builds a WHERE SQL clause based on the given column key/values
     *
     * @since   3.9.6
     *
     * @param   array   $params     Query Parameters (false = all records)
     */
    private function build_where_clause( $params ) {

        // Bail if no params
        if ( ! $params ) {
            return false;
        }

        // Build where clauses
        $where = array();
        if ( $params != false && is_array( $params ) && count( $params ) > 0 ) {
            foreach ( $params as $key => $value ) {
                // Skip blank params
                if ( empty( $value ) ) {
                    continue;
                }

                // Build condition based on the key
                switch ( $key ) {
                    case 'post_title':
                        $where[] = $key . " LIKE '%" . $value . "%'";
                        break;

                    case 'request_sent_start_date':
                        if ( ! empty( $params['request_sent_end_date'] ) && $params['request_sent_start_date'] > $params['request_sent_end_date'] ) {
                            $where[] = "request_sent <= '" . $value . "'";
                        } else {
                            $where[] = "request_sent >= '" . $value . "'";
                        }
                        break;

                    case 'request_sent_end_date':
                        if ( ! empty( $params['request_sent_start_date'] ) && $params['request_sent_start_date'] > $params['request_sent_end_date'] ) {
                            $where[] = "request_sent >= '" . $value . "'";
                        } else {
                            $where[] = "request_sent <= '" . $value . "'";
                        }
                        break;

                    default:
                        $where[] = $key . " = '" . $value . "'";
                        break;      
                }
            }
        }

        if ( ! count( $where ) ) {
            return false;
        }

        return implode( ' AND ', $where );

    }

    /**
     * Deletes a single Log entry for the given Log ID
     *
     * @since   3.9.6
     *
     * @param   array   $id     Log ID
     * @return  bool            Success
     */
    public function delete_by_id( $id ) {

        global $wpdb;

        return $wpdb->delete(
            $wpdb->prefix . $this->table,
            array(
                'id' => absint( $id ),
            )
        );

    }

    /**
     * Deletes multiple Log entries for the given Log IDs
     *
     * @since   3.9.6
     *
     * @param   array   $ids    Log IDs
     * @return  bool            Success
     */
    public function delete_by_ids( $ids ) {

        global $wpdb;

        return $wpdb->query( "  DELETE FROM " . $wpdb->prefix . $this->table . "
                                WHERE id IN (" . implode( ',', array_map( 'absint', $ids ) ) . ")" );

    }

    /**
     * Deletes Log entries for the given Post ID
     *
     * @since   3.7.9
     *
     * @param   int     $post_id    Post ID
     */
    public function delete_by_post_id( $post_id ) {

        global $wpdb;

        return $wpdb->delete(
            $wpdb->prefix . $this->table,
            array(
                'post_id' => absint( $post_id ),
            )
        );

    }

    /**
     * Deletes Log entries for the given Post ID and Pending Status
     *
     * @since   3.7.9
     *
     * @param   int     $post_id    Post ID
     */
    public function delete_pending_by_post_id( $post_id ) {

        global $wpdb;

        return $wpdb->delete(
            $wpdb->prefix . $this->table,
            array(
                'post_id'   => absint( $post_id ),
                'status'    => 'pending',
            )
        );

    }

    /**
     * Deletes all Log entries older than the given date
     *
     * @since   3.9.8
     *
     * @param   datetime    $date   Date and Time
     * @return  bool                Success
     */
    public function delete_by_request_sent_cutoff( $date_time ) {

        global $wpdb;

        // Build query
        $query = $wpdb->prepare( "  DELETE FROM " . $wpdb->prefix . $this->table . " 
                                    WHERE request_sent < %s",
                                    $date_time );

        // Run query
        return $wpdb->query( $query );

    }

    /**
     * Deletes all Log entries
     *
     * @since   3.9.6
     *
     * @return  bool    Success
     */
    public function delete_all() {

        global $wpdb;

        return $wpdb->query( "  TRUNCATE TABLE " . $wpdb->prefix . $this->table );

    }

    /**
     * Wrapper for PHP's error_log() function, which will only write
     * to the error log if:
     * - WP_DEBUG = true
     * - WP_DEBUG_DISPLAY = false
     * - WP_DEBUG_LOG = true
     *
     * This will ensure that the output goes to wp-content/debug.log
     *
     * @since   3.6.8
     *
     * @param   mixed   $data          Data to log
     * @param   array   $backtrace     Backtrace data from debug_backtrace()
     */
    public function add_to_debug_log( $data = '', $backtrace = false ) {

        // Bail if no WP_DEBUG, or it's false
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }

        // Bail if no WP_DEBUG_DISPLAY, or it's true
        if ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY ) {
            return;
        }

        // Bail if no WP_DEBUG_LOG, or it's false
        if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
            return;
        }

        // If we need to fetch the class and function name to prefix to the log entry, do so now
        $prefix_data = '';
        if ( $backtrace != false ) {
            if ( isset( $backtrace[0] ) ) {
                if ( isset( $backtrace[0]['class'] ) ) {
                    $prefix_data .= $backtrace[0]['class'];
                }
                if ( isset( $backtrace[0]['function'] ) ) {
                    $prefix_data .= '::' . $backtrace[0]['function'] . '()';
                }
            }
        }

        // If the data is empty, change it to 'called'
        if ( empty( $data ) ) {
            $data = 'Called';
        }

        // If the data is an array or object, convert it to a string
        if ( is_array( $data ) || is_object( $data ) ) {
            $data = print_r( $data, true );
        }

        // If we're prefixing the log entry, do so now
        if ( ! empty( $prefix_data ) ) {
            $data = $prefix_data . ': ' . $data;
        }

        // Add the data to the error log, which will appear in wp-content/debug.log
        error_log( $data );
        
    }

    /**
     * Takes a given array of log results, and builds HTML table row output
     * that can be used by:
     * - Posts > Log Meta Box
     * - Bulk Publish > Results Screen
     *
     * @since   3.7.9
     *
     * @param   array   $log                Log Results
     * @param   bool    $is_wp_list_table   Is Output for a WP_List_Table (adds checkbox and Post ID columns)
     * @return  string                      Table Rows HTML
     */
    public function build_log_table_output( $log, $is_wp_list_table = false ) {

        $html = '';
        $colspan = ( $is_wp_list_table ? 10 : 8 );

        // If no results, return a single row
        if ( ! $log || ! is_array( $log ) || count( $log ) == 0 ) {
            $html = '
                    <tr>
                        <td colspan="' . $colspan . '">' . sprintf( __( 'No status updates have been sent to %s.', $this->base->plugin->name ), $this->base->plugin->account ) . '</td>
                    </tr>';

            return $html;
        }

        // Get Post Actions
        $post_actions = $this->base->get_class( 'common' )->get_post_actions();

        // Build Table HTML
        foreach ( $log as $count => $result ) {
            // If output is for a WP_List_Table, add checkbox and Post ID
            if ( $is_wp_list_table ) {
                $checkbox_id = '<th scope="row" class="check-column">
                        <input type="checkbox" name="ids[' . $result['id'] . ']" value="' . $result['id'] . '" />
                    </th>
                    <td>
                        <a href="' . admin_url( 'admin.php?page=' . $this->base->plugin->name . '-log&s=' . $result['post_id'] ) . '">' . 
                            $result['post_id'] . '
                        </a>
                    </td>';
            }

            // Add row to HTML
            $html .= '
            <tr class="' . $result['result'] . ( ( $count % 2 > 0 ) ? ' alternate' : '' ) . '">
                ' . ( $is_wp_list_table ? $checkbox_id : '' ) . '
                <td class="request_sent column-request_sent">' . get_date_from_gmt( $result['request_sent'], get_option( 'date_format' ) . ' H:i:s' ) . '</td>
                <td>' . ( isset( $post_actions[ $result['action'] ] ) ? $post_actions[ $result['action'] ] : '&nbsp;' ) . '</td>
                <td>' . ( empty( $result['profile_name'] ) ? __( 'N/A', $this->base->plugin->name ) : $result['profile_name'] ) . '</td>
                <td>' . ( empty( $result['status_text'] ) ? __( 'N/A', $this->base->plugin->name ) : $result['status_text'] ) . '</td>
                <td>' . ucfirst( $result['result'] ) . '</td>';

            switch ( $result['result'] ) {
                case 'success':
                    $html .= '  <td>' . $result['result_message'] . '</td>
                                <td>' . get_date_from_gmt( $result['status_created_at'], get_option( 'date_format' ) . ' H:i:s' ) . '</td>
                                <td>' . get_date_from_gmt( $result['status_due_at'], get_option( 'date_format' ) . ' H:i:s' ) . '</td>';
                    break;

                default:
                    $html .= '  <td colspan="3">' . $result['result_message'] . '</td>';
                    break;
            }
                
            $html .= '</tr>';
        }

        // Return
        return $html;

    }

}