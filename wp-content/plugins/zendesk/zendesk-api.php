<?php
/*
 * Zendesk API Class
 *
 */

/*
 * The Zendesk API Class
 *
 * Handles all the work with the Zendesk API including authentication,
 * ticket creation, listings, etc. Operates via the JSON api, thus
 * requires the JSON functions available in PHP5 (and PHP4 as a PEAR
 * library).
 *
 * @uses json_encode, json_decode
 * @uses WP_Http wrappers
 *
 */
class Zendesk_API {
	private $api_url = '';
	private $username = false;
	private $password = false;

	/*
	 * Constructor
	 *
	 * The only parameter is the API url, together with the protocol
	 * (generally http or https). The trailing slash is appended during
	 * API calls if one doesn't exist.
	 *
	 */
	public function __construct( $api_url ) {
		$this->api_url = $api_url . '/api/v2';

		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			$this->cache_timeout = 60;
			$this->cache_timeout_views = 60 * 60;
			$this->cache_timeout_ticket_fields = 60 * 60;
			$this->cache_timeout_user = 60 * 60;
		} else {
			$this->cache_timeout = 5;
			$this->cache_timeout_views = 5;
			$this->cache_timeout_ticket_fields = 5;
			$this->cache_timeout_user = 5;
		}
	}

	/*
	 * Authentication
	 *
	 * Grabs the $username and $password and stores them in its own
	 * private variables. If the $validate argument is set to true
	 * (default behaviour) a call to the Zendesk API is issued to
	 * validate the current user's credentials.
	 *
	 * This method is public and returns true or false upon authentication
	 * success or failure.
	 *
	 */
	public function authenticate( $username, $password, $validate = true ) {
		$this->username = $username;
		$this->password = $password;

		if ( $validate ) {
			$result = $this->_get( 'users/me.json' );
			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$user_data = json_decode( $result['body']);
				$user_data = $user_data->user;

				if (is_null($user_data->id)) {
					return $this->auth_error();
				}

				return $user_data;

			} else {
				return $this->auth_error();
			}
		} else {
			return true;
		}
	}

	/*
	 * Authentication Helper
	 * set username and password to false
	 * return new WP_Error
	 */
	private function auth_error() {
		$this->username = false;
		$this->password = false;
		return new WP_Error( 'zendesk-api-error', __( 'We could not authenticate you with Zendesk, please try again!', 'zendesk' ) );
	}

	/*
	 * Use SSL
	 *
	 * Determines whether the given Zendesk account is set to use
	 * SSL. Fires a HEAD request to home.json via HTTPS and watches
	 * the response for a 302 redirect. If a redirect occurs, then
	 * there's no SSL, otherwise SSL is turned on.
	 *
	 * Works well with: cURL, PHP Streams, fsockopen
	 * @todo: Doesn't work with: fopen
	 *
	 */
	public function is_ssl( $account ) {
		$headers = array( 'Content-Type' => 'application/json' );
		$result = wp_remote_head( trailingslashit( 'https://' . $account . '.zendesk.com' ) . 'home.json', array( 'headers' => $headers ) );

		// Let's see if there was a redirect
		if ( ! is_wp_error( $result ) && $result['response']['code'] == 302 ) {
			return false;
		} else {
			return true;
		}
	}

	/*
	 * Create Ticket
	 *
	 * Creates a new ticket given the $subject and $description. The
	 * new ticket is authored by the currently set user, i.e. the
	 * credentials stored in the private variables of this class.
	 *
	 */
	public function create_ticket( $subject, $description, $requester_name = false, $requester_email = false ) {
		$ticket = array(
			'ticket' => array(
				'subject' => $subject,
				'comment' => array(
				  'body' => $description
				)
			)
		);

		if ( $requester_name && $requester_email ) {
			$ticket['ticket']['requester'] = array(
			  'name'  => $requester_name,
			  'email' => $requester_email
			);
		}

		$result = $this->_post( 'tickets.json', $ticket );

		if ( ! is_wp_error( $result ) && $result['response']['code'] == 201 ) {
			$location = $result['headers']['location'];
			preg_match( '/\.zendesk\.com\/api\/v2\/tickets\/([0-9]+)\.(json)/i', $location, $matches );

			if ( isset( $matches[1] ) )
				return $matches[1];
			else
				return new WP_Error( 'zendesk-api-error', __( 'A new ticket could not be created at this time, please try again later.', 'zendesk' ) );

		} else {
			return new WP_Error( 'zendesk-api-error', __( 'A new ticket could not be created at this time, please try again later.', 'zendesk' ) );
		}
	}

	/*
	 * Create Comment
	 *
	 * Creates a comment to the specified ticket with the specified text.
	 * The $public argument, as the name suggests, tells Zendesk whether
	 * this comment should be public or private.
	 *
	 */
	public function create_comment( $ticket_id, $text, $public = true ) {
		$ticket = array(
			'ticket' => array(
				'comment' => array(
					'public' => $public,
					'body' => $text
				)
			)
		);

		$result = $this->_put( 'tickets/' . $ticket_id . '.json', $ticket );

		if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
			return true;
		} else {
			return new WP_Error( 'zendesk-api-error', __( 'A new comment could not be created at this time, please try again later.', 'zendesk' ) );
		}
	}

	/*
	 * Get Ticket Comments
	 *
	 * Asks the Zendesk API for the comments on a certain ticket, provided
	 * the ticket id in the arguments. If the ticket was not found or is
	 * inaccessible by the current user, returns a WP_Error. Values are
	 * not cached.
	 *
	 */
	public function get_comments( $ticket_id ) {
		$result = $this->_get( 'tickets/' . $ticket_id . '/comments.json' );

		if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
			$comments = json_decode( $result['body'] );
			$comments = $comments->comments;
			return $comments;
		} else {
			return new WP_Error( 'zendesk-api-error', __( 'Could not fetch the comments at this time, please try again later.', 'zendesk' ), array( 'status' => $result['response']['code'] ) );
		}

		return $comments;
	}

	/*
	 * Create Request
	 *
	 * Same as the method above, but instead of tickets.json, requests.json
	 * is called. Used to create tickets by non-admin and non-agent users
	 * (based on their role, where 0 is generally end-users).
	 *
	 */
	public function create_request( $subject, $description ) {
		$request = array(
			'request' => array(
				'subject' => $subject,
				'comment' => array( 'body' => $description )
			)
		);

		$headers = array();

		$result = $this->_post( 'requests.json', $request, $headers );

		/*
		 * @todo: requests.json returns a 406 for end-users instead of
		 * the expected 201. Should probably fix this in future update,
		 * related issue: #23 Temporary fix is to allow 406's.
		 */
		if ( ! is_wp_error( $result ) && ( $result['response']['code'] == 201 || $result['response']['code'] == 406 ) ) {
			return true;
		} else {
			return new WP_Error( 'zendesk-api-error', __( 'A new request could not be created at this time, please try again later.', 'zendesk' ) );
		}
	}

	/*
	 * Get Views
	 *
	 * Returns an array of available views with their IDs, titles,
	 * ticket counts and more. If for some reason views cannot be
	 * fetched, returns a WP_Error object. Caching is enabled via
	 * the Transient API.
	 *
	 */
	public function get_views() {
		$transient_key = $this->_salt( 'views' );

		if ( false === ( $views = get_transient( $transient_key ) ) ) {

			$result = $this->_get( 'views.json' );

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$views = json_decode( $result['body'] );
				$views = $views->views;

				set_transient( $transient_key, $views, $this->cache_timeout_views );
				return $views;

			} else {

				if ( is_wp_error( $result ) )
					return new WP_Error( 'zendesk-api-error', __( 'The active views could not be fetched at this time, please try again later.', 'zendesk' ) );

				elseif ( $result['response']['code'] == 403 )
					return new WP_Error( 'zendesk-api-error', __( 'Access denied You do not have access to this view.', 'zendesk' ) );
			}
		}

		// Serving from cache
		return $views;
	}

	/*
	 * Get Ticket Fields
	 *
	 * Retrieves the ticket fields, used mostly for custom fields display
	 * in the tickets view widget in the dashboard.
	 *
	 */
	public function get_ticket_fields() {
		$transient_key = $this->_salt( 'ticket_fields' );

		if ( false === ( $fields = get_transient( $transient_key ) ) ) {
			$result = $this->_get( 'ticket_fields.json' );

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$fields = json_decode( $result['body'] );
				$fields = $fields->ticket_fields;
				set_transient( $transient_key, $fields, $this->cache_timeout_ticket_fields );
				return $fields;
			} else {
				if ( is_wp_error( $result ) )
					return new WP_Error( 'zendesk-api-error', __( 'The ticket fields could not be fetched at this time, please try again later.', 'zendesk' ) );
			}
		}

		// Serving from cache
		return $fields;
	}

	/*
	 * Get Requests
	 *
	 * Similar to the function above but used for end-users to return
	 * all open requests. Returns a WP_Error if requests could not be
	 * fetched. Uses the Transient API for caching results.
	 *
	 */
	public function get_requests() {
		$transient_key = $this->_salt( 'requests' );

		if ( false == ( $requests = get_transient( $transient_key ) ) ) {
			$result = $this->_get( 'requests.json' );

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$requests = json_decode( $result['body'] );
				$requests = $requests->requests;
				set_transient( $transient_key, $requests, $this->cache_timeout );
				return $requests;
			} else {
				return new WP_Error( 'zendesk-api-error', __( 'The requests could not be fetched at this time, please try again later.', 'zendesk' ) );
			}
		}

		// Serving from cache
		return $requests;
	}

	/*
	 * Get Tickets from View
	 *
	 * Returns an array of tickets for a specific view given in the
	 * $view_id argument. If such a view does not exist or an error
	 * has occured, this method returns a WP_Error. Caching in this
	 * method is enabled through WordPress transients.
	 *
	 */
	public function get_tickets_from_view( $view_id ) {
		$transient_key = $this->_salt( 'view-' . $view_id );

		if ( false === ( $tickets = get_transient( $transient_key ) ) ) {
			$result = $this->_get( 'views/' . $view_id . '/tickets.json' );

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$tickets = json_decode( $result['body'] );
				$tickets = $tickets->tickets;

				set_transient( $transient_key, $tickets, $this->cache_timeout );
				return $tickets;
			} else {
				return new WP_Error( 'zendesk-api-error', __( 'The tickets for this view could not be fetched at this time, please try again later.', 'zendesk' ) );
			}
		}

		// Serving from cache
		return $tickets;
	}

	/*
	 * Get Ticket Info
	 *
	 * Asks the Zendesk API for details about a certain ticket, provided
	 * the ticket id in the arguments. If the ticket was not found or is
	 * inaccessible by the current user, returns a WP_Error. Values are
	 * not cached.
	 *
	 */
	public function get_ticket_info( $ticket_id ) {
		$transient_key = $this->_salt( 'ticket-' . $ticket_id );

		if ( false === ( $ticket = get_transient( $transient_key ) ) ) {
			$result = $this->_get( 'tickets/' . $ticket_id . '.json' );

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$ticket = json_decode( $result['body'] );
				$ticket = $ticket->ticket;
				set_transient( $transient_key, $ticket, $this->cache_timeout );
				return $ticket;
			} else {
				return new WP_Error( 'zendesk-api-error', __( 'Could not fetch the ticket at this time, please try again later.', 'zendesk' ) );
			}
		}

		// Serving from cache
		return $ticket;
	}

	/*
	 * Get Request Info
	 *
	 * Similar to the method above but asks for the request info, available
	 * to the end-users. If the request was not found, returns a WP_Error.
	 * Value is not cached.
	 *
	 */
	public function get_request_info( $ticket_id ) {
		$transient_key = $this->_salt( 'request-' . $ticket_id );

		if ( false === ( $request = get_transient( $transient_key ) ) ) {

			$result = $this->_get( 'requests/' . $ticket_id . '.json' );

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$request = json_decode( $result['body'] );
				set_transient( $transient_key, $request, $this->cache_timeout );
				return $request;
			} else {
				return new WP_Error( 'zendesk-api-error', __( 'Could not fetch the request at this time, please try again later.', 'zendesk' ) );
			}
		}

		// Serving from cache
		return $request;
	}

	/*
	 * Get User Details
	 *
	 * Asks the Zendesk API for the details about a specific user. Input
	 * argument is the user id which is sometimes present in the tickets
	 * details. User objects are cached using the Transient API.
	 *
	 */
	public function get_user( $user_id ) {
		$transient_key = $this->_salt( 'user-' . $user_id );

		if ( false == ( $user = get_transient( $transient_key ) ) ) {
			$result = $this->_get( 'users/' . $user_id . '.json' );

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$user = json_decode( $result['body'] );
				set_transient( $transient_key, $user, $this->cache_timeout_user );
				return $user;
			} else {
				return new WP_Error( 'zendesk-api-error', __( 'The requested user details could not be fetched at this time, please try again later.', 'zendesk' ) );
			}
		}

		// Serving from cache
		return $user;
	}

	/*
	 * API GET
	 *
	 * This is a private method used by the methods above to actually
	 * access the Zendesk API. Handles the construction of the request
	 * header and fires a new wp_remote_get request each time.
	 *
	 */
	private function _get( $endpoint, $extra_headers = array() ) {
		$headers = array( 'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password ), 'Content-Type' => 'application/json' );
		$target_url = trailingslashit( $this->api_url ) . $endpoint;
		$result = wp_remote_get( $target_url, array( 'headers' => $headers, 'sslverify' => false ) );

		if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && is_wp_error( $result ) ) {
			$error_string = 'Zendesk API GET Error (' . $target_url . '): ' . $result->get_error_message();
			if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
				echo $error_string  . '<br />';
			}

			Zendesk_Support::log( $error_string, TRUE );
		}

		return $result;
	}

	/*
	 * API POST
	 *
	 * Similar to the GET method, this function forms the request params
	 * as a POST request to the Zendesk API, given an endpoint and a
	 * $post_data which is generally an associative array.
	 *
	 */
	private function _post( $endpoint, $post_data = null, $extra_headers = array() ) {
		$post_data = json_encode( $post_data );
		$headers = array( 'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password ), 'Content-Type' => 'application/json' );
		$headers = array_merge( $headers, $extra_headers );
		$target_url = trailingslashit( $this->api_url ) . $endpoint;
		$result = wp_remote_post( $target_url, array( 'redirection' => 0, 'headers' => $headers, 'body' => $post_data, 'sslverify' => false ) );

		if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && is_wp_error( $result ) ) {
			$error_string = 'Zendesk API POST Error (' . $target_url . '): ' . $result->get_error_message();
			if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
				echo $error_string  . '<br />';
			}

			Zendesk_Support::log( $error_string, TRUE );
		}
		return $result;
	}

	/*
	 * API PUT
	 *
	 * Following the above pattern, this function uses wp_remote_request
	 * to fire a PUT request against the Zendesk API. Returns the result
	 * object as it was returned by the request.
	 *
	 */
	private function _put( $endpoint, $put_data = null, $extra_headers = array() ) {
		$put_data = json_encode( $put_data );
		$headers = array( 'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password ), 'Content-Type' => 'application/json' );
		$headers = array_merge( $headers, $extra_headers );

		$target_url = trailingslashit( $this->api_url ) . $endpoint;
		$result = wp_remote_request( $target_url, array( 'method' => 'PUT', 'headers' => $headers, 'body' => $put_data, 'sslverify' => false ) );

		if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && is_wp_error( $result ) ) {
			$error_string = 'Zendesk API PUT Error (' . $target_url . '): ' . $result->get_error_message();
			if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
				echo $error_string  . '<br />';
			}

			Zendesk_Support::log( $error_string, TRUE );
		}
		return $result;
	}

	/*
	 * Cache Salts (helper)
	 *
	 * Use this function to compose Transient API keys, prepends a zd-
	 * and generates a salt based on the username and the api_url and
	 * the provided postfix variable.
	 *
	 */
	private function _salt( $postfix ) {
		return 'zd-' . md5( 'zendesk-' . $this->username . $this->api_url . $postfix );
	}
}
