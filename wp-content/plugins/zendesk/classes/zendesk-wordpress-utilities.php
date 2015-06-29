<?php

/*
 * The Zendesk Utilities Class
 *
 * This has all miscellaneous helper methods. Normally to get urls or to manipulate strings.
 *
 */

class Zendesk_Wordpress_Utilities {
  /*
   * Helper: Zendesk Ticket URL
   *
   * Returns the URL to the Zendesk ticket given the ticket ID.
   *
   */
  public static function _ticket_url( $ticket_id ) {
    global $zendesk_support;

    return trailingslashit( $zendesk_support->zendesk_url ) . 'tickets/' . $ticket_id;
  }

  /*
   * Helper: Zendesk User URL
   *
   * Returns the URL to the Zendesk user profile given the user ID.
   *
   */
  public static function _user_url( $user_id ) {
    global $zendesk_support;

    return trailingslashit( $zendesk_support->zendesk_url ) . 'users/' . $user_id;
  }

  /*
   * Helper: Custom Excerpt
   *
   * Create an excerpt of any string given the string and the number
   * of words to truncate to, default is 50.
   *
   */
  public static function _excerpt( $string, $words = 50 ) {
    $blah   = explode( ' ', $string );
    $return = '';
    if ( count( $blah ) > $words ) {
      for ( $i = 0; $i < $words; $i ++ ) {
        $return .= $blah[ $i ] . ' ';
      }

      $return .= '...';

      return $return;
    } else {
      return $string;
    }
  }

  /*
   * Helper: File Size
   *
   * Used to display the sizes of the attachments in Zendesk comments.
   *
   */
  public static function _file_size( $bytes ) {
    $filesizename = array( " bytes", " kb", " mb", " gb", " tb", " pb", " eb", " zb", " yb" );

    return $bytes ? round( $bytes / pow( 1024, ( $i = floor( log( $bytes, 1024 ) ) ) ), 2 ) . $filesizename[ $i ] : '0 bytes';
  }

}
