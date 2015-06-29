<?php

require_once( plugin_dir_path( __FILE__ ) . 'zendesk-wordpress-notices.php' );
require_once( plugin_dir_path( __FILE__ ) . 'zendesk-wordpress-tickets.php' );

/*
 * The Dashboard widget Class
 *
 * This has all the methods to get the code for the dashboard widget
 *
 */

class Zendesk_Wordpress_Dashboard_Widget {
  protected static $instance = null;

  /*
   * get an instance of this class
   */
  public static function get_instance() {
    if ( is_null( self::$instance ) ) {
      self::$instance = new self();
    }

    return self::$instance;
  }

  /*
   * Dashboard Widget Setup
   *
   * This function checks the current user's Zendesk credentials as well
   * as the plugin settings for Dashboard Widget, then displays the
   * correct widget. All dashboard widgets have the same ID, meaning
   * that only one instance could be used every time. This is done to
   * keep the widget's sort order once set.
   *
   * @uses wp_add_dashboard_widget
   *
   */
  function _dashboard_widget_setup() {
    global $zendesk_support;
    $agents         = Zendesk_Wordpress_Agents::get_instance();
    $widget_options = $this->_get_current_user_dashboard_widget();

    // If the plugin hasn't been configured yet.
    if ( ! isset( $zendesk_support->settings['account'] ) || empty( $zendesk_support->settings['account'] ) && $widget_options != 'none' ) {
      wp_add_dashboard_widget( 'zendesk-dashboard-widget', __( 'Zendesk Support', 'zendesk' ), array(
        &$this,
        '_dashboard_widget_config'
      ) );

      return;
    }

    if ( ! $zendesk_support->zendesk_user && $widget_options == 'contact-form' && $zendesk_support->settings['contact_form_anonymous'] && $agents->_is_agent( $zendesk_support->settings['contact_form_anonymous_user'] ) ) {
      wp_add_dashboard_widget( 'zendesk-dashboard-widget', $zendesk_support->settings['contact_form_title'], array(
        &$this,
        '_dashboard_widget_contact_form'
      ) );

      return;
    }

    if ( ! $zendesk_support->zendesk_user && $widget_options != 'none' ) {
      wp_add_dashboard_widget( 'zendesk-dashboard-widget', __( 'Zendesk Support Login', 'zendesk' ), array(
        &$this,
        '_dashboard_widget_login'
      ) );
    } else {

      // Based on user role and the plugin settings.
      switch ( $widget_options ) {
        case 'contact-form':
          wp_add_dashboard_widget( 'zendesk-dashboard-widget', $zendesk_support->settings['contact_form_title'], array(
            &$this,
            '_dashboard_widget_contact_form'
          ) );
          break;

        case 'tickets-widget':
          wp_add_dashboard_widget( 'zendesk-dashboard-widget', __( 'Zendesk for WordPress', 'zendesk' ), array(
            &$this,
            '_dashboard_widget_tickets'
          ) );
          break;
      }
    }
  }

  /*
   * Dashboard Widget Config
   *
   * This widget is displayed if the plugin is activated, but the
   * administrator has not set up the account settings yet.
   *
   */
  public function _dashboard_widget_config() {
    ?>
    <div class="inside">
      <?php if ( current_user_can( 'manage_options' ) ): ?>
        <img class="zendesk-buddha-smaller"
             src="<?php echo plugins_url( '/images/zendesk-32-color.png', ZENDESK_BASE_FILE ); ?>" alt="Zendesk"/>
        <p
          class="description"><?php printf( __( "Howdy! You're almost ready to go, we just need you to <a href='%s'>set up a few things first.</a>", 'zendesk' ), admin_url( 'zendesk-wordpress-admin.php?page=zendesk-support' ) ); ?></p>
      <?php else: ?>
        <img class="zendesk-buddha-smaller"
             src="<?php echo plugins_url( '/images/zendesk-32-color.png', ZENDESK_BASE_FILE ); ?>" alt="Zendesk"/>
        <p
          class="description"><?php _e( "Howdy! Looks like the WordPress administrator hasn't set this plugin up yet. Give them a poke to get moving!", 'zendesk' ); ?></p>
      <?php endif; ?>
    </div>
  <?php
  }

  /*
   * Dashboard Widget: Login
   *
   * The login dashboard widget, displayed to the users that are logged
   * in, but not into their Zendesk account. Zendesk account credentials
   * are kept in the zendesk_user_options user meta field in the database,
   * loaded during admin_init and could be accessed via $this->zendesk_user.
   *
   */
  public function _dashboard_widget_login() {
    global $zendesk_support;
    $notices = Zendesk_Wordpress_Notices::get_instance();
    ?>
    <div class="inside">
      <?php $notices->_do_notices( 'zendesk_login' ); ?>

      <img class="zendesk-buddha" src="<?php echo plugins_url( '/images/zendesk-32-color.png', ZENDESK_BASE_FILE ); ?>"
           alt="Zendesk"/>

      <p
        class="description"><?php _e( 'Use your Zendesk account credentials to log in the form below. Please note that these are not your WordPress username and password.', 'zendesk' ); ?></p>

      <form id="zendesk-login" method="post" action="<?php echo admin_url(); ?>">
        <input type="hidden" name="zendesk-form-submit" value="1"/>
        <input type="hidden" name="zendesk-form-context" value="login"/>

        <p>
          <label><?php _e( 'Username:', 'zendesk' ); ?></label>
          <input name="zendesk-form-data[username]" type="text" class="regular-text"
                 value="<?php echo $zendesk_support->user->user_email; ?>"/><br/>
        </p>

        <p>
          <label><?php _e( 'Password:', 'zendesk' ); ?></label>
          <input name="zendesk-form-data[password]" type="password" class="regular-text"/>
        </p>

        <p class="submit">
          <input name="Submit" type="submit" class="button-primary"
                 value="<?php esc_attr_e( 'Login to Zendesk', 'zendesk' ); ?>"/><br/>
          <?php _e( "Don't have an account?", 'zendesk' ); ?> <a
            href="<?php echo trailingslashit( $zendesk_support->zendesk_url ); ?>registration"><?php _e( 'Sign up!', 'zendesk' ); ?></a>
        </p>
        <br class="clear"/>
      </form>
    </div>
  <?php
  }

  /*
   * Dashboard Widget: Contact Form
   *
   * Displays the Contact Form widget in the dashboard. Upon processing,
   * a new ticket is created via the Zendesk API with the Summary and
   * Details filled out in this form. A logout link is also present.
   *
   */
  public function _dashboard_widget_contact_form() {
    global $zendesk_support;
    $notices = Zendesk_Wordpress_Notices::get_instance();
    ?>
    <div class="inside">
      <?php
      $notices->_do_notices( 'zendesk_login' );
      $notices->_do_notices( 'zendesk_contact_form' );
      ?>
      <form id="zendesk-contact-form" method="post" action="<?php echo admin_url(); ?>">
        <input type="hidden" name="zendesk-form-submit" value="1"/>
        <input type="hidden" name="zendesk-form-context" value="create-ticket"/>

        <p>
          <label><?php echo $zendesk_support->settings['contact_form_summary']; ?></label>
          <input name="zendesk-form-data[summary]" class="large-text" type="text"/>
        </p>

        <p>
          <label><?php echo $zendesk_support->settings['contact_form_details']; ?></label>
          <textarea id="zendesk-contact-form-details" name="zendesk-form-data[details]" class="large-text"
                    style="height: 10em;"></textarea>
        </p>

        <p class="submit">
          <input name="Submit" type="submit" class="button-primary"
                 value="<?php echo esc_attr( trim( $zendesk_support->settings['contact_form_submit'] ) ); ?>"/>

          <?php if ( $zendesk_support->zendesk_user ): ?>
            <?php printf( __( 'Logged in as <strong>%s</strong>', 'zendesk' ), $zendesk_support->zendesk_user['username'] ); ?> (
            <a href="?zendesk-logout=true"><?php _e( 'logout', 'zendesk' ); ?></a>)
          <?php endif; ?>


          <a target="_blank" href="http://zendesk.com/?source=wordpress-plugin"
             class="powered-by-zendesk"><?php _e( 'powered by Zendesk', 'zendesk' ); ?></a>
        </p>
        <br class="clear"/>
      </form>
    </div>
  <?php
  }

  /*
   * Dashboard Widget: Tickets
   *
   * This method displays the tickets widget in the dashboard screen.
   * Depending on logged in user Zendesk role, the tickets or the
   * requests are shown, with an option to change view. Views are
   * gathered from Zendesk via the API. This view has also got the
   * place holder for single ticket views accessed via AJAX calls.
   *
   */
  public function _dashboard_widget_tickets() {
    global $zendesk_support;
    $agents = Zendesk_Wordpress_Agents::get_instance();
    ?>
    <div class="inside">
      <?php
      // API requests based on the Zendesk role.
      if ( $agents->_is_agent() ) {
        $tickets = $zendesk_support->api->get_tickets_from_view( (int) $zendesk_support->zendesk_user['default_view']['id'] );
        $views   = $zendesk_support->api->get_views();
      } else {
        $tickets = $zendesk_support->api->get_requests();
        $views   = array();
      }

      // Empty the arrays if they are errors.
      if ( is_wp_error( $views ) ) {
        $notices = Zendesk_Wordpress_Notices::get_instance();
        $notices->_add_notice( 'zendesk_tickets_widget', $views->get_error_message(), 'alert' );
        $views = array();
      }

      if ( is_wp_error( $tickets ) ) {
        $notices = Zendesk_Wordpress_Notices::get_instance();
        $notices->_add_notice( 'zendesk_tickets_widget', $tickets->get_error_message(), 'alert' );
        $tickets = array();
      }

      // Notifications
      $notices = Zendesk_Wordpress_Notices::get_instance();
      $notices->_do_notices( 'zendesk_login' );
      $notices->_do_notices( 'zendesk_tickets_widget' );
      ?>
    </div>
    <div class="zendesk-tickets-widget">

      <!-- Dashboard Widget Main View -->
      <div class="zendesk-tickets-widget-main">
        <?php echo Zendesk_Wordpress_Tickets::_get_tickets_widget_html( $tickets ); ?>
      </div>

      <!-- Dashboard Widget Select View -->
      <div class="zendesk-tickets-widget-views" style="display: none;">
        <p class="zendesk-heading"><?php _e( 'Change view', 'zendesk' ); ?> <span class="zendesk-heading-link">(<a
              class="zendesk-change-view-cancel"
              href="<?php echo admin_url(); ?>"><?php _e( 'cancel', 'zendesk' ); ?></a>)</span></p>
        <table class="zendesk-views-table">
          <?php
          if ( count( $views ) > 0 && is_array( $views ) ) {
            foreach ( $views as $view ) {
              ?>
              <tr>
                <td>
                  <?php if ( $view->active != 1 ) { ?>
                    <span class="zendesk-view-empty">
                <?php echo $view->title; ?>
              </span>
                  <?php } else { ?>
                    <a data-id="<?php echo $view->id; ?>"
                       href="<?php echo admin_url(); ?>?zendesk-tickets-change-view=<?php echo $view->id; ?>">
                      <?php echo $view->title; ?>
                    </a>
                  <?php } ?>
                </td>
              </tr>
            <?php
            }
          } else { // no views
            ?>
            <tr>
              <td><span
                  class="description"><?php _e( 'There are no views available for this account.', 'zendesk' ); ?></span>
              </td>
            </tr>
          <?php
          }
          ?>
        </table>
      </div>

      <!-- Dashboard Widget Single View -->
      <div class="zendesk-tickets-widget-single" style="display: none;">
        <p class="zendesk-heading"><?php _e( 'Viewing Ticket', 'zendesk' ); ?> <span id="zendesk-ticket-title"></span>
          <span class="zendesk-heading-link">(<a class="zendesk-change-single-cancel"
                                                 href="<?php echo admin_url(); ?>"><?php _e( 'back', 'zendesk' ); ?></a>)</span>
        </p>

        <div id="zendesk-ticket-details-placeholder"></div>
      </div>

      <!-- Dashboard Widget Bottom -->
      <br class="clear"/>

      <div class="zendesk-tickets-bottom">
        <p>
          <a target="_blank" href="<?php echo trailingslashit( $zendesk_support->zendesk_url ); ?>"
             class="button"><?php _e( 'My Helpdesk', 'zendesk' ); ?></a>
          <?php _e( 'Logged in as', 'zendesk' ); ?>
          <strong><?php echo $zendesk_support->zendesk_user['username']; ?></strong> (<a
            href="?zendesk-logout=true"><?php _e( 'logout', 'zendesk' ); ?></a>)
          <a target="_blank" href="http://zendesk.com/?source=wordpress-plugin"
             class="powered-by-zendesk"><?php _e( 'powered by Zendesk', 'zendesk' ); ?></a>
        </p>
      </div>

    </div>
    <br class="clear"/>
  <?php
  }

  /*
   * Get Current User Dashboard Widget (helper)
   *
   * Internal function, returns the current user's dashboard widget
   * settings based on his or her role and the plugin settings. The
   * returned string is 'tickets-widget', 'contact-form' or 'none'.
   *
   */
  public function _get_current_user_dashboard_widget() {
    global $zendesk_support;
    $role = $zendesk_support->_get_current_user_role();

    if ( array_key_exists( 'dashboard_' . $role, (array) $zendesk_support->settings ) ) {
      return $zendesk_support->settings[ 'dashboard_' . $role ];
    }

    return 'none';
  }
}
