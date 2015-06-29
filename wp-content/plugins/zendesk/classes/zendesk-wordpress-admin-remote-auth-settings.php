<?php

/*
 * The Zendesk Remote Auth Admin Settings Class
 *
 * This has all the methods to display the settings in our admin page for the remote auth.
 *
 */

class Zendesk_Wordpress_Admin_Remote_Auth_Settings {
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
 * Settings Section: Remote Auth General
 *
 */
  public function _settings_remote_auth_section_general() {
    _e( 'The general remote authentication settings', 'zendesk' );
  }

  /*
   * Get Available Dashboard Widget Options (helper)
   *
   * Returns an array with the available dashboard widget options,
   * where the array key is stored in the database and the array
   * value is displayed (thus localized) to the user.
   *
   */
  private function _remote_auth_strategy_options() {
    return array(
      'jwt' => __( "JSON Web Token (Recommend)", 'zendesk' ),
    );
  }

  /*
   * Settings Remote Auth: Strategy
   *
   */
  public function _settings_remote_auth_strategy() {
    global $zendesk_support;

    $current_strategy    = &$zendesk_support->remote_auth_settings['strategy'];
    $remote_auth_enabled = &$zendesk_support->remote_auth_settings['enabled'];

    if ( ! $current_strategy && $remote_auth_enabled ) {
      $current_strategy = 'classic';
    } else if ( ! $current_strategy && ! $remote_auth_enabled ) {
      $current_strategy = 'jwt';
    }

    ?>
    <select name="zendesk-settings-remote-auth[strategy]">
      <?php foreach ( $this->_remote_auth_strategy_options() as $value => $caption ): ?>
        <option <?php selected( $value == $current_strategy ); ?>
          value="<?php echo $value; ?>"><?php echo $caption; ?></option>
      <?php endforeach; ?>
    </select>
  <?php
  }

  /*
   * Settings Remote Auth: Enabled
   *
   * This simply says whether remote authentication is enabled or not,
   * used to be a checkbox, but that is now handled in the remote
   * auth validation section.
   *
   */
  public function _settings_field_remote_auth_enabled() {
    global $zendesk_support;

    $remote_auth = (bool) $zendesk_support->remote_auth_settings['enabled'];
    ?>
    <span class="description">
        <?php if ( $remote_auth ): ?>
          <strong><?php _e( 'Remote authentication is enabled', 'zendesk' ); ?></strong>
        <?php else: ?>
          <strong><?php _e( 'Remote authentication is <strong>disabled</strong>', 'zendesk' ); ?></strong>
        <?php endif; ?>

      <br/><?php _e( 'To activate remote authentication, ensure a shared token <br /> is entered below and click &quot;Save Changes&quot;', 'zendesk' ); ?>

      </span>
  <?php
  }

  /*
   * Settings Remote Auth: Shared Token
   *
   * Shared token is the shared secret located under the single sign-on
   * settings on the Zendesk Account Security page. We ask for that
   * token right here.
   *
   */
  public function _settings_field_remote_auth_token() {
    global $zendesk_support;
    ?>
    <input type="text" class="regular-text" name="zendesk-settings-remote-auth[token]"
           value="<?php echo $zendesk_support->remote_auth_settings['token']; ?>"/><br/>
    <span class="description">
      <?php printf( __( 'Your shared token could be obtained on the %s in the <br /> Single Sign-On section.', 'zendesk' ), sprintf( '<a target="_blank" href="' . trailingslashit( $zendesk_support->zendesk_url ) . 'settings/security">%s</a>', __( 'Account Security page', 'zendesk' ) ) ); ?>
      <br/><br/>
      <?php printf( __( '<strong>Remember</strong> that you can always go to: <br /> %s to use the regular login <br /> in case you get unlucky and somehow lock yourself out of Zendesk.', 'zendesk' ), '<a target="_blank" href="' . trailingslashit( $zendesk_support->zendesk_url ) . 'access/normal' . '">' . trailingslashit( $zendesk_support->zendesk_url ) . 'access/normal' . '</a>' ); ?>
    </span>
  <?php
  }

  /*
   * Settings Section: Remote Auth for Zendesk
   *
   */
  public function _settings_remote_auth_section_zendesk() {
    _e( 'The settings that need to be configured in your Zendesk account.', 'zendesk' );
  }

  /*
   * Settings Field: Remote Auth Login URL
   *
   * Displays the login URL for the Zendesk remote auth settings.
   *
   */
  public function _settings_field_remote_auth_login_url() {
    echo '<code>' . wp_login_url() . '?action=zendesk-remote-login' . '</code>';
  }

  /*
   * Settings Field: Remote Auth Logout URL
   *
   * Same as above but displays the logout URL.
   *
   */
  public function _settings_field_remote_auth_logout_url() {
    echo '<code>' . wp_login_url() . '?action=zendesk-remote-logout' . '</code>';
  }

  /*
   * Remote Auth Settings Validation
   *
   * Validates remote authentication settings submitted through the
   * settings page. Not too much settings here, nothing to validate.
   * Accessible through $this->remote_auth_settings
   *
   */
  public function _validate_remote_auth_settings( $settings ) {
    $settings['enabled'] = empty( $settings['token'] ) ? false : true;

    return $settings;
  }

}