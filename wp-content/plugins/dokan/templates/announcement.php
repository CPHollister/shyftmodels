<?php
    $announcement = Dokan_Template_Notice::init();

?>

<div class="dokan-dashboard-wrap">
    <?php dokan_get_template( 'dashboard-nav.php', array( 'active_menu' => 'announcement' ) ); ?>
    <?php //var_dump( $urls = dokan_get_dashboard_nav() ); ?>

    <div class="dokan-dashboard-content dokan-notice-listing">

        <?php do_action( 'dokan_before_listing_notice' ); ?>

            <article class="dokan-notice-listing-area">
                <header class="dokan-dashboard-header dokan-clearfix">
                    <span class="left-header-content">
                        <h1 class="entry-title"><?php _e( 'Announcement', 'dokan' ); ?></h1>
                    </span>
                </header>

                <div class="notice-listing-top dokan-clearfix">
                    <!-- Listing filters -->
                </div>

                <?php // show errors ?>

                <?php $announcement->show_announcement_template(); ?>
                <!-- Table for linsting  -->

                <!-- Pagination styles -->
            </article>

        <?php do_action( 'dokan_after_listing_notice' ); ?>
    </div><!-- #primary .content-area -->
</div><!-- .dokan-dashboard-wrap -->