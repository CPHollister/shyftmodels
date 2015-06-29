<div class="wrap">
    <h2><?php _e( 'Dokan Tools', 'dokan' ); ?></h2>

    <?php
    $msg = isset( $_GET['msg'] ) ? $_GET['msg'] : '';
    $text = '';

    switch ($msg) {
        case 'page_installed':
            $text = __( 'Pages have been created and installed!', 'dokan' );
            break;

        case 'regenerated':
            $text = __( 'Order sync table has been regenerated!', 'dokan' );
            break;
    }

    if ( $text ) {
        ?>
        <div class="updated">
            <p>
                <?php echo $text; ?>
            </p>
        </div>

    <?php } ?>

    <div class="metabox-holder">
        <div class="postbox">
            <h3><?php _e( 'Page Installation', 'dokan' ); ?></h3>

            <div class="inside">
                <p><?php _e( 'Clicking this button will create required pages for the plugin.', 'dokan' ); ?></p>
                <a class="button button-primary" href="<?php echo wp_nonce_url( add_query_arg( array( 'dokan_action' => 'dokan_install_pages' ), 'admin.php?page=dokan-tools' ), 'dokan-tools-action' ); ?>"><?php _e( 'Install Dokan Pages', 'dokan' ); ?></a>
            </div>
        </div>

        <div class="postbox">
            <h3><?php _e( 'Regenerate Order Sync Table', 'dokan' ); ?></h3>

            <div class="inside">
                <p><?php _e( 'This tool will delete all orders from the Dokan\'s sync table and re-build it.', 'dokan' ); ?></p>

                <a class="button button-primary" href="<?php echo wp_nonce_url( add_query_arg( array( 'dokan_action' => 'regen_sync_table' ), 'admin.php?page=dokan-tools' ), 'dokan-tools-action' ); ?>" onclick="return confirm('Are you sure?');"><?php _e( 'Re-build', 'dokan' ); ?></a>
            </div>
        </div>
    </div>
</div>