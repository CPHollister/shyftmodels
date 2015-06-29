<?php

/**
 * new WordPress Widget format
 * Wordpress 2.8 and above
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class Dokan_Store_Location extends WP_Widget {

    /**
     * Constructor
     *
     * @return void
     **/
    public function __construct() {
        $widget_ops = array( 'classname' => 'dokan-store-location', 'description' => __( 'Dokan Seller Store Location', 'dokan' ) );
        $this->WP_Widget( 'dokan-store-location', __( 'Dokan: Store Location', 'dokan' ), $widget_ops );
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

        $title        = apply_filters( 'widget_title', $instance['title'] );
        $store_info   = dokan_get_store_info( get_query_var( 'author' ) );
        $map_location = isset( $store_info['location'] ) ? esc_attr( $store_info['location'] ) : '';

        echo $before_widget;

        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        ?>

        <div class="location-container">

            <?php if ( ! empty( $map_location ) ) { ?>
                <div id="dokan-store-location"></div>

                <script type="text/javascript">
                    jQuery(function($) {
                        <?php
                        $locations = explode( ',', $map_location );
                        $def_lat = isset( $locations[0] ) ? $locations[0] : 90.40714300000002;
                        $def_long = isset( $locations[1] ) ? $locations[1] : 23.709921;
                        ?>

                        var def_longval = <?php echo $def_long; ?>;
                        var def_latval = <?php echo $def_lat; ?>;

                        var curpoint = new google.maps.LatLng(def_latval, def_longval),
                            $map_area = $('#dokan-store-location');

                        var gmap = new google.maps.Map( $map_area[0], {
                            center: curpoint,
                            zoom: 15,
                            mapTypeId: window.google.maps.MapTypeId.ROADMAP
                        });

                        var marker = new window.google.maps.Marker({
                            position: curpoint,
                            map: gmap
                        });
                    })

                </script>
            <?php } ?>
        </div>

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
            'title' => __( 'Store Location', 'dokan' ),
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

add_action( 'widgets_init', create_function( '', "register_widget( 'Dokan_Store_Location' );" ) );