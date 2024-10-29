<div class="option">
    <table class="widefat wp-to-social-log">
        <thead>
            <tr>
                <th><?php _e( 'Request Sent', $this->base->plugin->name ); ?></th>
                <th><?php _e( 'Action', $this->base->plugin->name ); ?></th>
                <th><?php _e( 'Profile', $this->base->plugin->name ); ?></th>
                <th><?php _e( 'Status Text', $this->base->plugin->name ); ?></th>
                <th><?php _e( 'Result', $this->base->plugin->name ); ?></th>
                <th><?php _e( 'Response', $this->base->plugin->name ); ?></th>
                <th><?php echo sprintf( __( '%s: Status Created At', $this->base->plugin->name ), $this->base->plugin->account ); ?></th>
                <th><?php echo sprintf( __( '%s: Status Scheduled For', $this->base->plugin->name ), $this->base->plugin->account ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            echo $this->base->get_class( 'log' )->build_log_table_output( $log );
            ?>
        </tbody>
    </table>
</div>

<?php
if ( is_array( $log ) ) {
    ?>
    <div class="option">
        <a href="post.php?post=<?php echo $post->ID; ?>&action=edit&<?php echo $this->base->plugin->name; ?>-export-log=1" class="button">
            <?php _e( 'Export Log', $this->base->plugin->name ); ?>
        </a>
        <a href="post.php?post=<?php echo $post->ID; ?>&action=edit&<?php echo $this->base->plugin->name; ?>-clear-log=1" class="button clear-log" data-action="<?php echo $this->base->plugin->filter_name; ?>_clear_log" data-target="#<?php echo $this->base->plugin->name; ?>-log">
            <?php _e( 'Clear Log', $this->base->plugin->name ); ?>
        </a>
    </div>
    <?php
}