<?php

/*
 * The Zendesk Admin Settings Class
 *
 * This has all the methods to display the settings in our admin page
 *
 */

class Zendesk_Wordpress_Admin_Settings {
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
   * Settings: Authentication Section
   *
   * Outputs the description for the authentication settings registered
   * during admin_init, displayed underneath the section title, which
   * is defined during section registration.
   *
   */

  public function _settings_section_authentication() {
    _e( "We need your Zendesk subdomain, so we can use Zendesk's API to get your ticket information.", 'zendesk' );
  }

  /*
   * Settings: Account Field
   *
   * Field for $this->settings['account'] -- simply the account name,
   * without any http or zendesk.com prefixes and postfixes. Validated
   * together with all the other options.
   *
   */
  public function _settings_field_account() {
    global $zendesk_support;
    ?>
    <?php if ( ! $zendesk_support->settings['account'] ): ?>
      <strong>http://<input type="text" style="width: 120px;" class="regular-text" id="zendesk_account"
                            name="zendesk-settings[account]"
                            value="<?php echo $zendesk_support->settings["account"]; ?>"/>.zendesk.com</strong> <br/>
      <span class="description">Even if you have host mapping, please use your subdomain here.<br/>
        We will automatically detect if you use SSL or not.</span>
    <?php else: ?>
      http://<input type="text" style="width: 120px; display: none;" class="regular-text" id="zendesk_account"
                    name="zendesk-settings[account]" value="<?php echo $zendesk_support->settings["account"]; ?>"/>
      <strong id="zendesk_account_string"><?php echo $zendesk_support->settings['account']; ?></strong>.zendesk.com<br/>
      <span class="description">
      <a id="zendesk_account_change" href="#"><?php _e( 'Click here to change your subdomain', 'zendesk' ); ?></a>
      </span>
    <?php endif; ?>
  <?php
  }

  /*
   * Settings: SSL Field
   *
   * Boolean field for $this->settings['ssl'] -- switches on or off
   * SSL access to the Zendesk servers.
   *
   */
  public function _settings_field_ssl() {
    global $zendesk_support;
    $ssl = (bool) $zendesk_support->settings['ssl'];
    ?>
    <?php if ( $ssl ): ?>
      <span class="description"><?php _e( 'Your account is using SSL', 'zendesk' ); ?></span>
    <?php else: ?>
      <span class="description"><?php _e( 'Your account is <strong>not</strong> using SSL', 'zendesk' ); ?></span>
    <?php endif; ?>
  <?php
  }

  /*
   * Settings: Dashboard Widget Section
   *
   * Outputs the description for the Dashboard Widget section, which
   * appears underneath the section title.
   *
   */
  public function _settings_section_dashboard_widget() {
    _e( "The Dashboard Widget can be changed depending on a User's capabilitites.", 'zendesk' );
  }

  /*
   * Settings: Dashboard Widget Access
   *
   * This function is used to output several different options fields,
   * which is why there's an $args input array which generally contains
   * one key called 'role' with a value listed in the array below. Works
   * well for Administrator, Editor, Author, Contributor and Subscriber.
   *
   * @uses $this->_available_dashboard_widget_options()
   *
   */
  public function _settings_field_dashboard_access( $args ) {
    global $zendesk_support;
    if ( ! isset( $args['role'] ) || ! in_array( $args['role'], array(
        'administrator',
        'editor',
        'author',
        'contributor',
        'subscriber'
      ) )
    ) {
      return;
    }
    $role = $args['role'];
    ?>
    <select name="zendesk-settings[dashboard_<?php echo $role; ?>]" id="zendesk_dashboard_<?php echo $role; ?>">
      <?php foreach ( $this->_available_dashboard_widget_options() as $value => $caption ): ?>
        <option <?php selected( $value == $zendesk_support->settings[ 'dashboard_' . $role ] ); ?>
          value="<?php echo $value; ?>"><?php echo $caption; ?></option>
      <?php endforeach; ?>
    </select>
  <?php
  }

  /*
   * Settings: Contact Form Section
   *
   * Outputs the contact form section description, appears underneath
   * the section heading
   *
   */
  public function _settings_section_contact_form() {
    _e( 'The contact form is a way for users to submit support requests. It can be added to the dashboard using the options above.', 'zendesk' );
  }

  /*
   * Settings: Contact Form Title
   *
   * The title of the contact form dashboard widget, accessible via
   * $this->settings['contact_form_title']
   *
   */
  public function _settings_field_contact_form_title() {
    global $zendesk_support;
    $value = $zendesk_support->_is_default( 'contact_form_title' ) ? '' : $zendesk_support->settings['contact_form_title'];
    ?>
    <input type="text" class="regular-text" name="zendesk-settings[contact_form_title]" value="<?php echo $value; ?>"
           placeholder="<?php echo $zendesk_support->default_settings['contact_form_title']; ?>"/>
  <?php
  }

  /*
   * Settings: Contact Form Summary Label
   *
   * The Summary label text in the contact form dashboard widget,
   * accessible via $this->settings['contact_form_summary']
   *
   */
  public function _settings_field_contact_form_summary() {
    global $zendesk_support;
    $value = $zendesk_support->_is_default( 'contact_form_summary' ) ? '' : $zendesk_support->settings['contact_form_summary'];
    ?>
    <input type="text" class="regular-text" name="zendesk-settings[contact_form_summary]" value="<?php echo $value; ?>"
           placeholder="<?php echo $zendesk_support->default_settings['contact_form_summary']; ?>"/>
  <?php
  }

  /*
   * Settings: Contact From Details Label
   *
   * The Details label text in the contact form dashboard widget,
   * accessible via $this->settings['contact_form_details']
   *
   */
  public function _settings_field_contact_form_details() {
    global $zendesk_support;
    $value = $zendesk_support->_is_default( 'contact_form_details' ) ? '' : $zendesk_support->settings['contact_form_details'];
    ?>
    <input type="text" class="regular-text" name="zendesk-settings[contact_form_details]" value="<?php echo $value; ?>"
           placeholder="<?php echo $zendesk_support->default_settings['contact_form_details']; ?>"/>
  <?php
  }

  /*
   * Settings: Contact Form Submit Label
   *
   * The caption of the submit button in the contact form dashboard
   * widget, accessible via $this->settings['contact_form_submit']
   * Escape when printing.
   *
   */
  public function _settings_field_contact_form_submit() {
    global $zendesk_support;
    $value = $zendesk_support->_is_default( 'contact_form_submit' ) ? '' : $zendesk_support->settings['contact_form_submit'];
    ?>
    <input type="text" class="regular-text" name="zendesk-settings[contact_form_submit]" value="<?php echo $value; ?>"
           placeholder="<?php echo $zendesk_support->default_settings['contact_form_submit']; ?>"/>
  <?php
  }

  /*
   * Settings Field: Contact Form Anonymous Status
   *
   * This says whether anonymous tickets submissions through the
   * contact form widget are allowed or not. The field below appears
   * only when this is active (via javascript of course)
   *
   */
  public function _settings_field_contact_form_anonymous() {
    global $zendesk_support;
    ?>
    <input id="zendesk_contact_form_anonymous" type="checkbox" name="zendesk-settings[contact_form_anonymous]"
           value="1" <?php checked( (bool) $zendesk_support->settings['contact_form_anonymous'] ); ?> />
    <label
      for="zendesk_contact_form_anonymous"><?php _e( 'Check this to allow users without Zendesk accounts to submit requests.', 'zendesk' ); ?></label>
    <br/>
    <span
      class="description"><?php _e( 'If disabled, users will need to login to Zendesk to submit requests.', 'zendesk' ); ?></span>
  <?php
  }

  /*
   * Settings Field: Contact Form Anonymous User
   *
   * This is the user via whom the requests are fired when the
   * anonymous contact form is enabled. A select box is given with
   * a list of agents and the current user.
   *
   */
  public function _settings_field_contact_form_anonymous_user() {
    global $zendesk_support;
    $agents = Zendesk_Wordpress_Agents::get_instance();

    // Fetch the agents
    $users = $agents->_get_agents();

    // Let's see if the current user *is* an agent.
    $contains_current_user = false;
    foreach ( $users as $user ) {
      if ( $user->ID == $zendesk_support->user->ID ) {
        $contains_current_user = true;
        break;
      }
    }

    // If the current user's not an agent append them to the beginning of the list.
    if ( ! $contains_current_user ) {
      array_unshift( $users, $zendesk_support->user );
    }

    ?>
    <select id="zendesk_contact_form_anonymous_user" name="zendesk-settings[contact_form_anonymous_user]">
      <?php foreach ( $users as $user ): ?>
        <option <?php selected( $user->ID == $zendesk_support->settings['contact_form_anonymous_user'] ); ?>
          value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?> (<?php echo $user->user_email; ?>)
        </option>
      <?php endforeach; ?>
    </select><br/>
    <span class="description">
      <?php _e( 'Contact form submissions will be done "via" this agent, through the Zendesk API. <br /> This agent must be authenticated into Zendesk via the Wordpress for Zendesk widget.<br /> Agents not authenticated via the dashboard widget are not shown here.', 'zendesk' ); ?>
      <br/><a target="_blank"
              href="https://support.zendesk.com/entries/20116518-setting-up-anonymous-ticket-submissions-with-zendesk-for-wordpress"><?php _e( 'Learn more at Zendesk.com', 'zendesk' ); ?></a>
    </span>
  <?php
  }

  /*
   * Settings: Dropbox Section
   *
   */
  public function _settings_section_dropbox() {
    _e( 'The Zendesk Dropbox places a convenient tab on your pages that allow your visitors to contact you via a pop-up form.', 'zendesk' );
  }

  /*
   * Settings: Dropbox Display
   *
   * Boolean value which turns on or off the Zendesk Dropbox. This
   * value is checked when registering Dropbox scritps, styles and
   * code. Accessed from $this->settings['dropbox_display']
   *
   */
  public function _settings_field_dropbox_display() {
    global $zendesk_support;
    ?>
    <select name="zendesk-settings[dropbox_display]" id="zendesk_dropbox_display">
      <option
        value="none" <?php selected( $zendesk_support->settings['dropbox_display'] == 'none' ); ?> ><?php _e( 'Do not display the Zendesk dropbox anywhere', 'zendesk' ); ?></option>
      <option
        value="auto" <?php selected( $zendesk_support->settings['dropbox_display'] == 'auto' ); ?> ><?php _e( 'Display the Zendesk dropbox on all posts and pages', 'zendesk' ); ?></option>
      <option
        value="manual" <?php selected( $zendesk_support->settings['dropbox_display'] == 'manual' ); ?> ><?php _e( 'I will decide where the Zendesk dropbox displays using a template tag', 'zendesk' ); ?></option>
    </select>

  <?php
  }

  /*
   * Settings: Dropbox Code
   *
   * A text area to stick in the dropbox code which is printed
   * during the wp_footer action in the theme if the dropbox display
   * setting is set to true. Access via $this->settings['dropbox_code']
   *
   */
  public function _settings_field_dropbox_code() {
    global $zendesk_support;
    ?>
    <span
      class="description float-left"><strong><?php printf( __( 'Obtain your Dropbox code from the %s in your Zendesk.', 'zendesk' ), sprintf( '<a target="_blank" href="' . trailingslashit( $zendesk_support->zendesk_url ) . 'account/dropboxes/new">%s</a>', __( 'Dropbox Configuration page', 'zendesk' ) ) ); ?></strong></span>
    <br/>
    <textarea id="zendesk_dropbox_code" cols="60" rows="5"
              name="zendesk-settings[dropbox_code]"><?php echo esc_textarea( $zendesk_support->settings['dropbox_code'] ); ?></textarea>
    <br/>
  <?php
  }

  /*
   * Settings: Web Widget Section
   *
   */
  public function _settings_section_webwidget() {
    global $zendesk_support;
    _e( 'The Zendesk Web Widget makes it easy for your customers to get the help they need, wherever they are on your website, with one click or tap.', 'zendesk' );
    ?>
    <br/>
    <?php printf( __( 'Activate your widget and access settings on the %s in your Zendesk.', 'zendesk' ), sprintf( '<a target="_blank" href="' . trailingslashit( $zendesk_support->zendesk_url ) . 'agent/admin/widget">%s</a>', __( 'Widget Configuration page', 'zendesk' ) ) ); ?>
    <br/>
    <strong> <?php _e( 'Note:', 'zendesk' ); ?> </strong>
    <?php
    _e( 'You\'ll need to visit this page initially to set up your widget.' );
  }

  /*
   * Settings: Web Widget Display
   *
   * Boolean value which turns on or off the Zendesk Web Widget. This
   * value is checked when registering Web Widget scripts, styles and
   * code. Accessed from $this->settings['webwidget_display']
   *
   */
  public function _settings_field_webwidget_display() {
    global $zendesk_support;
    ?>
    <select name="zendesk-settings[webwidget_display]" id="zendesk_webwidget_display">
      <option
        value="none" <?php selected( $zendesk_support->settings['webwidget_display'] == 'none' ); ?> ><?php _e( 'Do not display the Zendesk Widget anywhere', 'zendesk' ); ?></option>
      <option
        value="auto" <?php selected( $zendesk_support->settings['webwidget_display'] == 'auto' ); ?> ><?php _e( 'Display the Zendesk Widget on all posts and pages', 'zendesk' ); ?></option>
      <option
        value="manual" <?php selected( $zendesk_support->settings['webwidget_display'] == 'manual' ); ?> ><?php _e( 'I will decide where the Zendesk Widget displays using a template tag', 'zendesk' ); ?></option>
    </select>

  <?php
  }

  /*
   * Settings: Web Widget Code
   *
   * A text area to stick in the web widget code which is printed
   * during the wp_footer action in the theme if the webwidget display
   * setting is set to true. Access via $this->settings['webwidget_code']
   *
   */
  public function _settings_field_webwidget_code() {
    global $zendesk_support;
    ?>
    <span
      class="description float-left"><strong><?php _e( 'Advanced users only (no need to modify code below)', 'zendesk' ); ?></strong></span>
    <br/>
    <textarea id="zendesk_webwidget_code" cols="60" rows="5"
              name="zendesk-settings[webwidget_code]"><?php echo esc_textarea( $zendesk_support->settings['webwidget_code'] ); ?></textarea>
    <br/>
  <?php
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
  public function _available_dashboard_widget_options() {
    return array(
      'none'           => __( "Don't display anything", 'zendesk' ),
      'contact-form'   => __( 'Show a Contact Form', 'zendesk' ),
      'tickets-widget' => __( 'Show the Tickets widget', 'zendesk' ),
    );
  }

  /*
   * Settings Validation
   *
   * Validates all the incoming settings, generally submitted from
   * the Zendesk Settings admin page. Check, sanitize, strip and
   * return. The returning array is stored in the database and then
   * accessible through $this->settings.
   *
   */
  public function _validate_settings( $settings ) {
    global $zendesk_support;

    // Check for SSL activity and keep the version.
    $settings['ssl']     = $zendesk_support->api->is_ssl( $settings['account'] );
    $settings['version'] = $zendesk_support->default_settings['version'];

    // Validate the Zendesk Account
    if ( ( ! preg_match( '/^[a-zA-Z0-9][a-zA-Z0-9\-]{0,}[a-zA-Z0-9]$/', $settings['account'] ) && ! preg_match( '/^[a-zA-Z0-9]{1}$/', $settings['account'] ) ) || ( strlen( $settings['account'] ) > 63 ) ) {
      unset( $settings['account'] );
    }

    // Dashboard widgets visibility
    foreach ( array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ) as $role ) {
      if ( isset( $settings[ 'dashboard_' . $role ] ) && ! array_key_exists( $settings[ 'dashboard_' . $role ], $this->_available_dashboard_widget_options() ) ) {
        unset( $settings[ 'dashboard_' . $role ] );
      }
    }

    // Clean up contact form title and others
    foreach (
      array(
        'contact_form_title',
        'contact_form_summary',
        'contact_form_details',
        'contact_form_submit'
      ) as $key
    ) {
      $settings[ $key ] = empty( $settings[ $key ] ) ? $zendesk_support->default_settings[ $key ] : htmlspecialchars( trim( $settings[ $key ] ) );
    }

    // Anonymous contact form (checkbox)
    if ( ! isset( $settings['contact_form_anonymous'] ) ) {
      $settings['contact_form_anonymous'] = false;
    }


    // Nuke login credentials and web widget snippet if account has changed.
    if ( $settings['account'] !== $zendesk_support->settings['account'] ) {
      // Running a direct SQL query is *way* faster than meta querying users one by one.
      global $wpdb;
      $wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key = 'zendesk_user_options';" );

      // Clear the web widget code so it gets generated again using the new account
      $settings['webwidget_code'] = '';
    }

    // If the Web Widget has just been switched on, hide the Feedback Tab
    if ( $settings['webwidget_display'] != 'none' && $zendesk_support->settings['webwidget_display'] == 'none' ) {
      $settings['dropbox_display'] = 'none';
    }

    // Merge the submitted settings with the defaults. Second
    // argument will overwrite the first.
    if ( is_array( $zendesk_support->settings ) ) {
      $settings = array_merge( $zendesk_support->settings, $settings );
    } else {
      $settings = array_merge( $zendesk_support->default_settings, $settings );
    }

    return $settings;
  }
}
