<?php
$dokan_template_settings = Dokan_Template_Settings::init();
$validate                = $dokan_template_settings->validate();

if ( $validate !== false && !is_wp_error( $validate ) ) {
   $dokan_template_settings->insert_settings_info();
}
?>
<div class="dokan-dashboard-wrap">
    <?php dokan_get_template( 'dashboard-nav.php', array( 'active_menu' => 'settings/payment' ) ); ?>

    <div class="dokan-dashboard-content dokan-settings-content">
        <article class="dokan-settings-area">
            <header class="dokan-dashboard-header">
                <h1 class="entry-title">
                    <?php _e( 'Payment Settings', 'dokan' );?>
                    <small>&rarr; <a href="<?php echo dokan_get_store_url( get_current_user_id() ); ?>"><?php _e( 'Visit Store', 'dokan' ); ?></a></small>
                </h1>
            </header><!-- .dokan-dashboard-header -->

            <div class="dokan-page-help">
                <?php _e( 'These are the withdraw methods available for you. Please update your payment informations below to submit withdraw requests and get your store payments seamlessly.', 'dokan' ); ?>
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

            $profile_info   = dokan_get_store_info( $current_user->ID );


            if ( is_wp_error( $validate ) ) {
            }
            ?>

            <div class="dokan-ajax-response">
                <?php echo dokan_get_profile_progressbar(); ?>
            </div>

            <?php 
            /**
             * @since 2.2.2 Insert action before payment settings form
             */
            do_action( 'dokan_payment_settings_before_form', $current_user, $profile_info ); ?>

            <form method="post" id="payment-form"  action="" class="dokan-form-horizontal">

                <?php wp_nonce_field( 'dokan_payment_settings_nonce' ); ?>

                <?php $methods = dokan_withdraw_get_active_methods(); ?>
                <?php foreach ( $methods as $method_key ) {
                    $method = dokan_withdraw_get_method( $method_key );
                    ?>
                    <fieldset classs="payment-field-<?php echo $method_key; ?>">
                        <div class="dokan-form-group">
                            <label class="dokan-w3 dokan-control-label" for="dokan_setting"><?php echo $method['title'] ?></label>
                            <div class="dokan-w6">
                                <?php if ( is_callable( $method['callback'] ) ) {
                                    call_user_func( $method['callback'], $profile_info );
                                } ?>
                            </div> <!-- .dokan-w6 -->
                        </div>
                    </fieldset>
                <?php } ?>

                <?php 
                /**
                 * @since 2.2.2 Insert action on botton of payment settings form
                 */
                do_action( 'dokan_payment_settings_form_bottom', $current_user, $profile_info ); ?>

                <div class="dokan-form-group">

                    <div class="dokan-w4 ajax_prev dokan-text-left" style="margin-left:24%;">
                        <input type="submit" name="dokan_update_payment_settings" class="dokan-btn dokan-btn-danger dokan-btn-theme" value="<?php esc_attr_e( 'Update Settings', 'dokan' ); ?>">
                    </div>
                </div>

            </form>

            <?php 
            /**
             * @since 2.2.2 Insert action after social settings form
             */
            do_action( 'dokan_payment_settings_after_form', $current_user, $profile_info ); ?>

            <!--settings updated content ends-->
        </article>
    </div><!-- .dokan-dashboard-content -->
</div><!-- .dokan-dashboard-wrap -->