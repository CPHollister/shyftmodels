<?php
global $post;

$user_id                 = get_current_user_id();
$processing_time         = dokan_get_shipping_processing_times();

$_additional_price       = get_post_meta( $post->ID, '_additional_price', true );
$_additional_qty         = get_post_meta( $post->ID, '_additional_qty', true );
$_processing_time        = get_post_meta( $post->ID, '_dps_processing_time', true );

$dps_shipping_type_price = get_user_meta( $user_id, '_dps_shipping_type_price', true );
$dps_additional_qty      = get_user_meta( $user_id, '_dps_additional_qty', true );
$dps_pt                  = get_user_meta( $user_id, '_dps_pt', true );

$porduct_shipping_pt     = ( $_processing_time ) ? $_processing_time : $dps_pt;
?>

<?php do_action( 'dokan_product_options_shipping_before' ); ?>

<div class="dokan-form-horizontal dokan-product-shipping">
    <input type="hidden" name="product_shipping_class" value="0">
    <?php if ( 'yes' == get_option( 'woocommerce_calc_shipping' ) ): ?>
        <div class="dokan-form-group">
            <label class="dokan-w4 dokan-control-label" for="_disable_shipping"><?php _e( 'Disable Shipping', 'dokan' ); ?></label>
            <div class="dokan-w8 dokan-text-left">
                <?php dokan_post_input_box( $post->ID, '_disable_shipping', array( 'label' => __( 'Disable shipping for this product', 'dokan' ) ), 'checkbox' ); ?>
            </div>
        </div>
    <?php endif ?>

    <div class="dokan-form-group">
        <label class="dokan-w4 dokan-control-label" for="_backorders"><?php echo __( 'Weight', 'dokan' ) . ' (' . get_option( 'woocommerce_weight_unit' ) . ')'; ?></label>
        <div class="dokan-w4 dokan-text-left">
            <?php dokan_post_input_box( $post->ID, '_weight' ); ?>
        </div>
    </div>

    <div class="dokan-form-group">
        <label class="dokan-w4 dokan-control-label" for="_backorders"><?php echo __( 'Dimensions', 'dokan' ) . ' (' . get_option( 'woocommerce_dimension_unit' ) . ')'; ?></label>
        <div class="dokan-w8 dokan-text-left product-dimension">
            <?php dokan_post_input_box( $post->ID, '_length', array( 'class' => 'form-control col-sm-1', 'placeholder' => __( 'length', 'dokan' ) ), 'number' ); ?>
            <?php dokan_post_input_box( $post->ID, '_width', array( 'class' => 'form-control col-sm-1', 'placeholder' => __( 'width', 'dokan' ) ), 'number' ); ?>
            <?php dokan_post_input_box( $post->ID, '_height', array( 'class' => 'form-control col-sm-1', 'placeholder' => __( 'height', 'dokan' ) ), 'number' ); ?>
        </div>
    </div>

    <?php if ( 'yes' == get_option( 'woocommerce_calc_shipping' ) ): ?>
        <div class="dokan-form-group hide_if_disable">
            <label class="dokan-w4 dokan-control-label" for="_overwrite_shipping"><?php _e( 'Override Shipping', 'dokan' ); ?></label>
            <div class="dokan-w8 dokan-text-left">
                <?php dokan_post_input_box( $post->ID, '_overwrite_shipping', array( 'label' => __( 'Override default shipping cost for this product', 'dokan' ) ), 'checkbox' ); ?>
            </div>
        </div>

        <div class="dokan-form-group dokan-shipping-price dokan-shipping-type-price show_if_override hide_if_disable">
            <label class="dokan-w4 dokan-control-label" for="shipping_type_price"><?php _e( 'Additional cost', 'dokan' ); ?></label>

            <div class="dokan-w4 dokan-text-left">
                <input id="shipping_type_price" value="<?php echo $_additional_price; ?>" name="_additional_price" placeholder="0.00" class="dokan-form-control" type="number" step="any">
            </div>
        </div>

        <div class="dokan-form-group dokan-shipping-price dokan-shipping-add-qty show_if_override hide_if_disable">
            <label class="dokan-w4 dokan-control-label" for="dps_additional_qty"><?php _e( 'Per Qty Additional Price', 'dokan' ); ?></label>

            <div class="dokan-w4 dokan-text-left">
                <input id="additional_qty" value="<?php echo ( $_additional_qty ) ? $_additional_qty : $dps_additional_qty; ?>" name="_additional_qty" placeholder="1.99" class="dokan-form-control" type="number" step="any">
            </div>
        </div>

        <div class="dokan-form-group dokan-shipping-price dokan-shipping-add-qty show_if_override hide_if_disable">
            <label class="dokan-w4 dokan-control-label" for="dps_additional_qty"><?php _e( 'Processing Time', 'dokan' ); ?></label>

            <div class="dokan-w4 dokan-text-left">
                <select name="_dps_processing_time" id="_dps_processing_time" class="dokan-form-control">
                    <?php foreach ( $processing_time as $processing_key => $processing_value ): ?>
                          <option value="<?php echo $processing_key; ?>" <?php selected( $porduct_shipping_pt, $processing_key ); ?>><?php echo $processing_value; ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
    <?php endif ?>

    <?php do_action( 'dokan_product_options_shipping' ); ?>
</div>
