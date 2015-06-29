<?php
$dokan_template_settings = Dokan_Template_Settings::init();
$validate                = $dokan_template_settings->profile_validate();

if ( $validate !== false && !is_wp_error( $validate ) ) {
   $dokan_template_settings->insert_settings_info();
}
?>

<div class="dokan-dashboard-wrap">
    <?php dokan_get_template( 'dashboard-nav.php', array( 'active_menu' => 'settings/social' ) ); ?>

    <div class="dokan-dashboard-content dokan-settings-content">
        <article class="dokan-settings-area">
            <header class="dokan-dashboard-header">
                <h1 class="entry-title">
                    <?php _e( 'Social Profiles', 'dokan' );?>
                    <small>&rarr; <a href="<?php echo dokan_get_store_url( get_current_user_id() ); ?>"><?php _e( 'Visit Store', 'dokan' ); ?></a></small>
                </h1>
            </header><!-- .dokan-dashboard-header -->

            <div class="dokan-page-help">
                <?php _e( 'Social profiles help you to gain more trust. Consider adding your social profile links for better user interaction.', 'dokan' ); ?>
            </div>

            <?php if ( is_wp_error( $validate ) ) {
                $messages = $validate->get_error_messages();

                foreach( $messages as $message ) {
                    ?>
                    <div class="dokan-alert dokan-alert-danger" style="width: 40%; margin-left: 25%;">
                        <button type="button" class="dokan-close" data-dismiss="alert">&times;</button>
                        <strong><?php echo $message; ?></strong>
                    </div>

                    <?php
                }
            } ?>

            <?php //$dokan_template_settings->setting_field($validate); ?>

            <!--settings updated content-->
            <?php
            global $current_user;

            if ( isset( $_GET['message'] ) ) {
                ?>
                <div class="dokan-alert dokan-alert-success">
                    <button type="button" class="dokan-close" data-dismiss="alert">&times;</button>
                    <strong><?php _e( 'Your profile has been updated successfully!', 'dokan' ); ?></strong>
                </div>
            <?php
            }

            $profile_info  = dokan_get_store_info( $current_user->ID );
            $social_fields = dokan_get_social_profile_fields();
            ?>

            <div class="dokan-ajax-response">
                <?php echo dokan_get_profile_progressbar(); ?>
            </div>

            <?php 
            /**
             * @since 2.2.2 Insert action before social settings form
             */
            do_action( 'dokan_profile_settings_before_form', $current_user, $profile_info ); ?>

            <form method="post" id="profile-form"  action="" class="dokan-form-horizontal"><?php ///settings-form ?>

                <?php wp_nonce_field( 'dokan_profile_settings_nonce' ); ?>

                <?php foreach( $social_fields as $key => $field ) { ?>
                    <div class="dokan-form-group">
                        <label class="dokan-w3 dokan-control-label"><?php echo $field['title']; ?></label>

                        <div class="dokan-w5">
                            <div class="dokan-input-group dokan-form-group">
                                <span class="dokan-input-group-addon"><i class="fa fa-<?php echo isset( $field['icon'] ) ? $field['icon'] : ''; ?>"></i></span>
                                <input id="settings[social][<?php echo $key; ?>]" value="<?php echo isset( $profile_info['social'][$key] ) ? esc_url( $profile_info['social'][$key] ) : ''; ?>" name="settings[social][<?php echo $key; ?>]" class="dokan-form-control" placeholder="http://" type="url">
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <?php 
                /**
                 * @since 2.2.2 Insert action on bottom social settings form
                 */
                do_action( 'dokan_profile_settings_form_bottom', $current_user, $profile_info ); ?>

                <div class="dokan-form-group">
                    <div class="dokan-w4 ajax_prev dokan-text-left" style="margin-left:24%;">
                        <input type="submit" name="dokan_update_profile_settings" class="dokan-btn dokan-btn-danger dokan-btn-theme" value="<?php esc_attr_e( 'Update Settings', 'dokan' ); ?>">
                    </div>
                </div>

            </form>

            <?php 
            /**
             * @since 2.2.2 Insert action after social settings form
             */
            do_action( 'dokan_profile_settings_after_form', $current_user, $profile_info ); ?>
            <!--settings updated content end-->

        </article>
    </div><!-- .dokan-dashboard-content -->
</div><!-- .dokan-dashboard-wrap -->