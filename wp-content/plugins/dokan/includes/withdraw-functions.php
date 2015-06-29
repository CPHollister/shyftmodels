<?php

/**
 * Get default withdraw methods
 *
 * @return array
 */
function dokan_withdraw_register_methods() {
    $methods = array(
        'paypal' => array(
            'title'    =>  __( 'PayPal', 'dokan' ),
            'callback' => 'dokan_withdraw_method_paypal'
        ),
        'bank' => array(
            'title'    => __( 'Bank Transfer', 'dokan' ),
            'callback' => 'dokan_withdraw_method_bank'
        ),
        'skrill' => array(
            'title'    => __( 'Skrill', 'dokan' ),
            'callback' => 'dokan_withdraw_method_skrill'
        ),
    );

    return apply_filters( 'dokan_withdraw_methods', $methods );
}

/**
 * Get registered withdraw methods suitable for Settings Api
 *
 * @return array
 */
function dokan_withdraw_get_methods() {
    $methods = array();
    $registered = dokan_withdraw_register_methods();

    foreach ($registered as $key => $value) {
        $methods[$key] = $value['title'];
    }

    return $methods;
}

/**
 * Get active withdraw methods.
 *
 * Default is paypal
 *
 * @return array
 */
function dokan_withdraw_get_active_methods() {
    $methods = dokan_get_option( 'withdraw_methods', 'dokan_selling', array( 'paypal' ) );

    return $methods;
}

/**
 * Get a single withdraw method based on key
 *
 * @param string $method_key
 * @return boolean|array
 */
function dokan_withdraw_get_method( $method_key ) {
    $methods = dokan_withdraw_register_methods();

    if ( isset( $methods[$method_key] ) ) {
        return $methods[$method_key];
    }

    return false;
}

/**
 * Get title from a withdraw method
 *
 * @param string $method_key
 * @return string
 */
function dokan_withdraw_get_method_title( $method_key ) {
    $registered = dokan_withdraw_register_methods();

    if ( isset( $registered[$method_key]) ) {
        return $registered[$method_key]['title'];
    }

    return '';
}

/**
 * Callback for PayPal in store settings
 *
 * @global WP_User $current_user
 * @param array $store_settings
 */
function dokan_withdraw_method_paypal( $store_settings ) {
    global $current_user;

    $email = isset( $store_settings['payment']['paypal']['email'] ) ? esc_attr( $store_settings['payment']['paypal']['email'] ) : $current_user->user_email ;
    ?>
    <div class="dokan-form-group">
        <div class="dokan-w8">
            <div class="dokan-input-group">
                <span class="dokan-input-group-addon"><?php _e( 'E-mail', 'dokan' ); ?></span>
                <input value="<?php echo $email; ?>" name="settings[paypal][email]" class="dokan-form-control email" placeholder="you@domain.com" type="text">
            </div>
        </div>
    </div>
    <?php
}

/**
 * Callback for Skrill in store settings
 *
 * @global WP_User $current_user
 * @param array $store_settings
 */
function dokan_withdraw_method_skrill( $store_settings ) {
    global $current_user;

    $email = isset( $store_settings['payment']['skrill']['email'] ) ? esc_attr( $store_settings['payment']['skrill']['email'] ) : $current_user->user_email ;
    ?>
    <div class="dokan-form-group">
        <div class="dokan-w8">
            <div class="dokan-input-group">
                <span class="dokan-input-group-addon"><?php _e( 'E-mail', 'dokan' ); ?></span>
                <input value="<?php echo $email; ?>" name="settings[skrill][email]" class="dokan-form-control email" placeholder="you@domain.com" type="text">
            </div>
        </div>
    </div>
    <?php
}

/**
 * Callback for Bank in store settings
 *
 * @global WP_User $current_user
 * @param array $store_settings
 */
function dokan_withdraw_method_bank( $store_settings ) {
    $account_name   = isset( $store_settings['payment']['bank']['ac_name'] ) ? esc_attr( $store_settings['payment']['bank']['ac_name'] ) : '';
    $account_number = isset( $store_settings['payment']['bank']['ac_number'] ) ? esc_attr( $store_settings['payment']['bank']['ac_number'] ) : '';
    $bank_name      = isset( $store_settings['payment']['bank']['bank_name'] ) ? esc_attr( $store_settings['payment']['bank']['bank_name'] ) : '';
    $bank_addr      = isset( $store_settings['payment']['bank']['bank_addr'] ) ? esc_textarea( $store_settings['payment']['bank']['bank_addr'] ) : '';
    $swift_code     = isset( $store_settings['payment']['bank']['swift'] ) ? esc_attr( $store_settings['payment']['bank']['swift'] ) : '';
    ?>
    <div class="dokan-form-group">
        <div class="doakn-w8">
            <input name="settings[bank][ac_name]" value="<?php echo $account_name; ?>" class="dokan-form-control" placeholder="<?php esc_attr_e( 'Your bank account name', 'dokan' ); ?>" type="text">
        </div>
    </div>

    <div class="dokan-form-group">
        <div class="doakn-w8">
            <input name="settings[bank][ac_number]" value="<?php echo $account_number; ?>" class="dokan-form-control" placeholder="<?php esc_attr_e( 'Your bank account number', 'dokan' ); ?>" type="text">
        </div>
    </div>

    <div class="dokan-form-group">
        <div class="doakn-w8">
            <input name="settings[bank][bank_name]" value="<?php echo $bank_name; ?>" class="dokan-form-control" placeholder="<?php _e( 'Name of bank', 'dokan' ) ?>" type="text">
        </div>
    </div>

    <div class="dokan-form-group">
        <div class="doakn-w8">
            <textarea name="settings[bank][bank_addr]" class="dokan-form-control" placeholder="<?php esc_attr_e( 'Address of your bank', 'dokan' ) ?>"><?php echo $bank_addr; ?></textarea>
        </div>
    </div>

    <div class="dokan-form-group">
        <div class="col-md-10">
            <input value="<?php echo $swift_code; ?>" name="settings[bank][swift]" class="dokan-form-control" placeholder="<?php esc_attr_e( 'Swift code', 'dokan' ); ?>" type="text">
        </div>
    </div> <!-- .dokan-form-group -->
    <?php
}

/**
 * Get withdraw counts, used in admin area
 *
 * @global WPDB $wpdb
 * @return array
 */
function dokan_get_withdraw_count() {
    global $wpdb;

    $cache_key = 'dokan_withdraw_count';
    $counts = wp_cache_get( $cache_key );

    if ( false === $counts ) {

        $counts = array( 'pending' => 0, 'completed' => 0, 'cancelled' => 0 );
        $sql = "SELECT COUNT(id) as count, status FROM {$wpdb->dokan_withdraw} GROUP BY status";
        $result = $wpdb->get_results( $sql );

        if ( $result ) {
            foreach ($result as $row) {
                if ( $row->status == '0' ) {
                    $counts['pending'] = (int) $row->count;
                } elseif ( $row->status == '1' ) {
                    $counts['completed'] = (int) $row->count;
                } elseif ( $row->status == '2' ) {
                    $counts['cancelled'] = (int) $row->count;
                }
            }
        }
    }

    return $counts;
}

/**
 * Get active withdraw order status.
 *
 * Default is 'completed', 'processing', 'on-hold'
 *
 */
function dokan_withdraw_get_active_order_status() {
    $order_status = dokan_get_option( 'withdraw_order_status', 'dokan_selling', array( 'wc-completed' ) );

    return apply_filters( 'dokan_withdraw_active_status', $order_status );
}

/**
 * get comma seperated value from "dokan_withdraw_get_active_order_status()" return array
 * @param array array
 */
function dokan_withdraw_get_active_order_status_in_comma() {
    $order_status = dokan_withdraw_get_active_order_status();
    $status = "'" . implode("', '", $order_status ) . "'";
    return $status;
}
