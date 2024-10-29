<div class="postbox wpzinc-vertical-tabbed-ui">
    <!-- Second level tabs -->
    <ul class="wpzinc-nav-tabs wpzinc-js-tabs" data-panels-container="#settings-container" data-panel=".panel" data-active="wpzinc-nav-tab-vertical-active">
        <li class="wpzinc-nav-tab lock">
            <a href="#authentication" class="wpzinc-nav-tab-vertical-active" data-documentation="<?php echo $this->base->plugin->documentation_url; ?>/authentication-settings/">
                <?php _e( 'Authentication', $this->base->plugin->name ); ?>
            </a>
        </li>
        <li class="wpzinc-nav-tab file-text">
            <a href="#log-settings" data-documentation="<?php echo $this->base->plugin->documentation_url; ?>/log-settings/">
                <?php _e( 'Log Settings', $this->base->plugin->name ); ?>
            </a>
        </li>
        <li class="wpzinc-nav-tab default">
            <a href="#general-settings" data-documentation="<?php echo $this->base->plugin->documentation_url; ?>/general-settings/">
                <?php _e( 'General Settings', $this->base->plugin->name ); ?>
            </a>
        </li>
        <li class="wpzinc-nav-tab arrow-right-circle">
            <a href="#repost-settings" data-documentation="<?php echo $this->base->plugin->documentation_url; ?>/repost-settings/">
                <?php _e( 'Repost Settings', $this->base->plugin->name ); ?>
            </a>
        </li>
        <?php
        // Only display if we've auth'd and have profiles
        if ( ! empty ( $access_token ) ) {
            ?>
            <li class="wpzinc-nav-tab users">
                <a href="#user-access" data-documentation="<?php echo $this->base->plugin->documentation_url; ?>/user-access-settings/">
                    <?php _e( 'User Access', $this->base->plugin->name ); ?>
                </a>
            </li>
            <?php
        }
        ?>
        <li class="wpzinc-nav-tab tag">
            <a href="#custom-tags" data-documentation="<?php echo $this->base->plugin->documentation_url; ?>/custom-tags-settings/">
                <?php _e( 'Custom Tags', $this->base->plugin->name ); ?>
            </a>
        </li>
    </ul>

    <!-- Content -->
    <div id="settings-container" class="wpzinc-nav-tabs-content no-padding">
        <!-- Authentication -->
        <div id="authentication" class="panel">
            <div class="postbox">
                <?php
                $access_token = $this->get_setting( '', 'access_token' );
                ?>
                <header>
                    <h3><?php _e( 'Authentication', $this->base->plugin->name ); ?></h3>

                    <p class="description">
                        <?php echo sprintf( __( 'Authentication allows %s to post to %s', $this->base->plugin->name ), $this->base->plugin->displayName, $this->base->plugin->account ); ?>
                    </p>
                </header>
                
                <?php
                if ( ! empty ( $access_token ) ) {
                    // Already authenticated
                    ?>
                    <div class="option">
                        <div class="full">
                            <?php echo sprintf( __( 'Thanks - you\'ve authorized the plugin to post updates to your %s account.', $this->base->plugin->name ), $this->base->plugin->account ); ?>
                        </div>
                    </div>
                    <div class="option">
                        <div class="full">
                            <a href="admin.php?page=<?php echo $this->base->plugin->name; ?>-settings&amp;<?php echo $this->base->plugin->name; ?>-disconnect=1" class="button button-red">
                                <?php _e( 'Deauthorize Plugin', $this->base->plugin->name ); ?>
                            </a>
                        </div>
                    </div>
                    <?php
                } else {
                    // Need to authenticate
                    ?>
                    <div class="option">
                        <div class="full">
                            <p class="description">
                                <?php echo sprintf( __( 'To allow this Plugin to post updates to your %s account, please authorize it by clicking the button below.', $this->base->plugin->name ), $this->base->plugin->account ); ?>
                            </p>
                        </div>
                    </div>
                    <div class="option">
                        <div class="full">
                            <?php
                            if ( isset( $oauth_url ) ) {
                                ?>
                                <a href="<?php echo $oauth_url; ?>" class="button button-primary">
                                    <?php _e( 'Authorize Plugin', $this->base->plugin->name ); ?>
                                </a>
                                <?php
                            } else {
                                echo sprintf( __( 'We\'re unable to fetch the oAuth URL needed to begin authorization with %s.  Please <a href="%s" target="_blank">contact us for support</a>.', $this->base->plugin->name ), $this->base->plugin->account, $this->base->plugin->support_url );
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>   
        </div>

        <!-- General Settings -->
        <div id="general-settings" class="panel">
            <div class="postbox">
                <header>
                    <h3><?php _e( 'General Settings', $this->base->plugin->name ); ?></h3>
                    <p class="description">
                        <?php _e( 'Provides options for logging, Post default level settings and whether to use WordPress Cron when publishing or updating Posts.', $this->base->plugin->name ); ?>
                    </p>
                </header>

                <div class="option highlight">
                    <div class="full">
                        <h4><?php echo sprintf( __( 'Process %s Statuses Faster', $this->base->plugin->name ), $this->base->plugin->account ); ?></h4>

                        <p>
                            <?php echo sprintf( __( '%s Pro provides options to disable URL shortening, use WordPress\' CRON to schedule status messages and more.', $this->base->plugin->name ), $this->base->plugin->displayName ); ?>
                        </p>
                        
                        <a href="<?php echo $this->base->dashboard->get_upgrade_url( 'settings_inline_upgrade' ); ?>" class="button button-primary" target="_blank"><?php _e( 'Upgrade', $this->base->plugin->name ); ?></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Settings -->
        <div id="log-settings" class="panel">
            <div class="postbox">
                <header>
                    <h3><?php _e( 'Log Settings', $this->base->plugin->name ); ?></h3>
                    <p class="description">
                        <?php _e( 'Provides options to enable logging, display logs on Posts and how long to keep logs for.', $this->base->plugin->name ); ?>
                    </p>
                </header>

                <div class="option">
                    <div class="left">
                        <strong><?php _e( 'Enable Logging?', $this->base->plugin->name ); ?></strong>
                    </div>
                    <div class="right">
                        <input type="checkbox" name="log[enabled]" value="1" <?php checked( $this->get_setting( 'log', '[enabled]' ), 1 ); ?> data-conditional="enable_logging" />
                    </div>
                    <div class="full">
                        <p class="description">
                            <?php 
                            echo sprintf( 
                                __( 'If enabled, the <a href="%s">Plugin Logs</a> will detail status(es) sent to %s, 
                                including any errors or reasons why no status(es) were sent.', $this->base->plugin->name ), 
                                admin_url( 'admin.php?page=' . $this->base->plugin->name . '-logs' ),
                                $this->base->plugin->account
                            ); ?>
                        </p>
                    </div>
                </div>

                <div id="enable_logging">
                    <div class="option">
                        <div class="left">
                            <strong><?php _e( 'Display on Posts?', $this->base->plugin->name ); ?></strong>
                        </div>
                        <div class="right">
                            <input type="checkbox" name="log[display_on_posts]" value="1" <?php checked( $this->get_setting( 'log', '[display_on_posts]' ), 1 ); ?> />
               
                            <p class="description">
                                <?php 
                                echo sprintf( 
                                    __( 'If enabled, a Log will be displayed when editing a Post.  Logs are always available through the 
                                        <a href="%s">Plugin Logs</a> screen.', $this->base->plugin->name ),
                                    admin_url( 'admin.php?page=' . $this->base->plugin->name . '-logs' )
                                );
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="option">
                        <div class="left">
                            <strong><?php _e( 'Preserve Logs', $this->base->plugin->name ); ?></strong>
                        </div>
                        <div class="right">
                            <input type="number" name="log[preserve_days]" value="<?php echo $this->get_setting( 'log', '[preserve_days]' ); ?>" min="0" max="9999" step="1" />
                            <?php _e( 'days', $this->base->plugin->name ); ?>
                       
                            <p class="description">
                                <?php 
                                _e( 'The number of days to preserve logs for.  Zero means logs are kept indefinitely.', $this->base->plugin->name );
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Repost Settings -->
        <div id="repost-settings" class="panel">
            <div class="postbox">
                <header>
                    <h3><?php _e( 'Repost Settings', $this->base->plugin->name ); ?></h3>
                    <p class="description">
                        <?php _e( 'Provides options for when to run the WordPress Repost Cron Event on this WordPress installation.', $this->base->plugin->name ); ?><br />
                        <?php
                        echo sprintf( 
                            __( 'When Post(s) are scheduled on %s will depend on the <a href="%s/repost-settings" target="_blank">Repost Status Settings</a>.', $this->base->plugin->name ),
                            $this->base->plugin->account,
                            $this->base->plugin->documentation_url
                        );
                        ?>
                    </p>
                </header>

                <div class="option highlight">
                    <div class="full">
                        <h4><?php _e( 'Revive Old Posts with Repost', $this->base->plugin->name ); ?></h4>

                        <p>
                            <?php echo sprintf( __( 'Automatically schedule old Posts to %s with %s Pro.', $this->base->plugin->name ), $this->base->plugin->displayName, $this->base->plugin->displayName ); ?>
                        </p>
                        
                        <a href="<?php echo $this->base->dashboard->get_upgrade_url( 'settings_inline_upgrade' ); ?>" class="button button-primary" target="_blank"><?php _e( 'Upgrade', $this->base->plugin->name ); ?></a>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Only display if we've auth'd and have profiles
        if ( ! empty ( $access_token ) ) {
            // User Access
            ?>
            <!-- User Access -->
            <div id="user-access" class="panel">
                <div class="postbox">
                    <header>
                        <h3><?php _e( 'User Access', $this->base->plugin->name ); ?></h3>
                        <p class="description">
                            <?php _e( 'Optionally define which of your Post Types and connected social media account(s) should be available for configuration based on various WordPress User Roles.', $this->base->plugin->name ); ?>
                        </p>
                    </header>

                    <div class="option highlight">
                        <div class="full">
                            <h4><?php echo sprintf( __( 'Limit Post Types and Social Profiles by WordPress User Role', $this->base->plugin->name ), $this->base->plugin->account ); ?></h4>

                            <p>
                                <?php echo sprintf( __( '%s Pro provides options to limit which Post Types to show in the Settings screens, as well as prevent access to specific social media profiles linked to your Buffer account, on a per-WordPress Role basis.', $this->base->plugin->name ), $this->base->plugin->displayName ); ?>
                            </p>
                            
                            <a href="<?php echo $this->base->dashboard->get_upgrade_url( 'settings_inline_upgrade' ); ?>" class="button button-primary" target="_blank"><?php _e( 'Upgrade', $this->base->plugin->name ); ?></a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>

        <!-- Custom Tags -->
        <div id="custom-tags" class="panel">
            <div class="postbox">
                <header>
                    <h3><?php _e( 'Custom Tags', $this->base->plugin->name ); ?></h3>
                    <p class="description">
                        <?php _e( 'If your site uses Custom Fields, ACF or similar, you can specify additional tags to be added to the "Insert Tag" dropdown for each of your Post Types.  These can then be used by Users, instead of having to remember the template tag text to use.', $this->base->plugin->name ); ?>
                    </p>
                </header>

                <div class="option highlight">
                    <div class="full">
                        <h4><?php echo sprintf( __( 'Need to define your own Tags to use in status messages?', $this->base->plugin->name ), $this->base->plugin->account ); ?></h4>

                        <p>
                            <?php echo sprintf( __( '%s Pro provides options to define Custom Field / ACF Tags, which will then populate with Post data when used in status messages.  Tags also appear in the Insert Tags dropdown.', $this->base->plugin->name ), $this->base->plugin->displayName ); ?>
                        </p>
                        
                        <a href="<?php echo $this->base->dashboard->get_upgrade_url( 'settings_inline_upgrade' ); ?>" class="button button-primary" target="_blank"><?php _e( 'Upgrade', $this->base->plugin->name ); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>