<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<td>
    <input type="checkbox" name="variable_enabled[<?php echo $loop; ?>]" value="yes" <?php checked( $variation_post_status, 'publish' ); ?>>
</td>
<td class="upload_image" style="width:2% !important;">
    <span class="variation_placeholder_image" data-placeholder_image="<?php echo esc_attr( wc_placeholder_img_src() ); ?>"></span>
    <a href="#" class="upload_image_button <?php if ( $image_id > 0 ) echo 'dokan-img-remove'; ?>" rel="<?php echo esc_attr( $variation_id ); ?>">
        <img src="<?php if ( ! empty( $image ) ) echo esc_attr( $image ); else echo esc_attr( wc_placeholder_img_src() ); ?>" width="32px"/>
        <input type="hidden" name="upload_image_id[<?php echo $loop; ?>]" class="upload_image_id" value="<?php echo esc_attr( $image_id ); ?>" />
        <span class="overlay"></span>
    </a>
</td>
<td>
    <?php
        foreach ( $parent_data['attributes'] as $attribute ) {

            // Only deal with attributes that are variations
            if ( ! $attribute['is_variation'] ) {
                continue;
            }

            // Get current value for variation (if set)
            $variation_selected_value = isset( $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] ) ? $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] : '';

            // Name will be something like attribute_pa_color
            echo '<select name="attribute_' . sanitize_title( $attribute['name'] ) . '[' . $loop . ']" class="dokan-w3 dokan-form-control"><option value="">' . __( 'Any', 'dokan' ) . ' ' . esc_html( wc_attribute_label( $attribute['name'] ) ) . '&hellip;</option>';

            // Get terms for attribute taxonomy or value if its a custom attribute
            if ( $attribute['is_taxonomy'] ) {
                $post_terms = wp_get_post_terms( $parent_data['id'], $attribute['name'] );
                foreach ( $post_terms as $term ) {
                    echo '<option ' . selected( $variation_selected_value, $term->slug, false ) . ' value="' . esc_attr( $term->slug ) . '">' . apply_filters( 'woocommerce_variation_option_name', esc_html( $term->name ) ) . '</option>';
                }

            } else {

                $options = array_map( 'trim', explode( WC_DELIMITER, $attribute['value'] ) );

                foreach ( $options as $option ) {
                    echo '<option ' . selected( sanitize_title( $variation_selected_value ), sanitize_title( $option ), false ) . ' value="' . esc_attr( sanitize_title( $option ) ) . '">' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
                }

            }

            echo '</select>';
        }
    ?>

    <input type="hidden" name="variable_post_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $variation_id ); ?>" />
    <input type="hidden" class="variation_menu_order" name="variation_menu_order[<?php echo $loop; ?>]" value="<?php echo $loop; ?>" />
</td>
<td style="width:10% !important;">
    <input type="number" min="0" step="any" size="5" name="variable_regular_price[<?php echo $loop; ?>]" value="<?php if ( isset( $_regular_price ) ) echo esc_attr( $_regular_price ); ?>" class="dokan-form-control" placeholder="<?php _e( '0.00', 'dokan' ); ?>" size="10"/>
</td>
<td style="width:10% !important;">
    <?php if ( wc_product_sku_enabled() ) : ?>
        <input type="text" size="5" class="dokan-form-control" name="variable_sku[<?php echo $loop; ?>]" value="<?php if ( isset( $_sku ) ) echo esc_attr( $_sku ); ?>" placeholder="<?php echo esc_attr( $parent_data['sku'] ); ?>" size="10"/>
    <?php else : ?>
        <input type="hidden" name="variable_sku[<?php echo $loop; ?>]" value="<?php if ( isset( $_sku ) ) echo esc_attr( $_sku ); ?>" />
    <?php endif; ?>
</td>
<td style="width:25% !important;">
    <a href="#variation-edit-popup" class="dokan-btn dokan-btn-theme edit_variation"><i class="fa fa-pencil"></i></a>
    <a class="dokan-btn dokan-btn-theme remove_variation" data-variation_id=<?php echo $variation_id; ?>><i class="fa fa-trash-o"></i></a>
</td>














