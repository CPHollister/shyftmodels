<?php
$dokan_template_reviews = Dokan_Template_reviews::init();
$dokan_template_reviews->handle_status();
?>
<div class="dokan-dashboard-wrap">
    <?php dokan_get_template( 'dashboard-nav.php', array( 'active_menu' => 'reviews' ) ); ?>

    <div class="dokan-dashboard-content dokan-reviews-content">

        <article class="dokan-reviews-area">
            <header class="dokan-dashboard-header">
                <h1 class="entry-title"><?php _e( 'Reviews', 'dokan' ); ?></h1>
            </header><!-- .dokan-dashboard-header -->

            <?php $dokan_template_reviews->reviews_view(); ?>

        </article>

    </div><!-- .dokan-dashboard-content -->
</div><!-- .dokan-dashboard-wrap -->