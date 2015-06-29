<?php
/*
 * Zendesk Zendesk_Wordpress_Admin Class
 *
 */

require_once( plugin_dir_path( __FILE__ ) . 'zendesk-wordpress-admin-settings.php' );
require_once( plugin_dir_path( __FILE__ ) . 'zendesk-wordpress-admin-remote-auth-settings.php' );
require_once( plugin_dir_path( __FILE__ ) . 'zendesk-wordpress-admin-remote-auth-settings.php' );
require_once( plugin_dir_path( __FILE__ ) . 'zendesk-wordpress-agents.php' );

/*
 * The Zendesk Zendesk_Wordpress_Admin Class
 *
 * Handles all the work with the Zendesk_Wordpress_Admin side of the plugin.
 *
 */

class Zendesk_Wordpress_Admin {

  protected static $instance = null;

  /*
   * Get an instance of this class
   */
  public static function get_instance() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /*
   * Zendesk_Wordpress_Admin Initialization
   *
   * Register a bunch of sections and fields for the Zendesk plugin
   * options. All the options are stored in the $this->settings
   * array which is kept under the 'zendesk-settings' key inside
   * the WordPress database.
   *
   * @uses register_setting, add_settings_section, add_settings_field
   *
   */
  public function _admin_init() {
    global $zendesk_support;

    // Scripts and styles
    add_action( 'admin_print_styles', array( &$this, '_admin_print_styles' ) );

    // Comments columns & row actions
    add_filter( 'comment_row_actions', array( &$zendesk_support, '_comment_row_actions' ), 10, 2 );
    add_filter( 'manage_edit-comments_columns', array( &$zendesk_support, '_comments_columns_filter' ), 10, 1 );
    add_action( 'manage_comments_custom_column', array( &$zendesk_support, '_comments_columns_action' ), 10, 1 );
    add_action( 'admin_notices', array( &$this, '_wp_admin_notices' ) );

    // General Settings
    register_setting( 'zendesk-settings', 'zendesk-settings', array(
      Zendesk_Wordpress_Admin_Settings::get_instance(),
      '_validate_settings'
    ) );

    // Authentication Details
    add_settings_section( 'authentication', __( 'Your Zendesk Account', 'zendesk' ), array(
      Zendesk_Wordpress_Admin_Settings::get_instance(),
      '_settings_section_authentication'
    ), 'zendesk-settings' );
    add_settings_field( 'account', __( 'Subdomain', 'zendesk' ), array(
      Zendesk_Wordpress_Admin_Settings::get_instance(),
      '_settings_field_account'
    ), 'zendesk-settings', 'authentication' );

    // Show SSL when debug is on.
    if ( ZENDESK_DEBUG ) {
      add_settings_field( 'ssl', __( 'Use SSL', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_ssl'
      ), 'zendesk-settings', 'authentication' );
    }

    // Display the rest of the settings only if a Zendesk account has been specified.
    if ( $zendesk_support->settings['account'] ) {
      // Dashboard Widget Section
      add_settings_section( 'dashboard_widget', __( 'Dashboard Widget Visibility', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_section_dashboard_widget'
      ), 'zendesk-settings' );
      add_settings_field( 'dashboard_administrator', __( 'Administrators', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_dashboard_access'
      ), 'zendesk-settings', 'dashboard_widget', array( 'role' => 'administrator' ) );
      add_settings_field( 'dashboard_editor', __( 'Editors', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_dashboard_access'
      ), 'zendesk-settings', 'dashboard_widget', array( 'role' => 'editor' ) );
      add_settings_field( 'dashboard_author', __( 'Authors', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_dashboard_access'
      ), 'zendesk-settings', 'dashboard_widget', array( 'role' => 'author' ) );
      add_settings_field( 'dashboard_contributor', __( 'Contributors', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_dashboard_access'
      ), 'zendesk-settings', 'dashboard_widget', array( 'role' => 'contributor' ) );
      add_settings_field( 'dashboard_subscriber', __( 'Subscribers', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_dashboard_access'
      ), 'zendesk-settings', 'dashboard_widget', array( 'role' => 'subscriber' ) );

      // Contact Form Section
      add_settings_field( 'contact_form_anonymous', __( 'Anonymous Requests', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_contact_form_anonymous'
      ), 'zendesk-settings', 'contact_form' );
      add_settings_field( 'contact_form_anonymous_user', __( 'Anonymous Requests By', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_contact_form_anonymous_user'
      ), 'zendesk-settings', 'contact_form' );

      add_settings_section( 'contact_form', __( 'Contact Form Settings', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_section_contact_form'
      ), 'zendesk-settings' );
      add_settings_field( 'contact_form_title', __( 'Form Title', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_contact_form_title'
      ), 'zendesk-settings', 'contact_form' );
      add_settings_field( 'contact_form_summary', __( 'Summary Label', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_contact_form_summary'
      ), 'zendesk-settings', 'contact_form' );
      add_settings_field( 'contact_form_details', __( 'Details Label', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_contact_form_details'
      ), 'zendesk-settings', 'contact_form' );
      add_settings_field( 'contact_form_submit', __( 'Submit Button Label', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_contact_form_submit'
      ), 'zendesk-settings', 'contact_form' );

      // Dropbox Settings - Only appears if the Dropbox is active, or if the web widget is off and there is a Feedback Tab snipped in the settings
      if ( ( $zendesk_support->settings['dropbox_display'] == 'auto' || $zendesk_support->settings['dropbox_display'] == 'manual' ) || ( $zendesk_support->settings['webwidget_display'] == 'none' && $zendesk_support->settings['dropbox_code'] !== '' ) ) {
        add_settings_section( 'dropbox', __( 'Dropbox Settings', 'zendesk' ), array(
          Zendesk_Wordpress_Admin_Settings::get_instance(),
          '_settings_section_dropbox'
        ), 'zendesk-settings' );
        add_settings_field( 'dropbox_display', __( 'Display', 'zendesk' ), array(
          Zendesk_Wordpress_Admin_Settings::get_instance(),
          '_settings_field_dropbox_display'
        ), 'zendesk-settings', 'dropbox' );
        add_settings_field( 'dropbox_code', __( 'Dropbox Code', 'zendesk' ), array(
          Zendesk_Wordpress_Admin_Settings::get_instance(),
          '_settings_field_dropbox_code'
        ), 'zendesk-settings', 'dropbox' );
      }

      // Web Widget Settings
      add_settings_section( 'webwidget', __( 'Web Widget Settings', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_section_webwidget'
      ), 'zendesk-settings' );
      add_settings_field( 'webwidget_display', __( 'Display', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_webwidget_display'
      ), 'zendesk-settings', 'webwidget' );
      add_settings_field( 'webwidget_code', __( 'Web Widget Code', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Settings::get_instance(),
        '_settings_field_webwidget_code'
      ), 'zendesk-settings', 'webwidget' );

      // Remote Authentication Settings
      register_setting( 'zendesk-settings-remote-auth', 'zendesk-settings-remote-auth', array(
        Zendesk_Wordpress_Admin_Remote_Auth_Settings::get_instance(),
        '_validate_remote_auth_settings'
      ) );

      // Remote Authentication Section Zendesk
      add_settings_section( 'zendesk', __( 'Zendesk Configuration', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Remote_Auth_Settings::get_instance(),
        '_settings_remote_auth_section_zendesk'
      ), 'zendesk-settings-remote-auth' );
      add_settings_field( 'login_url', __( 'Remote Login URL', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Remote_Auth_Settings::get_instance(),
        '_settings_field_remote_auth_login_url'
      ), 'zendesk-settings-remote-auth', 'zendesk' );
      add_settings_field( 'logout_url', __( 'Remote Logout URL', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Remote_Auth_Settings::get_instance(),
        '_settings_field_remote_auth_logout_url'
      ), 'zendesk-settings-remote-auth', 'zendesk' );

      // Remote Authentication Section
      add_settings_section( 'general', __( 'General Settings', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Remote_Auth_Settings::get_instance(),
        '_settings_remote_auth_section_general'
      ), 'zendesk-settings-remote-auth' );
      add_settings_field( 'strategy', __( 'Remote Auth Strategy', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Remote_Auth_Settings::get_instance(),
        '_settings_remote_auth_strategy'
      ), 'zendesk-settings-remote-auth', 'general' );
      add_settings_field( 'enabled', __( 'Remote Auth Status', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Remote_Auth_Settings::get_instance(),
        '_settings_field_remote_auth_enabled'
      ), 'zendesk-settings-remote-auth', 'general' );
      add_settings_field( 'token', __( 'Remote Auth Shared Token', 'zendesk' ), array(
        Zendesk_Wordpress_Admin_Remote_Auth_Settings::get_instance(),
        '_settings_field_remote_auth_token'
      ), 'zendesk-settings-remote-auth', 'general' );

      // Zendesk Forms
      $zendesk_support->_process_forms();

    }
  }

  /*
     * Zendesk_Wordpress_Admin Styles & Scripts
     *
     * This method is fired for any possible admin page, which is why
     * we include the main admin.js scripts and the colorbox to use
     * for tickets widgets, and the comment to ticket forms.
     *
     */
  public function _admin_print_styles() {
    // Zendesk_Wordpress_Admin Scripts
    wp_enqueue_script( 'zendesk-admin', plugins_url( '/js/admin.js', ZENDESK_BASE_FILE ), array( 'jquery' ) );
    wp_enqueue_style( 'zendesk-admin', plugins_url( '/css/admin.css', ZENDESK_BASE_FILE ) );
    wp_enqueue_script( 'colorbox', plugins_url( '/js/jquery.colorbox-min.js', ZENDESK_BASE_FILE ), array( 'jquery' ) );
    wp_enqueue_style( 'colorbox', plugins_url( '/css/colorbox.css', ZENDESK_BASE_FILE ) );


    wp_localize_script( 'zendesk-admin', 'zendesk', array(
      'plugin_url' => plugins_url( '', ZENDESK_BASE_FILE )
    ) );
  }

  /*
     * Zendesk_Wordpress_Admin Notices
     *
     * These are different than the Zendesk notices, this is the core
     * WordPress functionality to display notices at the top of the
     * admin pages. Used for notifications.
     *
     */
  public function _wp_admin_notices() {
    global $zendesk_support;
    $agents = Zendesk_Wordpress_Agents::get_instance();

    if ( isset( $zendesk_support->settings['contact_form_anonymous'] ) && $zendesk_support->settings['contact_form_anonymous'] ) {

      $agent = $zendesk_support->settings['contact_form_anonymous_user'];
      if ( $zendesk_support->settings['account'] && ! $agents->_is_agent( $agent ) && current_user_can( 'manage_options' ) ) {
        ?>
        <div id="message" class="error"><p>
            <?php printf( __( '<strong>Whoops!</strong> The user specified as the anonymous requests author is not logged in to Zendesk! You can %s or kindly ask them to log in.', 'zendesk' ), sprintf( '<a href="' . admin_url( 'admin.php?page=zendesk-support' ) . '">%s</a>', __( 'change the user', 'zendesk' ) ) ); ?>
          </p></div>
      <?php
      }
    }
  }

  /*
   * Zendesk_Wordpress_Admin Menu
   *
   * Fired during the WordPress admin_menu hook, registers a new
   * admin menu page called Zendesk Support, the contents callback
   * is the semi-private _admin_menu_contents function.
   *
   * @uses add_menu_page
   *
   */
  public function _admin_menu() {
    global $zendesk_support;
    add_menu_page( 'Zendesk Support Settings', 'Zendesk', 'manage_options', 'zendesk-support', array(
      &$this,
      '_admin_menu_contents'
    ), plugins_url( '/images/zendesk-16.png', ZENDESK_BASE_FILE ) );
    $settings_page = add_submenu_page( 'zendesk-support', __( 'Zendesk Support Settings', 'zendesk' ), __( 'Settings', 'zendesk' ), 'manage_options', 'zendesk-support', array(
      &$this,
      '_admin_menu_contents'
    ) );

    if ( $zendesk_support->settings['account'] ) {
      add_submenu_page( 'zendesk-support', __( 'Zendesk Remote Authentication', 'zendesk' ), __( 'Remote Auth', 'zendesk' ), 'manage_options', 'zendesk-remote-auth', array(
        &$this,
        '_admin_menu_remote_auth_contents'
      ) );
    }

    add_action( 'admin_print_styles-' . $settings_page, array( &$this, '_admin_print_styles_settings' ) );
  }

  /*
   * Zendesk_Wordpress_Admin Styles & Scripts on Settings page
   *
   * This method is fired on the plugin settings page, handles the
   * child settings showing and hiding, placeholders and more.
   *
   */
  public function _admin_print_styles_settings() {
    wp_enqueue_script( 'zendesk-settings', plugins_url( '/js/settings.js', ZENDESK_BASE_FILE ), array(
      'jquery',
      'zendesk-admin'
    ) );
  }

  /*
   * Zendesk_Wordpress_Admin Menu Contents
   *
   * The contents of the admin menu registered above for the Zendesk
   * options. Below is one for remote auth options, uses the
   * WordPress Settings API.
   *
   */
  public function _admin_menu_contents() {
    global $zendesk_support;
    ?>
    <div class="wrap">
      <div id="icon-zendesk-32" class="icon32"><br></div>
      <h2><?php _e( 'Zendesk for WordPress Settings', 'zendesk' ); ?></h2>

      <?php if ( ! $zendesk_support->settings['account'] ): ?>
        <div id="message" class="updated below-h2 zendesk-info">
          <p><strong><?php _e( "You're almost there! Just one more thing...", 'zendesk' ); ?></strong></p>

          <p><?php _e( "Before you get your hands on all the juicy Zendesk for Wordpress features, we need to know your Zendesk subdomain. <br /> Your subdomain tells us who you are, and gives us access to the Zendesk API.", 'zendesk' ); ?></p>
        </div>
      <?php endif; ?>

      <form method="post" action="options.php">
        <?php wp_nonce_field( 'update-options' ); ?>
        <?php settings_fields( 'zendesk-settings' ); ?>
        <?php do_settings_sections( 'zendesk-settings' ); ?>
        <p class="submit">
          <input name="Submit" type="submit" class="button-primary"
                 value="<?php esc_attr_e( 'Save Changes', 'zendesk' ); ?>"/>
        </p>
      </form>
    </div>
    <?php
    // Print settings array for debug.
    if ( ZENDESK_DEBUG ) {
      echo '<pre>' . print_r( $zendesk_support->settings, true ) . '</pre>';
    }
  }

  /*
   * Zendesk_Wordpress_Admin Menu Remote Auth Contents
   *
   * The contents of the remote auth settings page registered in the
   * admin menu. Uses the Settings API to render the options.
   *
   */
  public function _admin_menu_remote_auth_contents() {
    ?>
    <div class="wrap">
      <div id="icon-zendesk-32" class="icon32"><br></div>
      <h2><?php _e( 'Zendesk Remote Authentication Settings', 'zendesk' ); ?></h2>

      <div id="message" class="updated below-h2">
        <p><strong><?php _e( 'Woah there Nelly!', 'zendesk' ); ?></strong></p>

        <p><?php _e( "Remote authentication takes a little bit of setup in here and inside Zendesk too. Don't worry, it's not rocket surgery.", 'zendesk' ); ?></p>

        <p><a target="_blank"
              href="https://support.zendesk.com/entries/20110872-setting-up-remote-authentication-for-wordpress"><?php _e( 'Check out this handy guide on getting it set up for WordPress.', 'zendesk' ); ?></a>
        </p>
      </div>

      <form method="post" action="options.php">
        <?php wp_nonce_field( 'update-options' ); ?>
        <?php settings_fields( 'zendesk-settings-remote-auth' ); ?>
        <?php do_settings_sections( 'zendesk-settings-remote-auth' ); ?>
        <p class="submit">
          <input name="Submit" type="submit" class="button-primary"
                 value="<?php esc_attr_e( 'Save Changes', 'zendesk' ); ?>"/>
        </p>
      </form>
    </div>
  <?php
  }

}
