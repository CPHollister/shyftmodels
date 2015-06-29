<?php

require_once( plugin_dir_path( __FILE__ ) . 'zendesk-wordpress-dashboard-widget.php' );
require_once( plugin_dir_path( __FILE__ ) . 'zendesk-wordpress-agents.php' );
require_once( plugin_dir_path( __FILE__ ) . 'zendesk-wordpress-utilities.php' );
require_once( plugin_dir_path( __FILE__ ) . 'zendesk-wordpress-logger.php' );
require_once( plugin_dir_path( __FILE__ ) . 'zendesk-wordpress-tickets.php' );

/*
 * The Zendesk Ajax Class
 *
 * Handles all the ajax calls to the plugin. Some examples are viewing tickets
 * and converting of comments into tickets. This responds in json format, thus
 * requires the json functions available in php5 (and php4 as a pear
 * library).
 *
 * @uses json_encode
 */

class Zendesk_Wordpress_Ajax {

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
   * AJAX Response: Convert to Ticket
   *
   * This request responds when attempting to convert a comment into
   * a ticket. Does not do any conversions, nor requests to the API,
   * simply passes in the current user and the requested comment. The
   * function below does the rest.
   *
   */
  public function _ajax_convert_to_ticket() {
    global $zendesk_support;
    $agents = Zendesk_Wordpress_Agents::get_instance();
    if ( isset( $_REQUEST['comment_id'] ) && is_numeric( $_REQUEST['comment_id'] ) && $agents->_is_agent() ) {
      $comment_id = $_REQUEST['comment_id'];
      $comment    = get_comment( $comment_id );

      // Comment found
      if ( $comment ) {
        $html = array();

        $html[] = '<div class="zendesk-comment-to-ticket">';

        $html[] = get_avatar( $comment->comment_author_email, 40 );
        $html[] = '<div class="zendesk-comment-box">';
        $html[] = '<div class="zendesk-comment-arrow"></div>';
        $html[] = '<p class="zendesk-author">' . sprintf( __( '<strong>%s</strong> said...', 'zendesk' ), $comment->comment_author ) . '</p>';
        $html[] = wpautop( Zendesk_Wordpress_Utilities::_excerpt( strip_tags( $comment->comment_content ), 70 ) );
        $html[] = '<p class="zendesk-comment-date">' . date( get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' ), strtotime( $comment->comment_date ) ) . '</p>';
        $html[] = '</div>';

        $html[] = '<br class="clear" />';
        $html[] = '<p class="zendesk-after-comment-box">' . __( 'A new ticket will be created inside your Zendesk account, and your response below will be added as a comment to that ticket.', 'zendesk' ) . '</p>';

        $html[] = get_avatar( $zendesk_support->zendesk_user['username'], 40 );
        $html[] = '<div class="zendesk-comment-box">';
        $html[] = '<div class="zendesk-comment-arrow"></div>';
        $html[] = '<p class="zendesk-author">' . __( '<strong>You</strong> say:', 'zendesk' ) . '</p>';
        $html[] = '<form class="zendesk-comment-to-ticket-form">';
        $html[] = '<textarea name="zendesk-comment-reply" class="zendesk-comment-reply"></textarea>';
        $html[] = '<br class="clear" />';
        $html[] = '<div class="zendesk-options">';
        $html[] = '<label><input name="zendesk-comment-public" value="1" checked="checked" type="checkbox" /> ' . __( 'Make this a public comment in the ticket', 'zendesk' ) . '</label>';
        $html[] = '<label><input name="zendesk-post-reply" value="1" type="checkbox" /> ' . __( 'Post as a reply on this blog post', 'zendesk' ) . '</label>';
        $html[] = '</div>';
        $html[] = '<input type="hidden" name="zendesk-comment-id" value="' . $comment->comment_ID . '" />';
        $html[] = '<input type="submit" class="button-primary zendesk-submit" value="' . __( 'Create ticket', 'zendesk' ) . '" /><div class="zendesk-loader" style="display: none;">loading</div>';
        $html[] = '<br class="clear" /><div class="zendesk-notices"></div>';
        $html[] = '</form>';
        $html[] = '</div>';

        $html[] = '</div>';
        $html[] = '<br class="clear" />';

        $html = implode( "\n", $html );

        $response = array(
          'status' => 200,
          'html'   => $html
        );
      }
    }

    echo json_encode( $response );
    die();
  }

  /*
   * AJAX Response: Convert to Ticket POST
   *
   * This requests responds upon the actual posting of the comments
   * to tickets integration, i.e. when the agent has typed a response
   * message and clicked the Create ticket button. The whole logics of
   * creating the ticket, attaching a comment to the ticket (private,
   * or public), associating a WordPress comment with the ticket and
   * posting back a WordPress comment as a reply happens here.
   *
   */
  public function _ajax_convert_to_ticket_post() {
    global $zendesk_support;
    $agents = Zendesk_Wordpress_Agents::get_instance();

    // If a different response is not set use this one.
    $response = array(
      'status' => 500,
      'error'  => __( 'Whoopsie! Problem communicating with Zendesk. Try that again.', 'zendesk' )
    );

    // Some validation
    if ( isset( $_REQUEST['comment_id'] ) && is_numeric( $_REQUEST['comment_id'] ) && $agents->_is_agent() ) {
      $comment_id = $_REQUEST['comment_id'];
      $comment    = get_comment( $comment_id );

      // Make sure it's a valid comment
      if ( $comment && $comment->comment_type != 'pingback' ) {

        // Fetch the associated post
        $post = get_post( $comment->comment_post_ID );

        // Fetch the incoming data
        $message        = trim( stripslashes( $_REQUEST['message'] ) );
        $comment_public = isset( $_REQUEST['comment_public'] ) ? true : false;
        $post_reply     = isset( $_REQUEST['post_reply'] ) ? true : false;

        // Let's format the new ticket
        $subject         = $post->post_title . ': ' . Zendesk_Wordpress_Utilities::_excerpt( strip_tags( $comment->comment_content ), 5 );
        $description     = strip_tags( $comment->comment_content );
        $requester_name  = $comment->comment_author;
        $requester_email = $comment->comment_author_email;

        // Create the ticket
        $ticket_id = $zendesk_support->api->create_ticket( $subject, $description, $requester_name, $requester_email );

        if ( ! is_wp_error( $ticket_id ) ) {

          // Ticket went okay so update the comment meta to associated it.
          update_comment_meta( $comment->comment_ID, 'zendesk-ticket', $ticket_id );

          // If we have a message set
          if ( strlen( $message ) ) {

            // Post a comment to the ticket
            $ticket_comment = $zendesk_support->api->create_comment( $ticket_id, $message, $comment_public );

            if ( ! is_wp_error( $ticket_comment ) ) {

              // Let's see if we need to post a comment back to WordPress.
              if ( $post_reply ) {
                $wp_comment = array(
                  'comment_post_ID'      => $post->ID,
                  'comment_author'       => $zendesk_support->user->display_name,
                  'comment_author_email' => $zendesk_support->user->user_email,
                  'comment_content'      => $message,
                  'comment_parent'       => $comment_id,
                  'user_id'              => $zendesk_support->user->ID,
                  'comment_date'         => current_time( 'mysql' ),
                  'comment_approved'     => 1
                );
                wp_insert_comment( $wp_comment );
              }

              $response = array(
                'status'     => 200,
                'ticket_id'  => $ticket_id,
                'ticket_url' => Zendesk_Wordpress_Utilities::_ticket_url( $ticket_id )
              );

            } else {

              // The ticket was created but the comment didn't get through.
              $response = array(
                'status' => 500,
                'error'  => __( 'A ticket has been created, but failed to post a comment to it.', 'zendesk' )
              );
            }
          } else {

            // A message is not set but the ticket was created.
            $response = array(
              'status'     => 200,
              'ticket_id'  => $ticket_id,
              'ticket_url' => Zendesk_Wordpress_Utilities::_ticket_url( $ticket_id )
            );

          }

        } else {

          // Failed to create the ticket.
          $response = array(
            'status' => 500,
            'error'  => $ticket_id->get_error_message()
          );
        }
      }
    }

    // Return the response JSON
    echo json_encode( $response );
    die();
  }

  /*
   * AJAX Response: View Ticket Comments
   *
   * This is an AJAX response to the zendesk_view_comments request which
   * displays a colorbox with the ticket comments. This is available
   * to agents only.
   *
   */
  public function _ajax_view_comments() {
    global $zendesk_support;
    $agents = Zendesk_Wordpress_Agents::get_instance();
    if ( isset( $_REQUEST['ticket_id'] ) && is_numeric( $_REQUEST['ticket_id'] ) && $agents->_is_agent() ) {
      $ticket_id = $_REQUEST['ticket_id'];

      $comments = $zendesk_support->api->get_comments( $ticket_id );

      if ( ! is_wp_error( $comments ) ) {

        $html   = array();
        $html[] = '<div class="zendesk-comment-to-ticket">';

        foreach ( $comments as $comment ) {

          $author = $zendesk_support->api->get_user( $comment->author_id );

          if ( is_wp_error( $author ) ) {
            $author        = null;
            $author->name  = 'Unknown';
            $author->email = 'unknown@zendesk.com';
          } else {
            $author = $author->user;
          }

          $html[] = '<a target="_blank" href="' . Zendesk_Wordpress_Utilities::_user_url( $comment->author_id ) . '">' . get_avatar( $author->email, 40 ) . '</a>';
          $html[] = '<div class="zendesk-comment-box">';
          $html[] = '<div class="zendesk-comment-arrow"></div>';
          $html[] = '<p class="zendesk-author">' . sprintf( __( '%s said...', 'zendesk' ), '<a target="_blank" href="' . Zendesk_Wordpress_Utilities::_user_url( $comment->author_id ) . '">' . $author->name . '</a>' ) . '</p>';
          $html[] = wpautop( $comment->body );

          // Let's see if we have any attachments there.
          if ( isset( $comment->attachments ) && count( $comment->attachments ) ) {
            $html[] = '<div class="zendesk-comment-attachments">';

            foreach ( $comment->attachments as $attachment ) {
              $html[] = '<p class="zendesk-comment-attachment"><a target="_blank" href="' . $attachment->url . '">' . $attachment->file_name . '</a> <span class="zendesk-attachment-size">(' . Zendesk_Wordpress_Utilities::_file_size( $attachment->size ) . ')</span></p>';
            }

            $html[] = '</div>';
          }

          $html[] = '<p class="zendesk-comment-date">' . date( get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' ), strtotime( $comment->created_at ) ) . '</p>';
          $html[] = '</div>';

          $html[] = '<br class="clear" />';

        }

        $html[] = '</div>';
        $html[] = '<br class="clear" />';

        $html = implode( "\n", $html );

        $response = array(
          'status' => 200,
          'html'   => $html
        );
      } else {
        $error_data = $comments->get_error_data();

        $response = array(
          'status' => $error_data['status'],
          'error'  => $comments->get_error_message()
        );
      }
    }

    echo json_encode( $response );
    die();
  }

  /*
   * AJAX Response: Get View
   *
   * This method is fired by WordPress wehn requesting via the AJAX
   * API and the zendesk_get_view action is set. Gathers the view
   * into an HTML table and outputs as a JSON response.
   *
   */
  public function _ajax_get_view() {
    global $zendesk_support;
    $agents = Zendesk_Wordpress_Agents::get_instance();
    if ( isset( $_REQUEST['view_id'] ) && is_numeric( $_REQUEST['view_id'] ) && $agents->_is_agent() ) {
      $requested_view = $_REQUEST['view_id'];

      // Is somebody trying to cheat?
      $dashboard_widget = Zendesk_Wordpress_Dashboard_Widget::get_instance();
      if ( $dashboard_widget->_get_current_user_dashboard_widget() != 'tickets-widget' ) {
        return;
      }
      $views = $zendesk_support->api->get_views();

      if ( ! is_wp_error( $views ) ) {
        foreach ( $views as $view ) {
          if ( $view->id == $requested_view ) {
            $zendesk_support->zendesk_user['default_view'] = array(
              'id'    => $view->id,
              'title' => $view->title
            );

            update_user_meta( $zendesk_support->user->ID, 'zendesk_user_options', $zendesk_support->zendesk_user );
            break;
          }
        }
      }

      // API requests based on the Zendesk role.
      $tickets = $zendesk_support->api->get_tickets_from_view( (int) $zendesk_support->zendesk_user['default_view']['id'] );

      // Empty the arrays if they are errors.
      if ( is_wp_error( $tickets ) ) {
        $tickets = array();
      }

      $response = array(
        'status' => 200,
        'html'   => Zendesk_Wordpress_Tickets::_get_tickets_widget_html( $tickets )
      );
    } else {
      $response = array(
        'status' => 403,
        'error'  => __( 'Access denied', 'zendesk' )
      );
    }

    echo json_encode( $response );
    die();
  }

  /*
   * AJAX Response: View Ticket
   *
   * This method is fired by WordPress when requesting via the AJAX
   * API and the zendesk_view_ticket action is set. Gathers the info
   * given the ticket id and returns a JSON object containing a status
   * code, the ticket details, and the ticket data formatted in an
   * HTML table.
   *
   */
  public function _ajax_view_ticket() {
    global $zendesk_support;
    $agents = Zendesk_Wordpress_Agents::get_instance();

    if ( isset( $_REQUEST['ticket_id'] ) && is_numeric( $_REQUEST['ticket_id'] ) ) {

      $ticket_id = $_REQUEST['ticket_id'];

      // API requests based on the Zendesk role.
      if ( $agents->_is_agent() ) {
        $ticket = $zendesk_support->api->get_ticket_info( $ticket_id );
      } else {
        $ticket = $zendesk_support->api->get_request_info( $ticket_id );
      }

      // If there was no error fetch further
      if ( ! is_wp_error( $ticket ) ) {
        // If there's a requester ID let's resolve it
        if ( isset( $ticket->requester_id ) ) {
          $requester = $zendesk_support->api->get_user( $ticket->requester_id );

          if ( ! is_wp_error( $requester ) ) {
            $requester = $requester->user->name;
          } else {
            $requester = __( 'Unknown', 'zendesk' );
          }
          // Otherwise set it to blank, blank fields don't show up.
        } else {
          $requester = '';
        }

        // Updated field is not viewable by end-users, so if it's
        // not set then set it to blank.
        if ( ! isset( $ticket->updated_at ) ) {
          $ticket->updated_at = '';
        }

        // Create the table values, where key is the label and value
        // is the value, doh!
        $table_values = array(
          __( 'Subject:', 'zendesk' )       => htmlspecialchars( $ticket->subject ),
          __( 'Description:', 'zendesk' )   => nl2br( htmlspecialchars( $ticket->description ) ),
          __( 'Ticket Status:', 'zendesk' ) => '<span class="zendesk-status-' . $ticket->status . '">' . $zendesk_support->_ticket_status( $ticket->status ) . '</span>',
          __( 'Requested by:', 'zendesk' )  => '<a target="_blank" href="' . Zendesk_Wordpress_Utilities::_user_url( $ticket->requester_id ) . '">' . $requester . '</a>',
          __( 'Created:', 'zendesk' )       => date( get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' ), strtotime( $ticket->created_at ) ),
          __( 'Updated:', 'zendesk' )       => date( get_option( 'date_format' ) . ' \a\t ' . get_option( 'time_format' ), strtotime( $ticket->updated_at ) )
        );

        // Agents only data
        if ( $agents->_is_agent() ) {

          // Custom fields
          $table_custom_fields = array();
          $ticket_fields       = $zendesk_support->api->get_ticket_fields();

          // Perhaps optimize this a little bit, though this is
          // the way values come in from Zendesk.
          if ( ! is_wp_error( $ticket_fields ) && ! empty( $ticket->custom_fields ) ) {
            $custom_fields_array = array();
            // Build an array with custom field values and ID as index
            foreach ( $ticket->custom_fields as $custom_field ) {
              $custom_fields_array[ $custom_field->id ] = $custom_field->value;
            }
            $custom_fields_ids = array_keys( $custom_fields_array );

            foreach ( $ticket_fields as $ticket_field ) {
              if ( ! in_array( $ticket_field->id, $custom_fields_ids ) ) {
                continue;
              }

              // Use numeric index in case there are duplicate field titles
              $table_custom_fields[ $ticket_field->id ] = array( 'title' => $ticket_field->title, 'value' => '' );
              // Use readable value for 'tagger' types
              if ( 'tagger' === $ticket_field->type ) {
                foreach ( $ticket_field->custom_field_options as $custom_field_option ) {
                  if ( $custom_fields_array[ $ticket_field->id ] === $custom_field_option->value ) {
                    $table_custom_fields[ $ticket_field->id ]['value'] = $custom_field_option->name;
                  }
                }
              } else {
                $table_custom_fields[ $ticket_field->id ]['value'] = $custom_fields_array[ $ticket_field->id ];
              }
            }
          }

          $table_actions = array(
            __( 'Comments:', 'zendesk' ) => '<a data-id="' . $ticket->id . '" href="#" class="zendesk-view-comments">' . __( 'View the comments thread', 'zendesk' ) . '</a>',
            __( 'View:', 'zendesk' )     => '<a target="_blank" href="' . Zendesk_Wordpress_Utilities::_ticket_url( $ticket->id ) . '">' . __( 'View this ticket on Zendesk', 'zendesk' ) . '</a>'
          );

        }

        // Use these for debug values
        //$table_values['Ticket'] = print_r($ticket, true);
        //$table_values['Fields'] = print_r($ticket_fields, true);

        // Start formatting the general HTML table.
        $html = '<table id="zendesk-ticket-details-table" class="zendesk-ticket-details-table">';

        foreach ( $table_values as $label => $value ) {
          if ( strlen( $value ) < 1 ) {
            continue;
          }
          $html .= '<tr><td class="zendesk-first"><span class="description">' . $label . '</span></td>';
          $html .= '<td>' . $value . '</td></tr>';
        }

        // Custom Fields Table (agents only)
        if ( isset( $table_custom_fields ) && ! empty( $table_custom_fields ) ) {
          $html .= '<tr><td colspan="2"><p class="zendesk-heading" style="margin-bottom: 0px;">' . __( 'Custom Fields', 'zendesk' ) . '</p></td></tr>';

          foreach ( $table_custom_fields as $table_custom_field ) {
            if ( strlen( $table_custom_field['value'] ) < 1 ) {
              continue;
            }
            $html .= '<tr><td class="zendesk-first"><span class="description">' . esc_html( $table_custom_field['title'] ) . '</span></td>';
            $html .= '<td>' . esc_html( $table_custom_field['value'] ) . '</td></tr>';
          }
        }

        // Actions Table (agents only)
        if ( isset( $table_actions ) && ! empty( $table_actions ) ) {
          $html .= '<tr><td colspan="2"><p class="zendesk-heading" style="margin-bottom: 0px;">' . __( 'Actions', 'zendesk' ) . '</p></td></tr>';
          foreach ( $table_actions as $label => $value ) {
            $html .= '<tr><td class="zendesk-first"><span class="description">' . $label . '</span></td>';
            $html .= '<td>' . $value . '</td></tr>';
          }
        }

        $html .= '</table>';

        // Format the response to output.
        $response = array(
          'status' => 200,
          'ticket' => $ticket,
          'html'   => $html
        );

      } else {

        // Something went wrong
        $response = array(
          'status' => 404,
          'data'   => $ticket->get_error_message()
        );
      }

    } else {

      // Something went really wrong
      $response = array(
        'status' => 404,
        'data'   => __( 'Ticket was not found.', 'zendesk' )
      );
    }

    // Output the response array as a JSON object.
    echo json_encode( $response );
    die();
  }
}
