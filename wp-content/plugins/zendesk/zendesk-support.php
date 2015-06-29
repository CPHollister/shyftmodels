<?php
/*
 * Plugin Name: Zendesk Support for WordPress
 * Plugin URI: http://zendesk.com
 * Description: Zendesk Support for WordPress
 * Author: Zendesk
 * Version: 1.6.2
 * Author URI: http://www.zendesk.com
 *
 */

// Debug
define( 'ZENDESK_DEBUG', false );

// Base file
define( 'ZENDESK_BASE_FILE', __FILE__ );

// Load Zendesk API Class & Compatibility hacks
require_once( plugin_dir_path( __FILE__ ) . 'classes/zendesk-wordpress-compatibility.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/zendesk-wordpress-api.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/JWT.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/zendesk-wordpress-admin.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/zendesk-wordpress-dashboard-widget.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/zendesk-wordpress-ajax.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/zendesk-wordpress-notices.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/zendesk-wordpress-agents.php' );
require_once( plugin_dir_path( __FILE__ ) . 'classes/zendesk-wordpress-utilities.php' );

/*
 * Zendesk Support Class
 *
 * This is the main plugin class, handles all the plugin options, WordPress
 * hooks and filters, as well as options validation. The Zendesk Web Widget
 * and the Zendesk Feedback Tab are fully defined in this class too.
 *
 */

class Zendesk_Support {
  public $settings = array();

  /*
   * Class Constructor
   *
   * Fired during WordPress init, assign actions and add filters, read
   * the settings from the database and construct the Zendesk URL.
   *
   */
  public function __construct() {

    // Load text domain
    load_plugin_textdomain( 'zendesk', null, basename( dirname( __FILE__ ) ) . '/languages' );

    add_action( 'admin_menu', array( Zendesk_Wordpress_Admin::get_instance(), '_admin_menu' ) );
    add_action( 'admin_init', array( Zendesk_Wordpress_Admin::get_instance(), '_admin_init' ) );

    // AJAX calls
    add_action( 'wp_ajax_zendesk_view_ticket', array( Zendesk_Wordpress_Ajax::get_instance(), '_ajax_view_ticket' ) );
    add_action( 'wp_ajax_zendesk_get_view', array( Zendesk_Wordpress_Ajax::get_instance(), '_ajax_get_view' ) );
    add_action( 'wp_ajax_zendesk_convert_to_ticket', array(
      Zendesk_Wordpress_Ajax::get_instance(),
      '_ajax_convert_to_ticket'
    ) );
    add_action( 'wp_ajax_zendesk_convert_to_ticket_post', array(
      Zendesk_Wordpress_Ajax::get_instance(),
      '_ajax_convert_to_ticket_post'
    ) );
    add_action( 'wp_ajax_zendesk_view_comments', array(
      Zendesk_Wordpress_Ajax::get_instance(),
      '_ajax_view_comments'
    ) );

    // Initialize
    $this->setup();

    // Setup the Dashboard widget.
    add_action( 'wp_dashboard_setup', array(
      Zendesk_Wordpress_Dashboard_Widget::get_instance(),
      '_dashboard_widget_setup'
    ) );

    // Let's see if we need to do a remote auth.
    $this->_do_remote_auth();

    // Let's see if Dropbox is set to auto
    if ( isset( $this->settings['dropbox_display'] ) && $this->settings['dropbox_display'] == 'auto' ) {
      add_action( 'wp_footer', array( &$this, 'dropbox_code' ) );
    }

    // Let's see if the visibility of the Web Widget is set to auto
    if ( isset( $this->settings['webwidget_display'] ) && $this->settings['webwidget_display'] == 'auto' ) {
      add_action( 'wp_footer', array( &$this, 'webwidget_code' ) );
    }
  }

  /*
   * Plugin Setup
   *
   * Load settings, set URLs, authenticate the current user.
   *
   */
  public function setup() {
    // Load up the settings, set the Zendesk URL and initialize the API object.
    $this->_load_settings();

    // Load default settings if there are no settings
    if ( false === $this->settings ) {
      $this->_default_settings();
    }

    $https             = ( isset( $this->settings['ssl'] ) && $this->settings['ssl'] ) ? 'https' : 'http';
    $this->zendesk_url = $https . '://' . $this->settings['account'] . '.zendesk.com';
    $this->api         = new Zendesk_Wordpress_API( $this->zendesk_url );

    // Fill in the Web Widget code if it's empty, we know the account, and they
    // haven't set the widget off
    if ( ! $this->settings['webwidget_code'] && $this->settings['account'] && $this->settings['webwidget_display'] != 'none' ) {
      $this->_set_default_webwidget_code();
    }

    // Zendesk Authentication Magic
    $this->zendesk_user = false;
    global $current_user;
    wp_get_current_user();

    // If the current user is logged in
    if ( 0 != $current_user->ID ) {

      // Gather the Zendesk user options
      $this->user         = $current_user;
      $this->zendesk_user = get_user_meta( $current_user->ID, 'zendesk_user_options', true );

      if ( $this->zendesk_user ) {
        $this->api->authenticate( $this->zendesk_user['username'], $this->zendesk_user['password'], false );
      }

    }
  }

  /*
   * Load Default Settings
   *
   * Sets the defaults for the settings array and calls _update_settings()
   * to write changes to the database. Generally run during plugin
   * activation or first run.
   *
   */
  private function _default_settings() {
    $this->settings             = $this->default_settings;
    $this->remote_auth_settings = $this->default_remote_auth_settings;

    $this->_update_settings();
  }

  /*
   * Set the default Web Widget code
   *
   * If users set their account setting we'll help them setting their Web Widget
   * code snippet for them. Once we have the snippet we also set the widget
  * visibility to auto.
   * They can of course overwrite these settings on the settings page.
   *
   */
  private function _set_default_webwidget_code() {
    if ( isset( $this->settings['account'] ) && $this->settings['account'] ) {
      $subdomain                        = $this->settings['account'];
      $this->settings['webwidget_code'] = <<<EOJS
<!-- Start of Zendesk Widget script -->
<script>/*<![CDATA[*/window.zEmbed||function(e,t){var n,o,d,i,s,a=[],r=document.createElement("iframe");window.zEmbed=function(){a.push(arguments)},window.zE=window.zE||window.zEmbed,r.src="javascript:false",r.title="",r.role="presentation",(r.frameElement||r).style.cssText="display: none",d=document.getElementsByTagName("script"),d=d[d.length-1],d.parentNode.insertBefore(r,d),i=r.contentWindow,s=i.document;try{o=s}catch(c){n=document.domain,r.src='javascript:var d=document.open();d.domain="'+n+'";void(0);',o=s}o.open()._l=function(){var o=this.createElement("script");n&&(this.domain=n),o.id="js-iframe-async",o.src=e,this.t=+new Date,this.zendeskHost=t,this.zEQueue=a,this.body.appendChild(o)},o.write('<body onload="document._l();">'),o.close()}("//assets.zendesk.com/embeddable_framework/main.js","{$subdomain}.zendesk.com");/*]]>*/</script>
<!-- End of Zendesk Widget script -->

EOJS;
      // We have a snippet now, display it by default unless the Feedback Tab is being used
      if ( $this->settings['dropbox_display'] == 'none' ) {
        $this->settings['webwidget_display'] = 'auto';
      } else {
        $this->settings['webwidget_display'] = 'none';
      }

      // Update the settings so these changes are permanent
      $this->_update_settings();
    }
  }

  /*
   * Load Settings
   *
   * Private function to load current settings from the database. Sets
   * settings to false if settings are not found (i.e. plugin is new).
   *
   */
  private function _load_settings() {
    $this->settings             = get_option( 'zendesk-settings', false );
    $this->remote_auth_settings = get_option( 'zendesk-settings-remote-auth', false );

    $this->default_settings = array(
      'ssl'                         => false,
      'version'                     => 1,
      'account'                     => '',
      'dashboard_administrator'     => 'tickets-widget',
      'dashboard_editor'            => 'contact-form',
      'dashboard_author'            => 'contact-form',
      'dashboard_contributor'       => 'contact-form',
      'dashboard_subscriber'        => 'contact-form',
      'contact_form_anonymous'      => false,
      'contact_form_anonymous_user' => false,
      'contact_form_title'          => __( 'How can we help you?', 'zendesk' ) . '  ',
      'contact_form_summary'        => __( 'Briefly describe your question', 'zendesk' ) . '  ',
      'contact_form_details'        => __( 'Give us some further details', 'zendesk' ) . '  ',
      'contact_form_submit'         => __( 'Submit', 'zendesk' ) . '  ',
      'dropbox_display'             => 'none',
      'dropbox_code'                => '',
      'webwidget_display'           => 'auto',
      'webwidget_code'              => ''
    );

    $this->default_remote_auth_settings = array(
      'enabled' => false,
      'token'   => ''
    );
  }

  /*
   * Delete Settings
   *
   * Removes all Zendesk settings from the database, as well as flushes
   * all the user's authentication settings. Use this during plugin
   * deactivation.
   *
   */
  private function _delete_settings() {
    delete_option( 'zendesk-settings' );
    delete_option( 'zendesk-settings-remote-auth' );
  }

  /*
   * Update Settings
   *
   * Use this private method after doing any changes to the settings
   * arrays. This method writes the changes to the database.
   *
   */
  private function _update_settings() {
    update_option( 'zendesk-settings', $this->settings );
    update_option( 'zendesk-settings-remote-auth', $this->remote_auth_settings );
  }

  /*
   * Dropbox Code
   *
   * Displays the javascript code for the Zendesk Dropbox. The options
   * in the $this->settings array are used for certain Zenbox options.
   * Depending on the options chosen, this fire in wp_footer or via a
   * custom template tag: zendesk_insert_dropbox()
   *
   */
  public function dropbox_code() {
    echo $this->settings['dropbox_code'];
  }

  /*
   * Web Widget Code
   *
   * Displays the javascript code for the Zendesk Web Widget. The options
   * in the $this->settings array are used for certain Zenbox options.
   * Depending on the options chosen, this fire in wp_footer or via a
   * custom template tag: the_zendesk_webwidget()
   *
   */
  public function webwidget_code() {
    if ( isset( $this->settings['webwidget_code'] ) && $this->settings['webwidget_code'] ) {
      echo $this->settings['webwidget_code'];
    }
  }

  /*
   * Form Processing
   *
   * This method is fired during admin init, does all the form
   * processing. Most of the forms are fired using the POST method,
   * although some (such as logout) can use the GET. GET should be
   * processed before post.
   *
   */
  public function _process_forms() {

    // Logout
    if ( isset( $_REQUEST['zendesk-logout'] ) && $this->zendesk_user ) {
      $this->zendesk_user = false;
      delete_user_meta( $this->user->ID, 'zendesk_user_options' );
      wp_redirect( admin_url( '?zendesk-logout-success=true' ) );
      die();
    }

    // Display a logout success message
    if ( isset( $_REQUEST['zendesk-logout-success'] ) ) {
      $notices = Zendesk_Wordpress_Notices::get_instance();
      $notices->_add_notice( 'zendesk_login', __( 'You have successfully logged out of your Zendesk account.', 'zendesk' ), 'confirm' );
    }

    // Change tickets view, probably never reached since an AJAX call
    // is more likely to respond to such a request. Lave this just in case.
    if ( isset( $_REQUEST['zendesk-tickets-change-view'] ) && is_numeric( $_REQUEST['zendesk-tickets-change-view'] ) && $this->zendesk_user ) {

      // Is somebody trying to cheat?
      $dashboard_widget = Zendesk_Wordpress_Dashboard_Widget::get_instance();
      if ( $dashboard_widget->_get_current_user_dashboard_widget() != 'tickets-widget' ) {
        $notices = Zendesk_Wordpress_Notices::get_instance();
        $notices->_add_notice( 'zendesk_login', __( 'You are not allowed to view the tickets widget', 'zendesk' ), 'alert' );

        return;
      }

      // Fire a request to catch all available views.
      $requested_view = (int) $_REQUEST['zendesk-tickets-change-view'];
      $views          = $this->api->get_views();

      if ( ! is_wp_error( $views ) ) {

        // Loop through the views and update the user meta.
        foreach ( $views as $view ) {
          if ( $view->id == $requested_view ) {
            $this->zendesk_user['default_view'] = array(
              'id'    => $view->id,
              'title' => $view->title
            );

            // Update and redirect.
            update_user_meta( $this->user->ID, 'zendesk_user_options', $this->zendesk_user );
            wp_redirect( admin_url() );
            die();
          }
        }
      } else {
        // Views could not be fetched
        $notices = Zendesk_Wordpress_Notices::get_instance();
        $notices->_add_notice( 'zendesk_tickets_widget', $views->get_error_message(), 'alert' );

        return;
      }
    }

    // Gather and validate some form data
    if ( ! isset( $_POST['zendesk-form-submit'], $_POST['zendesk-form-context'], $_POST['zendesk-form-data'] ) ) {
      return;
    }
    $context   = $_POST['zendesk-form-context'];
    $form_data = $_POST['zendesk-form-data'];

    // Pick the right form processor
    switch ( $context ) {
      case 'login':
        if ( $this->has_empty_fields( $form_data ) ) {
          $notices = Zendesk_Wordpress_Notices::get_instance();
          $notices->_add_notice( 'zendesk_login', __( 'All fields are required. Please try again.', 'zendesk' ), 'alert' );

          return;
        }

        $username = $form_data['username'];
        $password = $form_data['password'];

        $auth = $this->api->authenticate( $username, $password );
        if ( ! is_wp_error( $auth ) ) {
          // Get the user views
          $views = $this->api->get_views();

          if ( ! is_wp_error( $views ) ) {
            $default_view = array_shift( $views );
          } else {
            $default_view        = false;
            $default_view->id    = 0;
            $default_view->title = __( 'My open requests', 'zendesk' );
          }

          // Since this is not a remote auth set remote_auth to
          // false.
          $this->zendesk_user = array(
            'username'     => $username,
            'password'     => $password,
            'role'         => $auth->role,
            'default_view' => array(
              'id'    => $default_view->id,
              'title' => $default_view->title,
            )
          );

          $notices = Zendesk_Wordpress_Notices::get_instance();
          $notices->_add_notice( 'zendesk_login', sprintf( __( 'Howdy, <strong>%s</strong>! You are now logged in to Zendesk.', 'zendesk' ), $auth->name ), 'confirm' );

          update_user_meta( $this->user->ID, 'zendesk_user_options', $this->zendesk_user );
        } else {
          $notices = Zendesk_Wordpress_Notices::get_instance();
          $notices->_add_notice( 'zendesk_login', $auth->get_error_message(), 'alert' );
        }

        break;

      case 'create-ticket':

        $notices = Zendesk_Wordpress_Notices::get_instance();
        $agents  = Zendesk_Wordpress_Agents::get_instance();
        // Is somebody trying to cheat?
        $dashboard_widget = Zendesk_Wordpress_Dashboard_Widget::get_instance();
        if ( $dashboard_widget->_get_current_user_dashboard_widget() != 'contact-form' ) {
          $notices->_add_notice( 'zendesk_login', __( 'You are not allowed to view the contact form.', 'zendesk' ), 'alert' );

          return;
        }

        if ( ! isset( $form_data['summary'], $form_data['details'] ) ) {
          $notices->_add_notice( 'zendesk_contact_form', __( 'All fields are required. Please try again.', 'zendesk' ), 'alert' );

          return;
        }

        $summary = strip_tags( stripslashes( trim( $form_data['summary'] ) ) );
        $details = strip_tags( stripslashes( trim( $form_data['details'] ) ) );

        // Quick validation
        if ( empty( $summary ) || empty( $details ) ) {
          $notices->_add_notice( 'zendesk_contact_form', __( 'All fields are required. Please try again.', 'zendesk' ), 'alert' );

          return;
        }

        // Either tickets.json or requests.json based on user role.
        if ( $agents->_is_agent() ) {

          // Agent requests
          $response = $this->api->create_ticket( $summary, $details );

        } elseif ( ! $agents->_is_agent() && $this->zendesk_user ) {

          // End-users request (logged in)
          $response = $this->api->create_request( $summary, $details );

        } else {

          // Anonymous requests (if allowed in plugin settings)
          if ( $this->settings['contact_form_anonymous'] && $agents->_is_agent( $this->settings['contact_form_anonymous_user'] ) ) {

            // Find the agent to fire anonymous requests
            $agent = $agents->_get_agent( $this->settings['contact_form_anonymous_user'] );

            // Make sure the agent is there and is an agent (again)
            if ( ! $agent ) {
              $notices->_add_notice( 'zendesk_contact_form', __( 'Something went wrong. We could not use the agent to fire this request.', 'zendesk' ), 'alert' );
              break;
            }

            // Awkwward!
            if ( $agent['username'] == $this->user->user_email ) {
              $notices->_add_notice( 'zendesk_contact_form', sprintf( __( 'Wow, you managed to fire a request "on behalf of" yourself! Why don\'t you <a href="%s">login first</a>?', 'zendesk' ), admin_url( '?zendesk-login-form=true' ) ), 'alert' );
              break;
            }

            // Clone the current API settings and change the authentication pair
            $api = clone $this->api;
            $api->authenticate( $agent['username'], $agent['password'], false );

            // Fire a new ticket using the current user's name and email, similar to comments to tickets thing.
            $response = $api->create_ticket( $summary, $details, $this->user->display_name, $this->user->user_email );

            // Get rid of the cloned object
            unset( $api );
          }
        }

        // Error handling
        if ( ! is_wp_error( $response ) ) {
          $notices->_add_notice( 'zendesk_contact_form', __( 'Your request has been created successfully!', 'zendesk' ), 'confirm' );
        } else {
          $notices->_add_notice( 'zendesk_contact_form', $response->get_error_message(), 'alert' );
        }

        break;
    }
  }

  /*
   * A helper function that checks if username or password field is empty,
   * returning true if either one of these is empty or undefined.
   */
  private function has_empty_fields( $form_data ) {
    return ! isset( $form_data['username'] ) || ! isset( $form_data['password'] ) || empty( $form_data['username'] ) || empty( $form_data['password'] );
  }

  /*
   * Comment Row Actions
   *
   * Filtered at comment_row_actions, displays a Convert to Zendesk
   * ticket in the admin panel (comments view).
   *
   */
  public function _comment_row_actions( $actions, $comment ) {
    $agents = Zendesk_Wordpress_Agents::get_instance();

    // Do some validation, only agents can convert comments to tickets.
    // Pingbacks cannot be converted to tickets, and comments already
    // converted too.

    if ( $agents->_is_agent() && $comment->comment_type != 'pingback' && ! get_comment_meta( $comment->comment_ID, 'zendesk-ticket', true ) ) {
      $actions['zendesk'] = '<a class="zendesk-convert" href="#" data-id="' . $comment->comment_ID . '">' . __( 'Convert to Zendesk Ticket', 'zendesk' ) . '</a>';
    }

    return $actions;
  }

  /*
   * Comment Columns Filter
   *
   * Adds an extra column to the comments table with the "zendesk" key
   * and "Zendesk" as the caption.
   *
   */
  public function _comments_columns_filter( $columns ) {
    $agents = Zendesk_Wordpress_Agents::get_instance();
    if ( $agents->_is_agent() ) {
      $columns['zendesk'] = 'Zendesk';
    }

    return $columns;
  }

  /*
   * Comment Columns Action
   *
   * Works in pair with the function above, scans for when a table
   * contains the 'zendesk' column and whether the current user is
   * an agent.
   *
   */
  public function _comments_columns_action( $column ) {
    global $comment;
    $agents = Zendesk_Wordpress_Agents::get_instance();
    if ( $column == 'zendesk' && $agents->_is_agent() ) {
      $ticket_id = get_comment_meta( $comment->comment_ID, 'zendesk-ticket', true );

      // Make sure it's valid before printing.
      if ( $comment->comment_type != 'pingback' && $ticket_id ) {
        echo '<a target="_blank" class="zendesk-comment-ticket-id" href="' . Zendesk_Wordpress_Utilities::_ticket_url( $ticket_id ) . '">#' . $ticket_id . '</a>';
      }
    }
  }


  /*
   * Remote Authentication Process
   *
   * This is fired during plugin setup, i.e. during the init WordPress
   * action, thus we have control over any redirects before the request
   * is ever processed by the WordPress interpreter.
   *
   * Remote Auth is described here: https://support.zendesk.com/entries/23675367
   *
   * This method does both login and logout requests.
   *
   */
  public function _do_remote_auth() {
    // This is a login request.
    if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'zendesk-remote-login' ) {

      // Don't waste time if remote auth is turned off.
      if ( ! isset( $this->remote_auth_settings['enabled'] ) || ! $this->remote_auth_settings['enabled'] ) {
        _e( 'Remote authentication is not configured yet.', 'zendesk' );
        die();
      }

      // These are created by Zendesk
      $timestamp = $_REQUEST['timestamp'];
      $return_to = $_REQUEST['return_to'];

      global $current_user;
      wp_get_current_user();

      // If the current user is logged in
      if ( 0 != $current_user->ID ) {

        // Pick the most appropriate name for the current user.
        if ( $current_user->user_firstname != '' && $current_user->user_lastname != '' ) {
          $name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
        } else {
          $name = $current_user->display_name;
        }

        // Gather more info from the user, incl. external ID
        $email       = $current_user->user_email;
        $external_id = $current_user->ID;

        // The token is the remote "Shared Secret" under Settings - Security - SSO
        $token = $this->remote_auth_settings['token'];

        $current_strategy = $this->remote_auth_settings['strategy'];

        if ( ! $current_strategy || $current_strategy === 'classic' ) {

          $hash = md5( $name . $email . $external_id . $token . $timestamp );

          $sso_url = trailingslashit( $this->zendesk_url ) . 'access/remote/?action=zendesk-remote-login&return_to=' . urlencode( $return_to ) . '&name=' . urlencode( $name ) . '&email=' . urlencode( $email ) . '&external_id=' . urlencode( $external_id ) . '&timestamp=' . urlencode( $timestamp ) . '&hash=' . urlencode( $hash );
          wp_redirect( $sso_url );

          die();

        } else {

          $now = time();
          $jti = md5( $now . rand() );

          $payload = array(
            "jti"         => $jti,
            "iat"         => $now,
            "name"        => $name,
            "email"       => $email,
            "external_id" => $external_id
          );

          $jwt = JWT::encode( $payload, $token );

          // Create the SSO redirect URL and fire the redirect.
          $sso_url = trailingslashit( $this->zendesk_url ) . 'access/jwt/?action=zendesk-remote-login&return_to=' . urlencode( $return_to ) . '&jwt=' . $jwt;
          wp_redirect( $sso_url );

          // No further output.
          die();

        }
      } else {

        // If the current user is not logged in we ask him to visit the login form
        // first, authenticate and specify the current URL again as the return
        // to address. Hopefully WordPress will understand this.
        wp_redirect( wp_login_url( wp_login_url() . '?action=zendesk-remote-login&timestamp=' . urlencode( $timestamp ) . '&return_to=' . urlencode( $return_to ) ) );
        die();
      }
    }

    // Is this a logout request? Errors from Zendesk are handled here too.
    if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'zendesk-remote-logout' ) {

      // Don't waste time if remote auth is turned off.
      if ( ! isset( $this->remote_auth_settings['enabled'] ) || ! $this->remote_auth_settings['enabled'] ) {
        _e( 'Remote authentication is not configured yet.', 'zendesk' );
        die();
      }


      // Error processing and info messages are done here.
      $kind    = isset( $_REQUEST['kind'] ) ? $_REQUEST['kind'] : 'info';
      $message = isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : 'nothing';

      // Depending on the message kind
      if ( $kind == 'info' ) {

        // When the kind is an info, it probably means that the logout
        // was successful, thus, logout of WordPress too.
        wp_redirect( htmlspecialchars_decode( wp_logout_url() ) );
        die();

      } elseif ( $kind == 'error' ) {
        // If there was an error...
        ?>
        <p><?php _e( 'Remote authentication failed: ', 'zendesk' ); ?><?php echo $message; ?>.</p>
        <ul>
          <li><a href="<?php echo $this->zendesk_url; ?>"><?php _e( 'Try again', 'zendesk' ); ?></a></li>
          <li><a
              href="<?php echo wp_logout_url(); ?>"><?php printf( __( 'Log out of %s', 'zendesk' ), get_bloginfo( 'name' ) ); ?></a>
          </li>
          <li><a
              href="<?php echo admin_url(); ?>"><?php printf( __( 'Return to %s dashboard', 'zendesk' ), get_bloginfo( 'name' ) ); ?></a>
          </li>
        </ul>
      <?php
      }

      // No further output.
      die();
    }
  }

  /*
   * Helper: Ticket Status
   *
   * Internal function, repeats Zendesk's ticket statuses, ready
   * for translation. Used by the tickets widget.
   *
   */
  public function _ticket_status( $status ) {
    return __( $status, 'zendesk' );
  }

  /*
   * Get Current User Role (helper)
   *
   * Used internally, since the tickets widget and contact form widget
   * are distributed among roles, not capabilities. This private method
   * returns the current role as a string.
   *
   * @uses current_user_can
   *
   */
  public function _get_current_user_role() {
    foreach ( array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ) as $role ) {
      if ( current_user_can( $role ) ) {
        return $role;
      }
    }
  }

  /*
   * Helper: Is Default
   *
   * Checks whether the given key exists in the settings and whether
   * it's equal to the default settings. Used in contact form labels
   * to provide placeholders.
   *
   */
  public function _is_default( $key ) {
    return $this->settings[ $key ] === $this->default_settings[ $key ];
  }

  /*
   * Dropbox template tag
   *
   * Definition is outside of this class, logic is inside.
   *
   */
  public function the_zendesk_dropbox() {
    if ( isset( $this->settings['dropbox_display'] ) && $this->settings['dropbox_display'] == 'manual' ) {
      return $this->dropbox_code();
    }
  }

  /*
   * Web Widget template tag
   *
   * Definition is outside of this class, logic is inside.
   *
   */
  public function the_zendesk_webwidget() {
    if ( isset( $this->settings['webwidget_display'] ) && $this->settings['webwidget_display'] == 'manual' ) {
      return $this->webwidget_code();
    }
  }
}

;

// Register the Zendesk_Support class initialization during WordPress' init action. Globally available through $zendesk_support global.
add_action( 'init', create_function( '', 'global $zendesk_support; $zendesk_support = new Zendesk_Support();' ) );


/*
 * Dropbox template tag
 *
 * This is the template tage used by those users who only want the dropbox
 * displayed on certain pages.
 *
 * @global $zendesk_support
 *
 */
function the_zendesk_dropbox() {
  global $zendesk_support;

  // Simply call the method inside the object. Make sure object is
  // initialized before calling it's method.
  if ( $zendesk_support ) {
    $zendesk_support->the_zendesk_dropbox();
  }
}

/*
 * Web Widget template tag
 *
 * This is the template tag used by those users who only want the web widget
 * displayed on certain pages.
 *
 * @global $zendesk_support
 *
 */
function the_zendesk_webwidget() {
  global $zendesk_support;

  // Simply call the method inside the object. Make sure object is
  // initialized before calling it's method.
  if ( $zendesk_support ) {
    $zendesk_support->the_zendesk_webwidget();
  }
}
