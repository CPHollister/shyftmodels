<?php

/*
 * The Zendesk Agents Class
 *
 * This has all the helper methods to use when dealing with zendesk agents
 *
 */

class Zendesk_Wordpress_Agents {
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
   * Helper: Is Agent
   *
   * A conditional function that returns true if the current or
   * specified Zendesk user is an agent, otherwise returns false,
   * meaning that it's probably an end-user.
   *
   */
  public function _is_agent( $user_ID = false ) {
    global $zendesk_support;

    // Current user or a specific user ID.
    $zendesk_user = $user_ID ? get_user_meta( $user_ID, 'zendesk_user_options', true ) : $zendesk_support->zendesk_user;

    if ( isset( $zendesk_user['role'] ) && strcmp( $zendesk_user['role'], 'end-user' ) != 0 ) {
      return true;
    } else {
      return false;
    }
  }

  /*
   * Helper: Get Zendesk_Wordpress_Agents
   *
   * Scans the WordPress database for all users that are authenticated with
   * Zendesk and whose roles are agents.
   *
   */
  public function _get_agents() {
    // 20150430-MM Before this we were retrieving all the users of the blog, to
    // then loop trough the results and check if each of them _is_agent()
    // This could create memory problems for blogs with thousands of users, so
    // now we retrieve only users that we know for sure are authenticated with
    // Zendesk, using the Zendesk user options key as a filter
    $args  = array(
      'blog_id'      => get_current_blog_id(),
      'meta_key'     => 'zendesk_user_options',
      'meta_value'   => null,
      'meta_compare' => '!=',
    );
    $users = get_users( $args );

    $data = array();

    foreach ( $users as $user ) {
      if ( $this->_is_agent( $user->ID ) ) {
        $data[] = get_userdata( $user->ID );
      }
    }

    return $data;
  }

  /*
   * Helper: Get Agent
   *
   * Returns the Zendesk user options for the requested user ID.
   *
   */
  public function _get_agent( $user_ID ) {
    if ( ! $this->_is_agent( $user_ID ) ) {
      return false;
    }

    return get_user_meta( $user_ID, 'zendesk_user_options', true );
  }

}
