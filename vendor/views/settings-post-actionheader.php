<!-- Action Header -->
<div class="postbox">
    <header>
        <h3>
            <?php echo sprintf( __( '%s: %s: Settings', $this->base->plugin->name ), $profile['formatted_service'], $profile['formatted_username'] ); ?>
        </h3>

        <?php
        // If this Profile's timezone doesn't match WordPress' timezone, show a warning
        if ( isset( $profile['timezone'] ) ) {
            $timezones_match = $this->base->get_class( 'validation' )->timezones_match( 
                $profile['timezone'], 
                $profile['formatted_username'], 
                $this->base->get_class( 'api' )->get_timezone_settings_url( $profile['id'] )
            );
            if ( is_wp_error( $timezones_match ) ) {
                ?>
                <div class="notice-inline notice-warning">
                    <p>
                        <?php echo $timezones_match->get_error_message(); ?>
                    </p>
                </div>
                <?php
            }
        }
        ?>
    </header>

    <!-- Account Enabled -->
    <div class="option">
        <label for="<?php echo $profile_id; ?>_enabled">
            <div class="left">
                <strong><?php _e( 'Account Enabled', $this->base->plugin->name ); ?></strong>
            </div>
            <div class="right">
                <input type="checkbox" id="<?php echo $profile_id; ?>_enabled" name="<?php echo $this->base->plugin->name; ?>[<?php echo $profile_id; ?>][enabled]" id="<?php echo $profile_id; ?>_enabled" value="1"<?php checked( $this->get_setting( $post_type, '[' . $profile_id . '][enabled]', 0 ), 1, true ); ?> />
                <p class="description"><?php _e( 'Enabling this social media account means that Posts will be sent to this social media account, if the conditions in the Settings are met.', $this->base->plugin->name ); ?></p>
            </div>
        </label>
    </div>

    <?php
    // Upgrade Notice
    if ( class_exists( 'WP_To_Buffer' ) || class_exists( 'WP_To_Hootsuite' ) ) {
        require( $this->base->plugin->folder . 'vendor/views/settings-post-actionheader-upgrade.php' );
    } else {
        ?>
        <!-- Override Default Settings -->
        <div class="option">
            <label for="<?php echo $profile_id; ?>_override">
                <div class="left">
                    <strong><?php _e( 'Override Defaults', $this->base->plugin->name ); ?></strong>
                </div>
                <div class="right">
                    <?php
                    // Always force this option on for Pinterest profiles, as a subprofile (board) must be chosen
                    $override = ( isset( $profile['subprofiles'] ) ? 1 : $this->get_setting( $post_type, '[' . $profile_id . '][override]', 0 ) );
                    $disabled = ( isset( $profile['subprofiles'] ) ? true : false );
                    ?>
                    <input type="checkbox" id="<?php echo $profile_id; ?>_override" name="<?php echo $this->base->plugin->name; ?>[<?php echo $profile_id; ?>][override]" id="<?php echo $profile_id; ?>_override" value="1"<?php checked( $override, 1, true ); ?> data-conditional="<?php echo $post_type; ?>-<?php echo $profile_id; ?>-actions-panel"<?php echo ( $disabled ? ' disabled="disabled"' : '' ); ?> />
                    <p class="description"><?php _e( 'Check this box to define custom settings when publishing or updating to this social media account. Not checking this box will mean that this social media account uses settings from the "Settings" tab', $this->base->plugin->name ); ?></p>
                </div>
            </label>
        </div>
        <?php
    }
    ?>
</div>