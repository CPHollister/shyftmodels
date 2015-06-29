<?php

/*
 * The Zendesk Tickets Class
 *
 * This has the functions to handle zendesk tickets. We can get views for tickets, etc.
 *
 */

class Zendesk_Wordpress_Tickets {
  /*
   * Get Tickets Widget HTML (helper)
   *
   * This function returns the tickets table and current view as HTML.
   * Inteded to use inside the tickets view widget, passed on to the
   * AJAX responses that loads different views without refreshing.
   *
   */
  public static function _get_tickets_widget_html( $tickets ) {
    global $zendesk_support;
    $agents = Zendesk_Wordpress_Agents::get_instance();
    $html   = array();

    // Heading
    $html[] = '<p class="zendesk-heading">' . $zendesk_support->zendesk_user['default_view']['title'];
    if ( $agents->_is_agent() ) {
      $html[] = '<span class="zendesk-heading-link">(<a class="zendesk-change-view" href="#">' . __( 'change view', 'zendesk' ) . '</a>)</span>';
    }
    $html[] = '</p>';

    $html[] = '<table class="zendesk-tickets-table">';

    if ( count( $tickets ) > 0 && is_array( $tickets ) ) {
      foreach ( $tickets as $ticket ) {

        if ( ! strlen( $ticket->subject ) ) {
          $ticket->subject = Zendesk_Wordpress_Utilities::_excerpt( $ticket->description, 15 );
        }

        $html[] = '<tr>';
        $html[] = '<td class="zendesk-ticket-id"><div class="zendesk-loader" style="display: none"></div><a class="zendesk-ticket-id-text zendesk-ticket-view" data-id="' . $ticket->id . '" href="' . Zendesk_Wordpress_Utilities::_ticket_url( $ticket->id ) . '">#' . $ticket->id . '</a></td>';
        $html[] = '<td><a class="zendesk-ticket-view zendesk-ticket-subject" data-id="' . $ticket->id . '" href="' . Zendesk_Wordpress_Utilities::_ticket_url( $ticket->id ) . '">' . $ticket->subject . '</a></td>';
        $html[] = '<td class="zendesk-ticket-status"><a href="' . Zendesk_Wordpress_Utilities::_ticket_url( $ticket->id ) . '" target="_blank" class="zendesk-status-' . $ticket->status . '">' . $zendesk_support->_ticket_status( $ticket->status ) . '</a></td>';
        $html[] = '</tr>';
      }
    } else {
      $html[] = '<tr><td><span class="description">' . __( 'There are no tickets in this view.', 'zendesk' ) . '</span></td></tr>';
    }

    $html[] = '</table>';

    // Glue the HTML pieces and delimit with a line break
    return implode( "\n", $html );
  }

}
