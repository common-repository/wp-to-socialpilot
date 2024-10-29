<div class="option sortable<?php echo ( $key == 0 ? ' first' : '' ); ?>" data-status-index="<?php echo $key; ?>">
    <!-- Count + Delete -->
    <div class="left number">
        <a href="#" class="count" title="<?php _e( 'Drag status to reorder', $this->base->plugin->name ); ?>">#<?php echo ( $key + 1 ); ?></a>
        <a href="#" class="dashicons dashicons-trash delete" title="<?php _e( 'Delete Condition', $this->base->plugin->name ); ?>"></a>
    </div>

    <!-- Status -->
    <div class="right status">
        <!-- Tags and Feat. Image -->
        <div class="full">
            <?php
            // Get Featured Image Options
            $featured_image_options = $this->base->get_class( 'common' )->get_featured_image_options( 
                ( isset( $profile['service'] ) ? $profile['service'] : false ),
                $post_type
            );

            /**
             * If we're on a Pinterest profile, the user needs to specify the board to use
             * Buffer: The API gives us a list of subprofiles, comprising of boards
             * Hootsuite: The API gives us nothing, so we ask for a board URL, which we'll convert to a board ID later
             * For both, force featured images on, as they're required
             */
            $service = ( isset( $profile ) ? $profile['service'] : false );
            switch ( $service ) {
                case 'pinterest':
                    // Display list of subprofiles (boards), if available
                    if ( isset( $profile['subprofiles'] ) ) {
                        ?>
                        <!-- Subprofile -->                    
                        <select name="<?php echo $this->base->plugin->name; ?>[<?php echo $profile_id; ?>][<?php echo $action; ?>][status][sub_profile][]" size="1" class="right">
                            <option value="" class="disabled"><?php _e( 'Choose a Pinterest Board', $this->base->plugin->name ); ?></option>
                            <?php
                            foreach ( $profile['subprofiles'] as $sub_profile ) {
                                ?>
                                <option value="<?php echo $sub_profile['id']; ?>"<?php selected( $this->get_setting( $post_type, '[' . $profile_id . '][' . $action . '][status][' . $key . '][sub_profile]', '' ), $sub_profile['id'] ); ?>>- <?php echo $sub_profile['name']; ?></option>
                                <?php
                            }
                            ?>
                        </select> 
                        <?php
                    } else {
                        // Display URL field to enter 
                        ?>
                        <input type="url" name="<?php echo $this->base->plugin->name; ?>[<?php echo $profile_id; ?>][<?php echo $action; ?>][status][sub_profile][]" placeholder="<?php _e( 'Pinterest Board URL', $this->base->plugin->name ); ?>" value="<?php echo $this->get_setting( $post_type, '[' . $profile_id . '][' . $action . '][status][' . $key . '][sub_profile]', '' ); ?>" class="right">
                        <?php
                    }
                    break;

                default:
                    ?>
                    <!-- Use Feat. Image -->
                    <select name="<?php echo $this->base->plugin->name; ?>[<?php echo $profile_id; ?>][<?php echo $action; ?>][status][image][]" size="1" class="right"<?php echo ( ( isset( $profile['service'] ) && $profile['service'] == 'instagram' ) ? ' disabled="disabled"' : '' ); ?>>
                        <?php
                        foreach ( $featured_image_options as $value => $label ) {
                            ?>
                            <option value="<?php echo $value; ?>"<?php selected( $this->get_setting( $post_type, '[' . $profile_id . '][' . $action . '][status][' . $key . '][image]', 0 ), $value, true ); ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                    <?php
                    break;
            }
            ?>

            <!-- Tags -->
            <select size="1" class="left tags">
                <option value=""><?php _e( '--- Insert Tag ---', $this->base->plugin->name ); ?></option>
                <?php
                foreach ( $tags as $tag_group => $tag_group_tags ) {
                    ?>
                    <optgroup label="<?php echo $tag_group; ?>">
                        <?php
                        foreach ( $tag_group_tags as $tag => $tag_attributes ) {
                            // If the tag attributes is an array, this is a more complex tag
                            // that requires user input
                            if ( is_array( $tag_attributes ) ) {
                                ?>
                                <option value="<?php echo $tag; ?>" data-question="<?php echo $tag_attributes['question']; ?>" data-default-value="<?php echo $tag_attributes['default_value']; ?>" data-replace="<?php echo $tag_attributes['replace']; ?>"><?php echo $tag_attributes['label']; ?></option>
                                <?php
                            } else {
                                ?>
                                <option value="<?php echo $tag; ?>"><?php echo $tag_attributes; ?></option>
                                <?php
                            }
                        }
                        ?>
                    </optgroup>
                    <?php
                }
                ?>
            </select>
        </div>

        <!-- Status Message -->
        <div class="full">
            <textarea name="<?php echo $this->base->plugin->name; ?>[<?php echo $profile_id; ?>][<?php echo $action; ?>][status][message][]" rows="3" class="widefat autosize-js"><?php echo $this->get_setting( $post_type, '[' . $profile_id . '][' . $action . '][status][' . $key . '][message]', $this->base->plugin->publish_default_status ); ?></textarea>
        </div>

        <!-- Scheduling -->
        <div class="full">
            <select name="<?php echo $this->base->plugin->name; ?>[<?php echo $profile_id; ?>][<?php echo $action; ?>][status][schedule][]" size="1" data-conditional="schedule" data-conditional-value="custom,custom_field">
                <?php
                foreach ( $schedule as $schedule_option => $label ) {
                    ?>
                    <option value="<?php echo $schedule_option; ?>"<?php selected( $this->get_setting( $post_type, '[' . $profile_id . '][' . $action . '][status][' . $key . '][schedule]', '' ), $schedule_option ); ?>><?php echo $label; ?></option>
                    <?php
                }
                ?>
            </select>
        </div>
    </div>
</div>