<?php
/**
 * Dokan email handler class
 *
 * @package Dokan
 */
class Dokan_Email {

    public static function init() {
        static $instance = false;

        if ( !$instance ) {
            $instance = new self;
        }

        return $instance;
    }

    /**
     * Get from name for email.
     *
     * @access public
     * @return string
     */
    function get_from_name() {
        return wp_specialchars_decode( esc_html( get_option( 'woocommerce_email_from_name' ) ), ENT_QUOTES );
    }

    /**
     * Get from email address.
     *
     * @access public
     * @return string
     */
    function get_from_address() {
        return sanitize_email( get_option( 'woocommerce_email_from_address' ) );
    }


    /**
     * Get admin email address
     *
     * @return string
     */
    function admin_email() {
        return apply_filters( 'dokan_email_admin_mail', get_option( 'admin_email' ) );
    }


    /**
     * Get user agent string
     *
     * @return string
     */
    function get_user_agent() {
        return substr( $_SERVER['HTTP_USER_AGENT'], 0, 150 );
    }


    /**
     * Replace currency HTML entities with symbol
     *
     * @param string $amount
     * @return string
     */
    function currency_symbol( $amount ) {
        $price = sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $amount );

        return html_entity_decode( $price );
    }


    /**
     * Send email to seller from the seller contact form
     *
     * @param string $seller_email
     * @param string $from_name
     * @param string $from_email
     * @param string $message
     */
    function contact_seller( $seller_email, $from_name, $from_email, $message ) {
        $template = DOKAN_INC_DIR . '/emails/contact-seller.php';
        ob_start();
        include $template;
        $body = ob_get_clean();

        $find = array(
            '%from_name%',
            '%from_email%',
            '%user_ip%',
            '%user_agent%',
            '%message%',
            '%site_name%',
            '%site_url%'
        );

        $replace = array(
            $from_name,
            $from_email,
            dokan_get_client_ip(),
            $this->get_user_agent(),
            $message,
            $this->get_from_name(),
            home_url()
        );

        $subject = sprintf( __( '"%s" sent you a message from your "%s" store', 'dokan' ), $from_name, $this->get_from_name() );
        $body = str_replace( $find, $replace, $body);
        $headers = array( "Reply-To: {$from_name}<{$from_email}>" );

        $this->send( $seller_email, $subject, $body, $headers );
        do_action( 'after_send_contact_seller_mail', $seller_email, $subject, $body );
    }


    /**
     * Prepare body for withdraw email
     *
     * @param string $body
     * @param WP_User $user
     * @param float $amount
     * @param string $method
     * @param string $note
     * @return string
     */
    function prepare_withdraw( $body, $user, $amount, $method, $note = '' ) {
        $find = array(
            '%username%',
            '%amount%',
            '%method%',
            '%profile_url%',
            '%withdraw_page%',
            '%site_name%',
            '%site_url%',
            '%notes%'
        );

        $replace = array(
            $user->user_login,
            $this->currency_symbol( $amount ),
            dokan_withdraw_get_method_title( $method ),
            admin_url( 'user-edit.php?user_id=' . $user->ID ),
            admin_url( 'admin.php?page=dokan-withdraw' ),
            $this->get_from_name(),
            home_url(),
            $note
        );

        $body = str_replace( $find, $replace, $body);

        return $body;
    }


    /**
     * Send admin email notification when a new withdraw request is made
     *
     * @param WP_User $user
     * @param float $amount
     * @param string $method
     */
    function new_withdraw_request( $user, $amount, $method ) {
        $template = DOKAN_INC_DIR . '/emails/withdraw-new.php';
        ob_start();
        include $template;
        $body = ob_get_clean();

        $subject = sprintf( __( '[%s] New Withdraw Request', 'dokan' ), $this->get_from_name() );
        $body = $this->prepare_withdraw( $body, $user, $amount, $method );

        $this->send( $this->admin_email(), $subject, $body );
        do_action( 'after_new_withdraw_request', $this->admin_email(), $subject, $body );
    }


    /**
     * Send email to user once a withdraw request is approved
     *
     * @param int $user_id
     * @param float $amount
     * @param string $method
     */
    function withdraw_request_approve( $user_id, $amount, $method ) {
        $template = DOKAN_INC_DIR . '/emails/withdraw-approve.php';
        ob_start();
        include $template;
        $body = ob_get_clean();

        $user = get_user_by( 'id', $user_id );
        $subject = sprintf( __( '[%s] Your Withdraw Request has been approved', 'dokan' ), $this->get_from_name() );
        $body = $this->prepare_withdraw( $body, $user, $amount, $method );

        $this->send( $user->user_email, $subject, $body );
        do_action( 'after_withdraw_request_approve', $user->user_email, $subject, $body );
    }


    /**
     * Send email to user once a order has been cancelled
     *
     * @param int $user_id
     * @param float $amount
     * @param string $method
     * @param string $note
     */
    function withdraw_request_cancel( $user_id, $amount, $method, $note = '' ) {
        $template = DOKAN_INC_DIR . '/emails/withdraw-cancel.php';
        ob_start();
        include $template;
        $body = ob_get_clean();

        $user = get_user_by( 'id', $user_id );
        $subject = sprintf( __( '[%s] Your Withdraw Request has been cancelled', 'dokan' ), $this->get_from_name() );
        $body = $this->prepare_withdraw( $body, $user, $amount, $method, $note );

        $this->send( $user->user_email, $subject, $body );
        do_action( 'after_withdraw_request_cancel', $user->user_email, $subject, $body );
    }


    /**
     * Send email to admin once a new seller registered
     *
     * @param int $seller_id
     */
    function new_seller_registered_mail( $seller_id ) {
        $template = DOKAN_INC_DIR . '/emails/new-seller-registered.php';

        ob_start();
        include $template;
        $body = ob_get_clean();

        $seller = get_user_by( 'id', $seller_id );

        $find = array(
            '%seller_name%',
            '%store_url%',
            '%seller_edit%',
            '%site_name%',
            '%site_url%'
        );

        $replace = array(
            $seller->display_name,
            dokan_get_store_url( $seller_id ),
            admin_url( 'user-edit.php?user_id=' . $seller_id ),
            $this->get_from_name(),
            home_url(),
        );

        $body = str_replace( $find, $replace, $body);
        $subject = sprintf( __( '[%s] New Seller Registered', 'dokan' ), $this->get_from_name() );

        $this->send( $this->admin_email(), $subject, $body );
        do_action( 'after_new_seller_registered_mail', $this->admin_email(), $subject, $body );
    }




    /**
     * Send email to admin once a product is added
     *
     * @param int $product_id
     * @param string $status
     */
    function new_product_added( $product_id, $status = 'pending' ) {
        $template = DOKAN_INC_DIR . '/emails/new-product-pending.php';

        if ( $status == 'publish' ) {
            $template = DOKAN_INC_DIR . '/emails/new-product.php';
        }
        ob_start();
        include $template;
        $body = ob_get_clean();

        $product = get_product( $product_id );
        $seller = get_user_by( 'id', $product->post->post_author );
        $category = wp_get_post_terms($product->id, 'product_cat', array( 'fields' => 'names' ) );
        $category_name = $category ? reset( $category ) : 'N/A';

        $find = array(
            '%title%',
            '%price%',
            '%seller_name%',
            '%seller_url%',
            '%category%',
            '%product_link%',
            '%site_name%',
            '%site_url%'
        );

        $replace = array(
            $product->get_title(),
            $this->currency_symbol( $product->get_price() ),
            $seller->display_name,
            dokan_get_store_url( $seller->ID ),
            $category_name,
            admin_url( 'post.php?action=edit&post=' . $product_id ),
            $this->get_from_name(),
            home_url(),
        );

        $body = str_replace( $find, $replace, $body);
        $subject = sprintf( __( '[%s] New Product Added', 'dokan' ), $this->get_from_name() );

        $this->send( $this->admin_email(), $subject, $body );
        do_action( 'after_new_product_added', $this->admin_email(), $subject, $body );
    }


    /**
     * Send email to seller once a product is published
     *
     * @param WP_Post $post
     * @param WP_User $seller
     */
    function product_published( $post, $seller ) {

        $template = DOKAN_INC_DIR . '/emails/product-published.php';

        ob_start();
        include $template;
        $body = ob_get_clean();

        $product = get_product( $post->ID );

        $find = array(
            '%seller_name%',
            '%title%',
            '%product_link%',
            '%product_edit_link%',
            '%site_name%',
            '%site_url%'
        );

        $replace = array(
            $seller->display_name,
            $product->get_title(),
            get_permalink( $post->ID ),
            dokan_edit_product_url( $post->ID ),
            $this->get_from_name(),
            home_url(),
        );

        $body = str_replace( $find, $replace, $body);
        $subject = sprintf( __( '[%s] Your product has been approved!', 'dokan' ), $this->get_from_name() );

        $this->send( $seller->user_email, $subject, $body );
        do_action( 'after_product_published', $seller->user_email, $subject, $body );
    }

    /**
     * Send the email.
     *
     * @access public
     * @param mixed $to
     * @param mixed $subject
     * @param mixed $message
     * @param string $headers
     * @param string $attachments
     * @return void
     */
    function send( $to, $subject, $message, $headers = array() ) {
        add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
        add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );

        wp_mail( $to, $subject, $message, $headers );

        remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
        remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
    }
}