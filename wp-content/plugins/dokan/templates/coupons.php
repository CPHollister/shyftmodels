<?php
$dokan_template_coupons = Dokan_Template_Coupons::init();
$is_edit_page           = isset( $_GET['view'] ) && $_GET['view'] == 'add_coupons';
?>

<div class="dokan-dashboard-wrap">
    <?php dokan_get_template( 'dashboard-nav.php', array( 'active_menu' => 'coupon' ) ); ?>

    <div class="dokan-dashboard-content dokan-coupon-content">

        <article class="dashboard-coupons-area">
            <header class="dokan-dashboard-header dokan-clearfix">
                <span class="left-header-content dokan-left">
                    <h1 class="entry-title">
                        <?php _e( 'Coupon', 'dokan' ); ?>
                    <?php if ( $is_edit_page ) {
                        printf( '<small> - %s</small>', __( 'Edit Coupon', 'dokan' ) );
                    } ?>
                    </h1>
                </span>

                <?php if ( !$is_edit_page ) { ?>
                    <span class="left-header-content dokan-right">
                        <a href="<?php echo add_query_arg( array( 'view' => 'add_coupons'), dokan_get_navigation_url( 'coupons' ) ); ?>" class="dokan-btn dokan-btn-theme dokan-right"><i class="fa fa-gift">&nbsp;</i> <?php _e( 'Add new Coupon', 'dokan' ); ?></a>
                    </span>
                <?php } ?>
            </header><!-- .entry-header -->

            <?php
            if ( !dokan_is_seller_enabled( get_current_user_id() ) ) {
                dokan_seller_not_enabled_notice();
            } else {
                ?>

                <?php $dokan_template_coupons->list_user_coupons(); ?>

                <?php
                if ( is_wp_error( Dokan_Template_Shortcodes::$validated )) {
                    $messages = Dokan_Template_Shortcodes::$validated->get_error_messages();

                    foreach ($messages as $message) {
                        ?>
                        <div class="dokan-alert dokan-alert-danger" style="width: 40%; margin-left: 25%;">
                            <button type="button" class="dokan-close" data-dismiss="alert">&times;</button>
                            <strong><?php _e( $message,'dokan'); ?></strong>
                        </div>
                        <?php
                    }
                }
                ?>
                <?php $dokan_template_coupons->add_coupons_form( Dokan_Template_Shortcodes::$validated ); ?>

            <?php } ?>

        </article>
    </div><!-- .dokan-dashboard-content -->
</div><!-- .dokan-dashboard-wrap -->