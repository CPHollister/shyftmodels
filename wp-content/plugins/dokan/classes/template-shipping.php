<?php
/**
 * Dokan Shipping Class
 *
 * @author weDves
 */

class Dokan_Template_Shipping {

    public static function init() {
        static $instance = false;

        if ( !$instance ) {
            $instance = new Dokan_Template_Shipping();
        }

        return $instance;
    }

    public function __construct() {

        add_action( 'woocommerce_shipping_init', array($this, 'include_shipping' ) );
        add_action( 'woocommerce_shipping_methods', array($this, 'register_shipping' ) );
        add_action( 'woocommerce_product_tabs', array($this, 'register_product_tab' ) );
        add_action( 'woocommerce_after_checkout_validation', array($this, 'validate_country' ) );
    }


    /**
     * Include main shipping integration
     *
     * @return void
     */
    function include_shipping() {
        require_once DOKAN_INC_DIR . '/shipping-gateway/shipping.php';
    }

    /**
     * Register shipping method
     *
     * @param array $methods
     * @return array
     */
    function register_shipping( $methods ) {
        $methods[] = 'Dokan_WC_Shipping';

        return $methods;
    }

    /**
     * Validate the shipping area
     *
     * @param  array $posted
     * @return void
     */
    function validate_country( $posted ) {
        // print_r($posted);

        $shipping_method = WC()->session->get( 'chosen_shipping_methods' );

        // per product shipping was not chosen
        if ( ! is_array( $shipping_method ) || !in_array( 'dokan_product_shipping', $shipping_method ) ) {
            return;
        }

        if ( isset( $posted['ship_to_different_address'] ) && $posted['ship_to_different_address'] == '1' ) {
            $shipping_country = $posted['shipping_country'];
        } else {
            $shipping_country = $posted['billing_country'];
        }

        // echo $shipping_country;
        $packages = WC()->shipping->get_packages();
        $packages = reset( $packages );

        if ( !isset( $packages['contents'] ) ) {
            return;
        }

        $products = $packages['contents'];
        $destination_country = isset( $packages['destination']['country'] ) ? $packages['destination']['country'] : '';
        $destination_state = isset( $packages['destination']['state'] ) ? $packages['destination']['state'] : '';


        $errors = array();
        foreach ( $products as $key => $product) {

            $seller_id = get_post_field( 'post_author', $product['product_id'] );

            if ( ! Dokan_WC_Shipping::is_shipping_enabled_for_seller( $seller_id ) ) {
                continue;
            }

            if( Dokan_WC_Shipping::is_product_disable_shipping( $product['product_id'] ) ) {
                continue;
            }

            $dps_country_rates = get_user_meta( $seller_id, '_dps_country_rates', true );
            $dps_state_rates   = get_user_meta( $seller_id, '_dps_state_rates', true );

            $has_found = false;
            $dps_country = ( isset( $dps_country_rates ) ) ? $dps_country_rates : array();
            $dps_state = ( isset( $dps_state_rates[$destination_country] ) ) ? $dps_state_rates[$destination_country] : array();

            if ( array_key_exists( $destination_country, $dps_country ) ) {

                if ( $dps_state ) {
                    if ( array_key_exists( $destination_state, $dps_state ) ) {
                        $has_found = true;
                    } elseif ( array_key_exists( 'everywhere', $dps_state ) ) {
                        $has_found = true;
                    }
                } else {
                    $has_found = true;
                }
            } else {
                if ( array_key_exists( 'everywhere', $dps_country ) ) {
                    $has_found = true;
                }
            }

            if ( ! $has_found ) {
                $errors[] = sprintf( '<a href="%s">%s</a>', get_permalink( $product['product_id'] ), get_the_title( $product['product_id'] ) );
            }
        }

        if ( $errors ) {
            if ( count( $errors ) == 1 ) {
                $message = sprintf( __( 'This product does not ship to your chosen location: %s'), implode( ', ', $errors ) );
            } else {
                $message = sprintf( __( 'These products do not ship to your chosen location.: %s'), implode( ', ', $errors ) );
            }

            wc_add_notice( $message, 'error' );
        }
    }


     /**
     * Adds a seller tab in product single page
     *
     * @param array $tabs
     * @return array
     */
    function register_product_tab( $tabs ) {
        global $post;

        if( get_post_meta( $post->ID, '_disable_shipping', true ) == 'yes' ) {
            return $tabs;
        }

        if( get_post_meta( $post->ID, '_downloadable', true ) == 'yes' ) {
            return $tabs;
        }

        $enabled = get_user_meta( $post->post_author, '_dps_shipping_enable', true );
        if ( $enabled != 'yes' ) {
            return $tabs;
        }

        if ( 'yes' != get_option( 'woocommerce_calc_shipping' ) ) {
            return $tabs;
        }

        $tabs['shipping'] = array(
            'title' => __( 'Shipping', 'dokan' ),
            'priority' => 12,
            'callback' => array($this, 'shipping_tab')
        );

        return $tabs;
    }

    /**
     * Callback for Register_prouduct_tab function
     * @return [type] [description]
     */
    function shipping_tab() {
        global $post;

        $dps_processing        = get_user_meta( $post->post_author, '_dps_pt', true );
        $from              = get_user_meta( $post->post_author, '_dps_form_location', true );
        $dps_country_rates = get_user_meta( $post->post_author, '_dps_country_rates', true );
        $dps_state_rates   = get_user_meta( $post->post_author, '_dps_state_rates', true );
        $shipping_policy   = get_user_meta( $post->post_author, '_dps_ship_policy', true );
        $refund_policy     = get_user_meta( $post->post_author, '_dps_refund_policy', true );

        // Store wide shipping info
        $store_shipping_type_price    = (float)get_user_meta( $post->post_author, '_dps_shipping_type_price', true );
        $additional_product_cost      = (float)get_post_meta( $post->ID, '_additional_price', true );
        $base_shipping_type_price     = ( (float)$store_shipping_type_price + ( ($additional_product_cost) ? (float)$additional_product_cost : 0 ) );
        $additional_qty_product_price = get_post_meta( $post->ID, '_additional_qty', true );
        $dps_additional_qty           = get_user_meta( $post->post_author, '_dps_additional_qty', true );
        $additional_qty_price         = ( $additional_qty_product_price ) ? $additional_qty_product_price : $dps_additional_qty;
        $product_processing_time      = get_post_meta( $post->ID, '_dps_processing_time', true );
        $processing_time              = ( $product_processing_time ) ? $product_processing_time : $dps_processing;

        $country_obj = new WC_Countries();
        $countries   = $country_obj->countries;
        $states      = $country_obj->states;
        ?>

        <?php if ( $processing_time ) { ?>
            <p>
                <strong>
                    <?php _e( 'Ready to ship in', 'dokan' ); ?> <?php echo dokan_get_processing_time_value( $processing_time ); ?>

                    <?php
                    if ( $from ) {
                        echo __( 'from', 'dokan' ) . ' ' . $countries[$from];
                    }
                    ?>
                </strong>
            </p>
            <hr>
        <?php } ?>

        <?php if ( $dps_country_rates ) { ?>

            <h4><?php _e( 'Shipping Calculation:', 'dokan' ); ?></h4>

            <div class="dokan-shipping-calculate-wrapper dokan-form-inline">

                <div class="dokan-shipping-country-wrapper dokan-form-group dokan-w3">

                    <label for="dokan-shipping-country" class="dokan-control-label"><?php _e( 'Country', 'dokan' ); ?></label>
                    <select name="dokan-shipping-country" id="dokan-shipping-country" class="dokan-shipping-country dokan-form-control" data-product_id="<?php echo $post->ID; ?>" data-author_id="<?php echo $post->post_author; ?>">
                        <option value=""><?php _e( '--Select Country--', 'dokan' ); ?></option>
                        <?php foreach ( $dps_country_rates as $country => $cost ) { ?>
                            <option value="<?php echo $country; ?>"><?php echo ( $country == 'everywhere' ) ? _e( 'Other Countries' ) : $countries[$country]; ?></option>
                        <?php } ?>
                    </select>

                </div>

                <div class="dokan-shipping-state-wrapper dokan-form-group">

                </div>

                <div class="dokan-shipping-qty-wrapper dokan-form-group dokan-w3">
                    <label for="dokan-shipping-qty" class="dokan-control-label"><?php _e( 'Quantity', 'dokan' ); ?></label>
                    <input type="number" class="dokan-shipping-qty dokan-form-control" id="dokan-shipping-qty" name="dokan-shipping-qty" value="1" placeholder="1">
                </div>

                <button class="dokan-btn dokan-btn-theme dokan-shipping-calculator dokan-w3"><?php _e( 'Get Shipping Cost', 'dokan' ); ?></button>

                <div class="dokan-clearfix"></div>

                <div class="dokan-shipping-price-wrapper dokan-form-group">

                </div>

                <div class="dokan-clearfix"></div>
            </div>

        <?php } ?>

        <?php if ( $shipping_policy ) { ?>
            <p>&nbsp;</p>
            <strong><?php _e( 'Shipping Policy', 'dokan' ); ?></strong>
            <?php echo wpautop( $shipping_policy ); ?>
        <?php } ?>

        <?php if ( $refund_policy ) { ?>
            <hr>
            <p>&nbsp;</p>
            <strong><?php _e( 'Refund Policy', 'dokan' ); ?></strong>
            <hr>
            <?php echo wpautop( $refund_policy ); ?>
        <?php } ?>
        <?php
    }




}