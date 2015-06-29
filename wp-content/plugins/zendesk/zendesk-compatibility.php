<?php
/*
 * Zendesk Compatibility Hacks
 *
 * This file includes some functions redefinitions, ones that are used
 * but not defined in previous WordPress versions.
 *
 * @author Konstantin Kovshenin
 * @version 1.6.1
 *
 */

/*
 * Function: esc_textarea()
 *
 * Escaping for textarea values. Available since WordPress 3.1
 *
 */
if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		$safe_text = htmlspecialchars( $text, ENT_QUOTES );
		return apply_filters( 'esc_textarea', $safe_text, $text );
	}
}

/*
 * Function: get_user_meta()
 *
 * Get user meta data from the WordPress database. Available since
 * WordPress 3.0 ( deprecated function: get_usermeta )
 *
 */
if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $user_id, $key, $single = false ) {
		return get_usermeta( $user_id, $key );
	}
}

/*
 * Function: update_user_meta
 *
 * Update the user meta in a WordPress database. Available since
 * WordPress 3.0 ( deprecated function: update_usermeta )
 *
 */

if ( ! function_exists( 'update_user_meta' ) ) {
	function update_user_meta( $user_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_usermeta( $user_id, $meta_key, $meta_value );
	}
}
