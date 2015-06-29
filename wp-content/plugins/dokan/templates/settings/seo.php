<?php
$seo = Dokan_Store_Seo::init();
?>

<div class="dokan-dashboard-wrap">
    <?php dokan_get_template( 'dashboard-nav.php', array( 'active_menu' => 'settings/seo' ) ); ?>

    <div class="dokan-dashboard-content dokan-settings-content dokan-store-seo-wrapper">
        <article class="dokan-settings-area">
            <header class="dokan-dashboard-header">
                <h1 class="entry-title">
                    <?php _e( 'Store SEO', 'dokan' ); ?>
                    <small>&rarr; <a href="<?php echo dokan_get_store_url( get_current_user_id() ); ?>"><?php _e( 'Visit Store', 'dokan' ); ?></a></small>
                </h1>
            </header><!-- .dokan-dashboard-header -->
            
            <?php $seo->frontend_meta_form(); ?>

        </article>
    </div><!-- .dokan-dashboard-content -->
</div><!-- .dokan-dashboard-wrap -->