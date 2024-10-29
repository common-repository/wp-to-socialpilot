<!-- Action -->
<div id="profile-<?php echo $profile_id; ?>-<?php echo $action; ?>" class="postbox">
    <header>
        <h3>
            <?php
            if ( $profile_id == 'default' ) {
                echo 'Defaults: ' . $action_label;
            } else {
                echo sprintf( __( '%s: %s: %s', $this->base->plugin->name ), $profile['formatted_service'], $profile['formatted_username'], $action_label );
            }
            ?>

            <label for="<?php echo $profile_id; ?>_<?php echo $action; ?>_enabled">
                <input type="checkbox" id="<?php echo $profile_id; ?>_<?php echo $action; ?>_enabled" name="<?php echo $this->base->plugin->name; ?>[<?php echo $profile_id; ?>][<?php echo $action; ?>][enabled]" value="1"<?php checked( $this->get_setting( $post_type, '[' . $profile_id . '][' . $action .'][enabled]', 0 ), 1, true ); ?> data-conditional="<?php echo $post_type; ?>-<?php echo $profile_id; ?>-<?php echo $action; ?>-statuses" />
                <?php _e( 'Enabled', $this->base->plugin->name ); ?>
            </label>
        </h3>

        <p class="description">
            <?php
            echo sprintf( 
                __( 'If enabled, any status(es) defined here will be sent to %s when a %s is %s %s', $this->base->plugin->name ),
                $this->base->plugin->account,
                $post_type_object->labels->singular_name,
                $actions_plural[ $action ],
                ( $profile_id == 'default' ? '' : sprintf( __( 'to %s.<br />These override the default settings specified on the Defaults tab.', $this->base->plugin->name ), $profile['formatted_username'] ) ) 
            );
            ?>
        </p>
    </header>

    <div id="<?php echo $post_type; ?>-<?php echo $profile_id; ?>-<?php echo $action; ?>-statuses" class="statuses">
        <?php
        if ( $action == 'repost' ) {
            ?>
            <div class="option">
                <div class="left">
                    <strong><?php _e( 'Frequency', $this->base->plugin->name ); ?></strong>
                </div>
                <div class="right">
                    <?php echo sprintf( __( 'Automatically send the below statuses to %s every ', $this->base->plugin->name ), $this->base->plugin->account ); ?>
                    <input type="number" min="1" max="9999" step="1" name="<?php echo $this->base->plugin->name; ?>[<?php echo $profile_id; ?>][<?php echo $action; ?>][frequency]" value="<?php echo $this->get_setting( $post_type, '[' . $profile_id . '][' . $action . '][frequency]', 7 ); ?>" />
                    <select name="<?php echo $this->base->plugin->name; ?>[<?php echo $profile_id; ?>][<?php echo $action; ?>][frequency_unit]" size="1">
                        <?php
                        foreach ( $repost_frequency_units as $repost_frequency_unit => $label ) {
                            ?>
                            <option value="<?php echo $repost_frequency_unit; ?>"<?php selected( $this->get_setting( $post_type, '[' . $profile_id . '][' . $action .'][frequency_unit]', 'days' ), $repost_frequency_unit ); ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                    <?php echo sprintf( __( 'after the %s last modified date', $this->base->plugin->name ), $post_type_object->labels->singular_name ); ?>
                </div>
            </div>
            <?php
        }
        
        // Fetch Publish / Update / Repost Statuses
        $statuses = $this->get_setting( $post_type, '['. $profile_id .'][' . $action . '][status]', array() );
        
        if ( count ( $statuses ) == 0 || ! $statuses ) {
            // Output blank first status
            $key = 0;
            require( $this->base->plugin->folder . 'vendor/views/settings-post-action-status.php' );
        } else {
            // Iterate through saved statuses
            foreach ( $statuses as $key => $status ) {
                // Load sub view
                require( $this->base->plugin->folder . 'vendor/views/settings-post-action-status.php' );
            }
        }

        // Upgrade Notice
        if ( class_exists( 'WP_To_Buffer' ) || class_exists( 'WP_To_Hootsuite' ) || class_exists( 'WP_To_SocialPilot' ) ) {
            require( $this->base->plugin->folder . 'vendor/views/settings-post-action-status-upgrade.php' );
        } else {
           ?>
            <div class="option last">
                <a href="#" class="button add-status" data-status-index="<?php echo $key; ?>"><?php _e( 'Add Status Update', $this->base->plugin->name ); ?></a>
            </div>
            <?php 
        }
        ?>
    </div>
</div>