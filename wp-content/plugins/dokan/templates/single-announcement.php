<?php
$notice_id = get_query_var( 'single-announcement' );
$notice = array();
$template_notice = Dokan_Template_Notice::init();

if( is_numeric( $notice_id ) ) {
    $notice = $template_notice ->get_single_announcement( $notice_id );
}

?>
<div class="dokan-dashboard-wrap">
    <?php dokan_get_template( 'dashboard-nav.php', array( 'active_menu' => 'announcement' ) ); ?>

    <div class="dokan-dashboard-content dokan-notice-listing">

        <?php do_action( 'dokan_before_single_notice' ); ?>

            <?php if ( $notice ): ?>
                <?php $notice_data = reset( $notice ); ?>
                <?php
                    if( $notice_data->status == 'unread' ) {
                        $template_notice->update_notice_status( $notice_id, 'read' );
                    }
                 ?>
                <article class="dokan-notice-single-notice-area">
                    <header class="dokan-dashboard-header dokan-clearfix">
                        <span class="left-header-content">
                            <h1 class="entry-title"><?php echo $notice_data->post_title; ?></h1>
                        </span>
                    </header>
                    <span class="dokan-single-announcement-date"><i class="fa fa-calendar"></i> <?php echo date('F j, Y ', strtotime( $notice_data->post_date ) ); ?></span>

                    <div class="entry-content">
                        <?php echo wpautop( $notice_data->post_content ); ?>
                    </div>

                    <div class="dokan-announcement-link">
                        <a href="<?php echo dokan_get_navigation_url( 'announcement' ) ?>" class="dokan-btn dokan-btn-theme"><?php _e( 'Back to all Notice', 'dokan' ); ?></a>
                    </div>
                    <!-- Table for linsting  -->

                    <!-- Pagination styles -->
                </article>
            <?php else: ?>
                <article class="dokan-notice-single-notice-area">
                    <header class="dokan-dashboard-header dokan-clearfix">
                        <span class="left-header-content">
                            <h1 class="entry-title"><?php _e( 'Notice', 'dokan' ); ?></h1>
                        </span>
                    </header>
                    <div class="dokan-error">
                        <?php echo sprintf( "<p>%s <a href='%s'>%s</a></p", __( 'No Notice found; ', 'dokan' ), dokan_get_navigation_url('announcement'), __( 'Back to all Notice', 'dokan' ) ) ?>
                    </div>
                </article>
            <?php endif ?>

        <?php do_action( 'dokan_after_listing_notice' ); ?>
    </div><!-- #primary .content-area -->
</div><!-- .dokan-dashboard-wrap -->