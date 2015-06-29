<?php

/**
 * new WordPress Widget format
 * Wordpress 2.8 and above
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class Dokan_Store_Contact_Form extends WP_Widget {

    /**
     * Constructor
     *
     * @return void
     **/
    public function __construct() {
        $widget_ops = array( 'classname' => 'dokan-store-contact', 'description' => __( 'Dokan Seller Contact Form', 'dokan' ) );
        $this->WP_Widget( 'dokan-store-contact-widget', __( 'Dokan: Store Contact Form', 'dokan' ), $widget_ops );
    }

    /**
     * Outputs the HTML for this widget.
     *
     * @param array  An array of standard parameters for widgets in this theme
     * @param array  An array of settings for this widget instance
     * @return void Echoes it's output
     **/
    function widget( $args, $instance ) {

        if ( ! dokan_is_store_page() ) {
            return;
        }

        extract( $args, EXTR_SKIP );

        $title      = apply_filters( 'widget_title', $instance['title'] );
        $seller_id  = (int) get_query_var( 'author' );
        $store_info = dokan_get_store_info( $seller_id );

        echo $before_widget;

        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        ?>

        <form id="dokan-form-contact-seller" action="" method="post" class="seller-form clearfix">
            <div class="ajax-response"></div>
            <ul>
                <li class="form-group">
                    <input type="text" name="name" value="" placeholder="<?php esc_attr_e( 'Your Name', 'dokan' ); ?>" class="form-control" minlength="5" required="required">
                </li>
                <li class="form-group">
                    <input type="email" name="email" value="" placeholder="<?php esc_attr_e( 'you@example.com', 'dokan' ); ?>" class="form-control" required="required">
                </li>
                <li class="form-group">
                    <textarea  name="message" maxlength="1000" cols="25" rows="6" value="" placeholder="<?php esc_attr_e( 'Type your messsage...', 'dokan' ); ?>" class="form-control" required="required"></textarea>
                </li>
            </ul>

            <?php wp_nonce_field( 'dokan_contact_seller' ); ?>
            <input type="hidden" name="seller_id" value="<?php echo $seller_id; ?>">
            <input type="hidden" name="action" value="dokan_contact_seller">
            <input type="submit" name="store_message_send" value="<?php esc_attr_e( 'Send Message', 'dokan' ); ?>" class="dokan-right dokan-btn dokan-btn-theme">
        </form>

        <?php

        echo $after_widget;
    }

    /**
     * Deals with the settings when they are saved by the admin. Here is
     * where any validation should be dealt with.
     *
     * @param array  An array of new settings as submitted by the admin
     * @param array  An array of the previous settings
     * @return array The validated and (if necessary) amended settings
     **/
    function update( $new_instance, $old_instance ) {

        // update logic goes here
        $updated_instance = $new_instance;
        return $updated_instance;
    }

    /**
     * Displays the form for this widget on the Widgets page of the WP Admin area.
     *
     * @param array  An array of the current settings for this widget
     * @return void Echoes it's output
     **/
    function form( $instance ) {
        $instance = wp_parse_args( (array) $instance, array(
            'title' => __( 'Contact Seller', 'dokan' ),
        ) );

        $title = $instance['title'];
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'dokan' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <?php
    }
}

add_action( 'widgets_init', create_function( '', "register_widget( 'Dokan_Store_Contact_Form' );" ) );