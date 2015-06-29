<?php

if ( !class_exists( 'WeDevs_Settings_API' ) ) {
    require_once DOKAN_LIB_DIR . '/class.settings-api.php';
}

/**
 * WordPress settings API demo class
 *
 * @author Tareq Hasan
 */
class Dokan_Admin_Settings {

    private $settings_api;

    function __construct() {
        $this->settings_api = new WeDevs_Settings_API();

        add_action( 'admin_init', array($this, 'do_updates') );
        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_init', array($this, 'tools_page_handler') );

        add_action( 'admin_menu', array($this, 'admin_menu') );
        add_action( 'admin_notices', array($this, 'update_notice' ) );
    }

    /**
     * Dashboard scripts and styles
     *
     * @return void
     */
    function dashboard_script() {
        wp_enqueue_style( 'dokan-admin-dash', DOKAN_PLUGIN_ASSEST . '/css/admin.css' );

        $this->report_scripts();
    }

    /**
     * Reporting scripts
     *
     * @return void
     */
    function report_scripts() {
        wp_enqueue_style( 'dokan-admin-report', DOKAN_PLUGIN_ASSEST . '/css/admin.css' );
        wp_enqueue_style( 'jquery-ui' );
        wp_enqueue_style( 'dokan-chosen-style' );

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-flot' );
        wp_enqueue_script( 'jquery-chart' );
        wp_enqueue_script( 'chosen' );
    }

    /**
     * Seller announcement scripts
     *
     * @since 2.1
     *
     * @return void
     */
    function announcement_scripts() {
        global $post_type;

        if ( 'dokan_announcement' == $post_type ) {
            wp_enqueue_style( 'dokan-chosen-style' );
            wp_enqueue_script( 'chosen' );
        }
    }

    function admin_init() {
        Dokan_Template_Withdraw::init()->bulk_action_handler();

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        $menu_position = apply_filters( 'doakn_menu_position', 17 );
        $capability    = apply_filters( 'doakn_menu_capability', 'manage_options' );
        $withdraw      = dokan_get_withdraw_count();
        $withdraw_text = __( 'Withdraw', 'dokan' );

        if ( $withdraw['pending'] ) {
            $withdraw_text = sprintf( __( 'Withdraw %s', 'dokan' ), '<span class="awaiting-mod count-1"><span class="pending-count">' . $withdraw['pending'] . '</span></span>');
        }

        $dashboard = add_menu_page( __( 'Dokan', 'dokan' ), __( 'Dokan', 'dokan' ), $capability, 'dokan', array($this, 'dashboard'), 'dashicons-vault', $menu_position );
        add_submenu_page( 'dokan', __( 'Dokan Dashboard', 'dokan' ), __( 'Dashboard', 'dokan' ), $capability, 'dokan', array($this, 'dashboard') );
        add_submenu_page( 'dokan', __( 'Withdraw', 'dokan' ), $withdraw_text, $capability, 'dokan-withdraw', array($this, 'withdraw_page') );
        add_submenu_page( 'dokan', __( 'Sellers Listing', 'dokan' ), __( 'All Sellers', 'dokan' ), $capability, 'dokan-sellers', array($this, 'seller_listing') );
        $report = add_submenu_page( 'dokan', __( 'Earning Reports', 'dokan' ), __( 'Earning Reports', 'dokan' ), $capability, 'dokan-reports', array($this, 'report_page') );
        $announcement = add_submenu_page( 'dokan', __( 'Announcement', 'dokan' ), __( 'Announcement', 'dokan' ), $capability, 'edit.php?post_type=dokan_announcement' );

        do_action( 'dokan_admin_menu' );

        add_submenu_page( 'dokan', __( 'Tools', 'dokan' ), __( 'Tools', 'dokan' ), $capability, 'dokan-tools', array($this, 'tools_page') );
        add_submenu_page( 'dokan', __( 'Settings', 'dokan' ), __( 'Settings', 'dokan' ), $capability, 'dokan-settings', array($this, 'settings_page') );
        add_submenu_page( 'dokan', __( 'Add Ons', 'dokan' ), __( 'Add-ons', 'dokan' ), $capability, 'dokan-addons', array($this, 'addon_page') );

        add_action( $dashboard, array($this, 'dashboard_script' ) );
        add_action( $report, array($this, 'report_scripts' ) );
        // add_action( $announcement, array($this, 'announcement_scripts' ) );
        add_action( 'admin_print_scripts-post-new.php', array( $this, 'announcement_scripts' ), 11 );
        add_action( 'admin_print_scripts-post.php', array( $this, 'announcement_scripts' ), 11 );

    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'dokan_general',
                'title' => __( 'General', 'dokan' )
            ),
            array(
                'id'    => 'dokan_selling',
                'title' => __( 'Selling Options', 'dokan' )
            ),
            array(
                'id'    => 'dokan_pages',
                'title' => __( 'Page Settings', 'dokan' )
            )
        );
        return apply_filters( 'dokan_settings_sections', $sections );
    }

    function get_post_type( $post_type ) {
        $pages_array = array( '-1' => __( '- select -', 'dokan' ) );
        $pages = get_posts( array('post_type' => $post_type, 'numberposts' => -1) );

        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_array[$page->ID] = $page->post_title;
            }
        }

        return $pages_array;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $pages_array = $this->get_post_type( 'page' );
        $slider_array = $this->get_post_type( 'dokan_slider' );

        $settings_fields = array(
            'dokan_general' => array(
                'admin_access' => array(
                    'name'    => 'admin_access',
                    'label'   => __( 'Admin area access', 'dokan' ),
                    'desc'    => __( 'Disable sellers and customers from accessing wp-admin area', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
                'store_map' => array(
                    'name'    => 'store_map',
                    'label'   => __( 'Show Map on Store Page', 'dokan' ),
                    'desc'    => __( 'Enable showing Store location map on store left sidebar', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
                'contact_seller' => array(
                    'name'    => 'contact_seller',
                    'label'   => __( 'Show Contact Form on Store Page', 'dokan' ),
                    'desc'    => __( 'Enable showing contact seller form on store left sidebar', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
                'enable_theme_store_sidebar' => array(
                    'name'    => 'enable_theme_store_sidebar',
                    'label'   => __( 'Enable Store Sidebar From Theme', 'dokan' ),
                    'desc'    => __( 'Enable showing Store Sidebar From Your Theme.', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'off'
                ),
                'product_add_mail' => array(
                    'name'    => 'product_add_mail',
                    'label'   => __( 'Product Mail Notification', 'dokan' ),
                    'desc'    => __( 'Email notification on new product submission', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
                'store_seo' => array(
                    'name'    => 'store_seo',
                    'label'   => __( 'Enable Store SEO', 'dokan' ),
                    'desc'    => __( 'Sellers can manage their Store page SEO', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
            ),
            'dokan_selling' => array(
                'seller_enable_terms_and_conditions' => array(
                    'name'    => 'seller_enable_terms_and_conditions',
                    'label'   => __( 'Terms and Conditions', 'dokan' ),
                    'desc'    => __( 'Enable terms and conditions for seller store', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'off'
                 ),
                'new_seller_enable_selling' => array(
                    'name'    => 'new_seller_enable_selling',
                    'label'   => __( 'New Seller Enable Selling', 'dokan' ),
                    'desc'    => __( 'Make selling status enable for new registred seller', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
                'product_style' => array(
                    'name'    => 'product_style',
                    'label'   => __( 'Add/Edit Product Style', 'dokan' ),
                    'desc'    => __( 'The style you prefer for seller to add or edit products. ', 'dokan' ),
                    'type'    => 'select',
                    'default' => 'old',
                    'options' => array(
                        'old' => __( 'Tab View', 'dokan' ),
                        'new' => __( 'Flat View', 'dokan' )
                    )
                ),
                'product_category_style' => array(
                    'name'    => 'product_category_style',
                    'label'   => __( 'Category Selection', 'dokan' ),
                    'desc'    => __( 'What option do you prefer for seller to select product category? ', 'dokan' ),
                    'type'    => 'select',
                    'default' => 'single',
                    'options' => array(
                        'single' => __( 'Single', 'dokan' ),
                        'multiple' => __( 'Multiple', 'dokan' )
                    )
                ),
                'product_status' => array(
                    'name'    => 'product_status',
                    'label'   => __( 'New Product Status', 'dokan' ),
                    'desc'    => __( 'Product status when a seller creates a product', 'dokan' ),
                    'type'    => 'select',
                    'default' => 'pending',
                    'options' => array(
                        'publish' => __( 'Published', 'dokan' ),
                        'pending' => __( 'Pending Review', 'dokan' )
                    )
                ),
                'seller_percentage' => array(
                    'name'    => 'seller_percentage',
                    'label'   => __( 'Seller Percentage', 'dokan' ),
                    'desc'    => __( 'How much amount (%) a seller will get from each order', 'dokan' ),
                    'default' => '90',
                    'type'    => 'text',
                ),
                'order_status_change' => array(
                    'name'    => 'order_status_change',
                    'label'   => __( 'Order Status Change', 'dokan' ),
                    'desc'    => __( 'Seller can change order status', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'on'
                ),
                'withdraw_methods' => array(
                    'name'    => 'withdraw_methods',
                    'label'   => __( 'Withdraw Methods', 'dokan' ),
                    'desc'    => __( 'Withdraw methods for sellers', 'dokan' ),
                    'type'    => 'multicheck',
                    'default' => array( 'paypal' => 'paypal' ),
                    'options' => dokan_withdraw_get_methods()
                ),
                'withdraw_order_status' => array(
                    'name'    => 'withdraw_order_status',
                    'label'   => __( 'Order Status for Withdraw', 'dokan' ),
                    'desc'    => __( 'Order status for which seller can make a withdraw request.', 'dokan' ),
                    'type'    => 'multicheck',
                    'default' => array( 'wc-completed' => __( 'Completed', 'dokan' ), 'wc-processing' => __( 'Processing', 'dokan' ), 'wc-on-hold' => __( 'On-hold', 'dokan' ) ),
                    'options' => array( 'wc-completed' => __( 'Completed', 'dokan' ), 'wc-processing' => __( 'Processing', 'dokan' ), 'wc-on-hold' => __( 'On-hold', 'dokan' ) )
                ),
                'withdraw_limit' => array(
                    'name'    => 'withdraw_limit',
                    'label'   => __( 'Minimum Withdraw Limit', 'dokan' ),
                    'desc'    => __( 'Minimum balance required to make a withdraw request', 'dokan' ),
                    'default' => '50',
                    'type'    => 'text',
                ),
                'withdraw_date_limit' => array(
                    'name'    => 'withdraw_date_limit',
                    'label'   => __( 'Withdraw Threshold', 'dokan' ),
                    'desc'    => __( 'Days, ( Make order matured to make a withdraw request) <br> Value "0" will inactive this option', 'dokan' ),
                    'default' => '0',
                    'type'    => 'text',
                ),
                'custom_store_url' => array(
                    'name'    => 'custom_store_url',
                    'label'   => __( 'Seller Store URL', 'dokan' ),
                    'desc'    => sprintf( __( 'Define seller store URL (%s<strong>[this-text]</strong>/[seller-name])', 'dokan' ), site_url( '/' ) ),
                    'default' => 'store',
                    'type'    => 'text',
                ),
                'review_edit' => array(
                    'name'    => 'review_edit',
                    'label'   => __( 'Review Editing', 'dokan' ),
                    'desc'    => __( 'Seller can edit product reviews', 'dokan' ),
                    'type'    => 'checkbox',
                    'default' => 'off'
                ),
            ),
            'dokan_pages' => array(
                'dashboard' => array(
                    'name'    => 'dashboard',
                    'label'   => __( 'Dashboard', 'dokan' ),
                    'type'    => 'select',
                    'options' => $pages_array
                ),
                'my_orders' => array(
                    'name'    => 'my_orders',
                    'label'   => __( 'My Orders', 'dokan' ),
                    'type'    => 'select',
                    'options' => $pages_array
                )
            )
        );

        return apply_filters( 'dokan_settings_fields', $settings_fields );
    }

    function dashboard() {
        include dirname(__FILE__) . '/dashboard.php';
    }

    function settings_page() {
        echo '<div class="wrap">';
        settings_errors();

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    function withdraw_page() {
        include dirname(__FILE__) . '/withdraw.php';
    }

    function seller_listing() {
        include dirname(__FILE__) . '/sellers.php';
    }

    function report_page() {
        global $wpdb;

        include dirname(__FILE__) . '/reports.php';
    }

    function addon_page() {
        include dirname(__FILE__) . '/add-on.php';
    }

    function tools_page() {
        include dirname(__FILE__) . '/tools.php';
    }

    function tools_page_handler() {
        if ( isset( $_GET['dokan_action'] ) && current_user_can( 'manage_options' ) ) {
            $action = $_GET['dokan_action'];

            check_admin_referer( 'dokan-tools-action' );

            switch ($action) {
                case 'dokan_install_pages':

                    $pages = array(
                        array(
                            'post_title' => __( 'Dashboard', 'dokan' ),
                            'slug'       => 'dashboard',
                            'page_id'    => 'dashboard',
                            'content'    => '[dokan-dashboard]'
                        ),
                        array(
                            'post_title' => __( 'Store List', 'dokan' ),
                            'slug'       => 'store-listing',
                            'page_id'    => 'my_orders',
                            'content'    => '[dokan-stores]'
                        ),
                    );

                    foreach ($pages as $page) {
                        $page_id = wp_insert_post( array(
                            'post_title'     => $page['post_title'],
                            'post_name'      => $page['slug'],
                            'post_content'   => $page['content'],
                            'post_status'    => 'publish',
                            'post_type'      => 'page',
                            'comment_status' => 'closed'
                        ) );

                        if ( $page['slug'] == 'dashboard' ) {
                            update_option( 'dokan_pages', array( 'dashboard' => $page_id ) );
                        }
                    }

                    flush_rewrite_rules();

                    wp_redirect( admin_url( 'admin.php?page=dokan-tools&msg=page_installed' ) );
                    exit;

                    break;

                case 'regen_sync_table':
                    dokan_generate_sync_table();

                    wp_redirect( admin_url( 'admin.php?page=dokan-tools&msg=regenerated' ) );
                    exit;
                    break;

                default:
                    # code...
                    break;
            }
        }
    }

    public function do_updates() {
        if ( isset( $_GET['dokan_do_update'] ) && $_GET['dokan_do_update'] == 'true' ) {
            $installer = new Dokan_Installer();
            $installer->do_upgrades();
        }
    }

    public function is_dokan_needs_update() {
        $installed_version = get_option( 'dokan_theme_version' );

        // may be it's the first install
        if ( ! $installed_version ) {
            return false;
        }

        if ( version_compare( $installed_version, '1.2', '<' ) ) {
            return true;
        }

        return false;
    }

    public function update_notice() {
        if ( ! $this->is_dokan_needs_update() ) {
            return;
        }
        ?>
        <div id="message" class="updated">
            <p><?php _e( '<strong>Dokan Data Update Required</strong> &#8211; We need to update your install to the latest version', 'dokan' ); ?></p>
            <p class="submit"><a href="<?php echo add_query_arg( 'dokan_do_update', 'true', admin_url( 'admin.php?page=dokan' ) ); ?>" class="dokan-update-btn button-primary"><?php _e( 'Run the updater', 'dokan' ); ?></a></p>
        </div>

        <script type="text/javascript">
            jQuery('.dokan-update-btn').click('click', function(){
                return confirm( '<?php _e( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'dokan' ); ?>' );
            });
        </script>
    <?php
    }
}

$settings = new Dokan_Admin_Settings();
