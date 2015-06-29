<?php
/**
 * Dokan Upgrade class
 *
 * Performas upgrade dokan latest version
 *
 * @since 2.1
 *
 * @package Dokan
 */
class Dokan_Upgrade {

    /**
     * Constructor loader function
     *
     * Load autometically when class instantiate.
     *
     * @since 1.0
     */
    function __construct() {
        add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
        add_action( 'admin_init', array( $this, 'upgrade_action_perform' ) );
    }

    /**
     * Upgrade Notice display function
     *
     * @since 1.0
     *
     * @return void
     */
    public function upgrade_notice () {
        $installed_version = get_option( 'dokan_theme_version' );

        // may be it's the first install
        if ( ! $installed_version ) {
            return false;
        }

        if ( version_compare( $installed_version, DOKAN_PLUGIN_VERSION , '<' ) ) {
            ?>
                <div class="notice notice-warning">
                    <p><?php _e( '<strong>Dokan Data Update Required</strong> &#8211; Please click the button below to update to the latest version.', 'dokan' ) ?></p>

                    <form action="" method="post" style="padding-bottom: 10px;">
                        <input type="submit" class="button button-primary" name="dokan_upgrade_plugin" value="<?php esc_attr_e( 'Run the Updater', 'dokan' ); ?>">
                        <?php wp_nonce_field( 'dokan_upgrade_action', 'dokan_upgrade_action_nonce' ); ?>
                    </form>
                </div>
            <?php
        }
    }

    /**
     * Upgrade action
     *
     * @since 1.0
     *
     * @return void
     */
    function upgrade_action_perform() {

        if ( !isset( $_POST['dokan_upgrade_action_nonce'] ) ) {
            return;
        }

        if ( !wp_verify_nonce( $_POST['dokan_upgrade_action_nonce'], 'dokan_upgrade_action' ) ) {
            return;
        }

        if ( !isset( $_POST['dokan_upgrade_plugin'] ) ) {
            return;
        }

        $dokan_installer = new Dokan_Installer();
        $dokan_installer->do_upgrades();
        // call upgrade class
        $redirect_url = $_SERVER['HTTP_REFERER'];
        wp_safe_redirect( $redirect_url );

    }

}