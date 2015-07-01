<?php

/**
 * Dokan_Stripe_Connect class.
 *
 * @extends WC_Payment_Gateway
 */
class Dokan_Stripe_Connect extends WC_Payment_Gateway {

    function __construct() {

        $this->id           = 'dokan-stripe-connect';
        $this->method_title = __( 'Dokan Stripe Connect', 'dokan-stripe' );
        $this->icon         = plugins_url( '/assets/images/cards.png', dirname( __FILE__ ) );
        $this->has_fields   = true;
        $this->api_endpoint = 'https://api.stripe.com/';
        $this->supports     = array( 'products' );

        $this->init_form_fields();
        $this->init_settings();

        $this->title           = $this->settings['title'];
        $this->description     = $this->settings['description'];
        $this->enabled         = $this->settings['enabled'];
        $this->testmode        = $this->settings['testmode'];
        $this->stripe_checkout = isset( $this->settings['stripe_checkout'] ) && $this->settings['stripe_checkout'] == 'yes' ? true : false;
        $this->secret_key      = $this->testmode == 'no' ? $this->settings['secret_key'] : $this->settings['test_secret_key'];
        $this->publishable_key = $this->testmode == 'no' ? $this->settings['publishable_key'] : $this->settings['test_publishable_key'];
        $this->saved_cards     = $this->settings['saved_cards'] === "yes" ? true : false;

        /** All actions */
        add_action( 'wp_enqueue_scripts', array( &$this, 'payment_scripts' ) );
        add_action( 'admin_notices', array( &$this, 'checks' ) );
        add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Check if SSL is enabled and notify the user
     */
    function checks() {

        global $woocommerce;

        if ( $this->enabled == 'no' ) {
            return;
        }

        if ( $woocommerce->version < '1.5.8' ) {
            echo '<div class="error"><p>' . __( 'Stripe now uses stripe.js for security and requires WooCommerce 1.5.8. Please update WooCommerce to continue using Stripe.', 'dokan-stripe' ) . '</p></div>';
            return;
        }

        if ( ! $this->secret_key ) {
            echo '<div class="error"><p>' . sprintf( __( 'Stripe error: Please enter your secret key <a href="%s">here</a>', 'dokan-stripe' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=dokan_stripe_connect' ) ) . '</p></div>';
            return;

        } elseif ( ! $this->publishable_key ) {
            echo '<div class="error"><p>' . sprintf( __( 'Stripe error: Please enter your publishable key <a href="%s">here</a>', 'dokan-stripe' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=dokan_stripe_connect' ) ) . '</p></div>';
            return;
        }

        if ( $this->secret_key == $this->publishable_key ) {
            echo '<div class="error"><p>' . sprintf( __( 'Stripe error: Your secret and publishable keys match. Please check and re-enter.', 'dokan-stripe' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=dokan_stripe_connect' ) ) . '</p></div>';
            return;
        }

        if ( get_option( 'woocommerce_force_ssl_checkout' ) == 'no' && ! class_exists( 'WordPressHTTPS' ) ) {
            echo '<div class="error"><p>' . sprintf( __( 'Stripe is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - Stripe will only work in test mode.', 'dokan-stripe' ), admin_url( 'admin.php?page=woocommerce' ) ) . '</p></div>';
        }
    }

    /**
     * Check if this gateway is enabled and available in the user's country
     */
    function is_available() {
        global $woocommerce;

        if ( $this->enabled == "yes" ) {

            if ( $woocommerce->version < '1.5.8' ) {
                return false;
            }

            if ( ! is_ssl() && $this->testmode != 'yes' ) {
                return false;
            }

            if ( ! in_array( get_option( 'woocommerce_currency' ), array( 'AED','AFN','ALL','AMD','ANG','AOA','ARS','AUD','AWG','AZN','BAM','BBD','BDT','BGN','BIF','BMD','BND','BOB','BRL','BSD','BWP','BZD','CAD','CDF','CHF','CLP','CNY','COP','CRC','CVE','CZK','DJF','DKK','DOP','DZD','EEK','EGP','ETB','EUR','FJD','FKP','GBP','GEL','GIP','GMD','GNF','GTQ','GYD','HKD','HNL','HRK','HTG','HUF','IDR','ILS','INR','ISK','JMD','JPY','KES','KGS','KHR','KMF','KRW','KYD','KZT','LAK','LBP','LKR','LRD','LSL','LTL','LVL','MAD','MDL','MGA','MKD','MNT','MOP','MRO','MUR','MVR','MWK','MXN','MYR','MZN','NAD','NGN','NIO','NOK','NPR','NZD','PAB','PEN','PGK','PHP','PKR','PLN','PYG','QAR','RON','RSD','RUB','RWF','SAR','SBD','SCR','SEK','SGD','SHP','SLL','SOS','SRD','STD','SVC','SZL','THB','TJS','TOP','TRY','TTD','TWD','TZS','UAH','UGX','USD','UYU','UZS','VEF','VND','VUV','WST','XAF','XCD','XOF','XPF','YER','ZAR','ZMW' ) ) ) {
                return false;
            }

            if ( ! $this->secret_key ) return false;
            if ( ! $this->publishable_key ) return false;

            return true;
        }

        return false;
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title'       => __( 'Enable/Disable', 'dokan-stripe' ),
                'label'       => __( 'Enable Stripe', 'dokan-stripe' ),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => __( 'Title', 'dokan-stripe' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'dokan-stripe' ),
                'default'     => __( 'Dokan Credit card (Stripe)', 'dokan-stripe' )
            ),
            'description' => array(
                'title'       => __( 'Description', 'dokan-stripe' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'dokan-stripe' ),
                'default'     => 'Pay with your credit card via Stripe.'
            ),
            'testmode' => array(
                'title'       => __( 'Test mode', 'dokan-stripe' ),
                'label'       => __( 'Enable Test Mode', 'dokan-stripe' ),
                'type'        => 'checkbox',
                'description' => __( 'Place the payment gateway in test mode using test API keys.', 'dokan-stripe' ),
                'default'     => 'yes'
            ),
            'stripe_checkout' => array(
                'title'       => __( 'Stripe Checkout', 'dokan-stripe' ),
                'label'       => __( 'Enable Stripe Checkout', 'dokan-stripe' ),
                'type'        => 'checkbox',
                'description' => __( 'If enabled, this option shows a "pay" button and modal credit card form on the checkout, instead of credit card fields directly on the page.', 'dokan-stripe' ),
                'default'     => 'no'
            ),
            'saved_cards' => array(
                'title'       => __( 'Saved cards', 'dokan-stripe' ),
                'label'       => __( 'Enable saved cards', 'dokan-stripe' ),
                'type'        => 'checkbox',
                'description' => __( 'If enabled, users will be able to pay with a saved card during checkout. Card details are saved on Stripe servers, not on your store.', 'dokan-stripe' ),
                'default'     => 'no'
            ),
            'live-credentials-title' => array(
                'title' => __( 'Live credentials', 'dokan-stripe' ),
                'type'  => 'title',
            ),
            'secret_key' => array(
                'title'       => __( 'Secret Key', 'dokan-stripe' ),
                'type'        => 'text',
                'description' => __( 'Get your API keys from your stripe account.', 'dokan-stripe' ),
                'default'     => ''
            ),
            'publishable_key' => array(
                'title'       => __( 'Publishable Key', 'dokan-stripe' ),
                'type'        => 'text',
                'description' => __( 'Get your API keys from your stripe account.', 'dokan-stripe' ),
                'default'     => ''
            ),
            'client_id' => array(
                'title'       => __( 'Client ID', 'dokan-stripe' ),
                'type'        => 'text',
                'description' => __( 'Get your client ID from your stripe account, the Apps menu.', 'dokan-stripe' ),
                'default'     => ''
            ),
            'test-credentials-title' => array(
                'title' => __( 'Test credentials', 'dokan-stripe' ),
                'type'  => 'title',
            ),
            'test_secret_key' => array(
                'title'       => __( 'Test Secret Key', 'dokan-stripe' ),
                'type'        => 'text',
                'description' => __( 'Get your API keys from your stripe account.', 'dokan-stripe' ),
                'default'     => ''
            ),
            'test_publishable_key' => array(
                'title'       => __( 'Test Publishable Key', 'dokan-stripe' ),
                'type'        => 'text',
                'description' => __( 'Get your API keys from your stripe account.', 'dokan-stripe' ),
                'default'     => ''
            ),
            'test_client_id' => array(
                'title'       => __( 'Test Client ID', 'dokan-stripe' ),
                'type'        => 'text',
                'description' => __( 'Get your client ID from your stripe account, the Apps menu.', 'dokan-stripe' ),
                'default'     => ''
            ),
        );
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     */
    function admin_options() {
    ?>
        <h3><?php _e( 'Stripe Connect', 'dokan-stripe' ); ?></h3>
        <p><?php _e( 'Stripe works by adding credit card fields on the checkout and then sending the details to Stripe for verification.', 'dokan-stripe' ); ?></p>

        <?php if ( in_array( get_option( 'woocommerce_currency' ), array( 'AED','AFN','ALL','AMD','ANG','AOA','ARS','AUD','AWG','AZN','BAM','BBD','BDT','BGN','BIF','BMD','BND','BOB','BRL','BSD','BWP','BZD','CAD','CDF','CHF','CLP','CNY','COP','CRC','CVE','CZK','DJF','DKK','DOP','DZD','EEK','EGP','ETB','EUR','FJD','FKP','GBP','GEL','GIP','GMD','GNF','GTQ','GYD','HKD','HNL','HRK','HTG','HUF','IDR','ILS','INR','ISK','JMD','JPY','KES','KGS','KHR','KMF','KRW','KYD','KZT','LAK','LBP','LKR','LRD','LSL','LTL','LVL','MAD','MDL','MGA','MKD','MNT','MOP','MRO','MUR','MVR','MWK','MXN','MYR','MZN','NAD','NGN','NIO','NOK','NPR','NZD','PAB','PEN','PGK','PHP','PKR','PLN','PYG','QAR','RON','RSD','RUB','RWF','SAR','SBD','SCR','SEK','SGD','SHP','SLL','SOS','SRD','STD','SVC','SZL','THB','TJS','TOP','TRY','TTD','TWD','TZS','UAH','UGX','USD','UYU','UZS','VEF','VND','VUV','WST','XAF','XCD','XOF','XPF','YER','ZAR','ZMW' ) ) ) { ?>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table><!--/.form-table-->

        <?php } else { ?>

            <div class="inline error">
                <p>
                    <strong><?php _e( 'Gateway Disabled', 'dokan-stripe' ); ?></strong>
                    <?php echo __( 'Choose a currency supported by Stripe as your store currency to enable Stripe Connect.', 'dokan-stripe' ); ?>
                </p>
            </div>
        <?php
        } /* End check currency*/
    }

    /**
     * Get Stripe amount to pay
     * @return float
     */
    public function get_stripe_amount( $total ) {

        switch ( get_woocommerce_currency() ) {
            /* Zero decimal currencies*/
            case 'BIF' :
            case 'CLP' :
            case 'DJF' :
            case 'GNF' :
            case 'JPY' :
            case 'KMF' :
            case 'KRW' :
            case 'MGA' :
            case 'PYG' :
            case 'RWF' :
            case 'VND' :
            case 'VUV' :
            case 'XAF' :
            case 'XOF' :
            case 'XPF' :
                $total = absint( $total );
                break;
            default :
                $total = $total * 100; /* In cents*/
                break;
        }
        return $total;
    }

    /**
     * Payment form on checkout page
     */
    function payment_fields() {
        $checked = 1;
        ?>
        <fieldset>
            <?php
                if ( $this->description ) {
                    echo wpautop( esc_html( $this->description ) );
                }
                if ( $this->testmode == 'yes' ) {
                    echo '<p>' . __( 'TEST MODE ENABLED. In test mode, you can use the card number 4242424242424242 with any CVC and a valid expiration date.', 'dokan-stripe' ) . '</p>';
                }
            ?>

            <?php if ( is_user_logged_in() && ( $credit_cards = get_user_meta( get_current_user_id(), '_stripe_customer_id', false ) ) ) : ?>
                <p class="form-row form-row-wide">

                    <a class="button" style="float:right;" href="<?php echo get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ); ?>#saved-cards"><?php _e( 'Manage cards', 'dokan-stripe' ); ?></a>

                    <?php foreach ( $credit_cards as $i => $credit_card ) : if ( empty($credit_card['active_card']) ) continue; ?>
                        <input type="radio" id="stripe_card_<?php echo $i; ?>" name="stripe_customer_id" style="width:auto;" value="<?php echo $i; ?>" />
                        <label style="display:inline;" for="stripe_customer_<?php echo $i; ?>"><?php _e( 'Card ending with', 'dokan-stripe' ); ?> <?php echo $credit_card['active_card']; ?> (<?php echo $credit_card['exp_month'] . '/' . $credit_card['exp_year'] ?>)</label><br />
                    <?php endforeach; ?>

                    <input type="radio" id="new" name="stripe_customer_id" style="width:auto;" <?php checked( 1, 1 ) ?> value="new" /> <label style="display:inline;" for="new"><?php _e( 'Use a new credit card', 'dokan-stripe' ); ?></label>

                </p>
                <div class="clear"></div>
            <?php endif; ?>

            <div class="stripe_new_card" <?php if ( $checked === 0 ) : ?> style="display:none;"<?php endif; ?>
                data-description=""
                data-amount="<?php echo $this->get_stripe_amount( WC()->cart->total ); ?>"
                data-name="<?php echo sprintf( __( '%s', 'dokan-stripe' ), get_bloginfo( 'name' ) ); ?>"
                data-label="<?php _e( 'Confirm and Pay', 'dokan-stripe' ); ?>"
                data-currency="<?php echo strtolower( get_woocommerce_currency() ); ?>"
                data-image=""
                >
                <?php if ( ! $this->stripe_checkout ) : ?>
                    <?php $this->credit_card_form( array( 'fields_have_names' => false ) ); ?>
                <?php endif; ?>
            </div>
        </fieldset>
        <?php

    }

    /**
     * Saved for previous card
     * @param  integer $customer_id
     * @return object
     */
    public function get_saved_cards( $customer_id ) {

        if ( false === ( $cards = get_transient( 'stripe_cards_' . $customer_id ) ) ) {
            $response = $this->stripe_request( array(
                'limit'       => 100
            ), 'customers/' . $customer_id . '/cards', 'GET' );

            if ( is_wp_error( $response ) ) {
                return false;
            }

            $cards = $response->data;

            set_transient( 'stripe_cards_' . $customer_id, $cards, HOUR_IN_SECONDS * 48 );
        }

        return $cards;
    }

    /**
     * payment_scripts function.
     *
     * Outputs scripts used for stripe payment
     *
     * @access public
     */
    function payment_scripts() {

        if ( ! is_checkout() ) {
            return;
        }

        if ( $this->stripe_checkout ) {

            wp_enqueue_script( 'stripe', 'https://checkout.stripe.com/v2/checkout.js', '', '2.0', true );
            wp_enqueue_script( 'woocommerce_stripe', plugins_url( 'assets/js/stripe_checkout.js', dirname( __FILE__ ) ), array( 'stripe' ), false, true );

        } else {

            wp_enqueue_script( 'stripe', 'https://js.stripe.com/v1/', '', '1.0', true );
            wp_enqueue_script( 'woocommerce_stripe', plugins_url( 'assets/js/stripe.js', dirname( __FILE__ ) ), array( 'stripe' ), false, true );

        }

        $stripe_params = array(
            'key'                  => $this->publishable_key,
            'i18n_terms'           => __( 'Please accept the terms and conditions first', 'dokan-stripe' ),
            'i18n_required_fields' => __( 'Please fill in required checkout fields first', 'dokan-stripe' ),
        );

        if ( is_checkout_pay_page() && isset( $_GET['order'] ) && isset( $_GET['order_id'] ) ) {
            $order_key = urldecode( $_GET['order'] );
            $order_id  = absint( $_GET['order_id'] );
            $order     = new WC_Order( $order_id );

            if ( $order->id == $order_id && $order->order_key == $order_key ) {
                $stripe_params['billing_first_name'] = $order->billing_first_name;
                $stripe_params['billing_last_name']  = $order->billing_last_name;
                $stripe_params['billing_address_1']  = $order->billing_address_1;
                $stripe_params['billing_address_2']  = $order->billing_address_2;
                $stripe_params['billing_state']      = $order->billing_state;
                $stripe_params['billing_city']       = $order->billing_city;
                $stripe_params['billing_postcode']   = $order->billing_postcode;
                $stripe_params['billing_country']    = $order->billing_country;
            }
        }

        wp_localize_script( 'woocommerce_stripe', 'wc_stripe_params', $stripe_params );
        wp_localize_script( 'woocommerce_stripe', 'wc_stripe_connect_params', $stripe_params );
    }

    /**
     * Get order details
     *
     * @param  int  $order_id
     * @param  int  $seller_id
     *
     * @return array
     */
    public function get_dokan_order( $order_id, $seller_id ) {
        global $wpdb;

        $sql = "SELECT *
                FROM {$wpdb->prefix}dokan_orders AS do
                WHERE
                    do.seller_id = %d AND
                    do.order_id = %d";

        return $wpdb->get_row( $wpdb->prepare( $sql, $seller_id, $order_id ) );
    }

    /**
     * Process the payment
     */
    function process_payment( $order_id ) {
        global $woocommerce, $wpdb, $ignitewoo_vendors;

        $customer_id  = 0;
        $stripe_token = isset( $_POST['stripe_token'] ) ? woocommerce_clean( $_POST['stripe_token'] ) : '';

        $order        = new WC_Order( $order_id );

        try {

            if ( ! method_exists( 'Stripe', 'setApiKey' ) ) {
                require_once 'lib/Stripe.php';
            }

            Stripe::setApiKey( $this->secret_key );

            /* Check if paying via customer ID (saved credit card?)*/
            if ( isset( $_POST['stripe_customer_id'] ) && $_POST['stripe_customer_id'] !== 'new' && is_user_logged_in() ) {

                $customer_ids = get_user_meta( get_current_user_id(), '_stripe_customer_id', false );

                if ( isset( $customer_ids[ $_POST['stripe_customer_id'] ]['customer_id'] ) ) {
                    $customer_id = $customer_ids[ $_POST['stripe_customer_id'] ]['customer_id'];
                } else {
                    throw new Exception( __( 'Invalid card.', 'dokan-stripe' ) );
                }
            }

            /* Else, Check token*/
            else if ( empty( $stripe_token ) ) {
                throw new Exception( __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'dokan-stripe' ) );
            }

            /* Check amount*/
            if ( $order->order_total * 100 < 50 ) {
                throw new Exception( __( 'Minimum order total is 0.50', 'dokan-stripe' ) );
            }

            if ( is_user_logged_in() && ! $customer_id && $stripe_token ) {

                $customer_id = $this->add_customer( $order, $stripe_token );

            } else if ( !is_user_logged_in() ) {

                if ( !empty( $woocommerce->session->stripe_guest_user_token ) ) {

                    $customer_id = $woocommerce->session->stripe_guest_user_token;

                } else {

                    $customer_id = $this->add_customer( $order, $stripe_token );

                    $woocommerce->session->set( 'stripe_guest_user_token', $customer_id );
                }
            }

            $charge_ids   = array();
            $currency     = strtolower( get_woocommerce_currency() );
            $order_desc   = sprintf( __( '%s - Order %s', 'dokan-stripe' ), esc_html( get_bloginfo( 'name' ) ), $order->get_order_number() );

            $has_suborder = get_post_meta( $order_id, 'has_sub_order', true );
            $all_orders   = array();

            // put orders in an array
            // if has sub-orders, pick only sub-orders
            // if it's a single order, get the single order only
            if ( $has_suborder == '1' ) {
                $sub_orders = get_children( array( 'post_parent' => $order_id, 'post_type' => 'shop_order' ) );

                foreach ($sub_orders as $order_post) {
                    $sub_order    = new WC_Order( $order_post->ID );
                    $all_orders[] = $sub_order;
                }

            } else {
                $all_orders[] = $order;
            }

            if ( ! $all_orders ) {
                throw new Exception( __( 'No orders found to process!', 'dokan-stripe' ) );
            }

            // seems like we have some orders to process
            // iterate through orders and fetch the net amount and fees
            foreach ($all_orders as $tmp_order) {

                $seller_id    = $tmp_order->post->post_author;
                $do_order     = $this->get_dokan_order( $tmp_order->id, $seller_id );

                // in-case we can't find the order
                if ( ! $do_order ) {
                    throw new Exception( __( 'Something went wrong and the order can not be processed!', 'dokan-stripe' ) );
                }

                $fee          = floatval( $do_order->order_total ) - floatval( $do_order->net_amount );
                $access_token = get_user_meta( $seller_id, '_stripe_connect_access_key', true );
                $token        = Stripe_Token::create( array( 'customer' => $customer_id ), $access_token );

                $charge = Stripe_Charge::create( array(
                    'amount'          => round( $do_order->order_total, 2 ) * 100,
                    'currency'        => $currency,
                    'application_fee' => round( $fee, 2 ) * 100,
                    'description'     => $order_desc,
                    'card'            => ! empty( $token->id ) ? $token->id : $stripe_token
                ), $access_token );

                $charge_ids[ $seller_id ] = $charge->id;

                // if it's a sub-order, add the charge id to sub-order
                if ( $order->id !== $tmp_order->id ) {
                    $tmp_order->add_order_note( sprintf( __( 'Stripe order %s payment completed with charge ID: %s', 'dokan-stripe' ), $tmp_order->get_order_number(), $charge->id ) );
                }
            }

        } catch( Exception $e ) {

            /* Add order note*/
            $order->add_order_note( sprintf( __( 'Stripe Payment Error: %s', 'dokan-stripe' ), $e->getMessage() ) );
            update_post_meta( $order->id, '_dwh_stripe_charge_error', $e->getMessage());

            wc_add_notice( __( 'Error: ', 'dokan-stripe' ) . $e->getMessage() );
            return;
        }

        /* Add order note*/
        $order->add_order_note( sprintf( __( 'Stripe order %s payment completed (Charge IDs: %s)', 'dokan-stripe' ), $order->get_order_number(), implode( ', ', $charge_ids ) ) );

        /* Payment complete*/
        $order->payment_complete();

        foreach ($charge_ids as $seller_id => $charge_id ) {
            $meta_key = '_dokan_stripe_charge_id_' . $seller_id;
            update_post_meta( $order->id, $meta_key, $charge_id);
        }

        /* Return redirect URL to thank you page*/
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order )
        );
    }

    /**
     * add_customer function.
     *
     * @param mixed   $stripe_token
     *
     * @return void
     */
    function add_customer( $order, $stripe_token ) {

        if ( ! $stripe_token ) {
            return;
        }

        require_once 'lib/Stripe.php';

        Stripe::setApiKey( $this->secret_key );

        $customer = Stripe_Customer::create( array(
                'email'       => $order->billing_email,
                'description' => 'Customer: ' . $order->shipping_first_name . ' ' . $order->shipping_last_name,
                "card"        => $stripe_token,
                'expand[]'    => 'default_card'
            )
        );

        if ( empty( $customer->id ) ) {
            return;
        }

        if ( !is_user_logged_in() ) {
            return $customer->id;
        }

        if ( !empty( $customer->active_card->last4 )) {
            add_user_meta( get_current_user_id(), '_stripe_customer_id', array(
                'customer_id' => $customer->id,
                'active_card' => !empty( $customer->active_card->last4 ) ? $customer->active_card->last4 : '',
                'exp_year'    => !empty( $customer->active_card->exp_year ) ? $customer->active_card->exp_year : '',
                'exp_month'   => !empty( $customer->active_card->exp_month ) ? $customer->active_card->exp_month : '',
            ) );
        }


        return $customer->id;
    }


    /**
     * Maintain Stripe request
     *
     * @param  array $request
     * @param  string $api
     *
     * @return array
     */
    function stripe_request( $request, $api = 'charges' ) {
        global $woocommerce;

        $response = wp_remote_post( $this->api_endpoint . 'v1/' . $api, array(
                'method'        => 'POST',
                'headers'       => array(
                'Authorization' => 'Basic ' . base64_encode( $this->secret_key . ':' )
            ),
            'body'          => $request,
            'timeout'       => 70,
            'sslverify'     => false,
            'user-agent'    => 'WooCommerce ' . $woocommerce->version
        ));

        if ( is_wp_error($response) ) {
            return new WP_Error( 'stripe_error', __('There was a problem connecting to the payment gateway.', 'dokan-stripe') );
        }

        if ( empty($response['body']) ) {
            return new WP_Error( 'stripe_error', __('Empty response.', 'dokan-stripe') );
        }

        $parsed_response = json_decode( $response['body'] );

        /* Handle response */
        if ( ! empty( $parsed_response->error ) ) {

            return new WP_Error( 'stripe_error', $parsed_response->error->message );

        } elseif ( empty( $parsed_response->id ) ) {

            return new WP_Error( 'stripe_error', __('Invalid response.', 'dokan-stripe') );

        }

        return $parsed_response;
    }
}
