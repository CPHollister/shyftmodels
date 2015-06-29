<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

$attributes = maybe_unserialize( get_post_meta( $post_id, '_product_attributes', true ) );
$filter_attributes = array();
$add_variation_variable = array();
$variation_attribute_found = false;
$modified_attribute = array();
$attribute_taxonomies = wc_get_attribute_taxonomies();

// Get tax classes
$tax_classes = array_filter( array_map('trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
$tax_class_options = array();
$tax_class_options[''] = __( 'Standard', 'woocommerce' );
if ( $tax_classes ) {
    foreach ( $tax_classes as $class ) {
        $tax_class_options[ sanitize_title( $class ) ] = esc_attr( $class );
    }
}

$parent_data = array(
    'id'        => $post_id,
    'attributes' => $attributes,
    'tax_class_options' => $tax_class_options,
    'sku'       => get_post_meta( $post_id, '_sku', true ),
    'weight'    => get_post_meta( $post_id, '_weight', true ),
    'length'    => get_post_meta( $post_id, '_length', true ),
    'width'     => get_post_meta( $post_id, '_width', true ),
    'height'    => get_post_meta( $post_id, '_height', true ),
    'tax_class' => get_post_meta( $post_id, '_tax_class', true )
);

if ( ! $parent_data['weight'] )
    $parent_data['weight'] = '0.00';

if ( ! $parent_data['length'] )
    $parent_data['length'] = '0';

if ( ! $parent_data['width'] )
    $parent_data['width'] = '0';

if ( ! $parent_data['height'] )
    $parent_data['height'] = '0';

if ( $attributes ) {

    foreach( $attributes as $item => $attribute ) {

        if ( isset( $attribute['is_variation'] ) ) {
            $variation_attribute_found = true;
            break;
        }
    }

    foreach( $attributes as $item => $attribute ) {
        if ( $attribute['is_taxonomy'] ) {

            $post_terms = wp_get_post_terms( $parent_data['id'], $attribute['name'], array('fields' => 'names') );

            foreach ($post_terms as $option ) {
                $attribute['term'][sanitize_title( $option )] = $option;
            }

        } else {
            $options =  array_map( 'trim', explode( WC_DELIMITER, $attribute['value'] ) );
            foreach ($options as $option ) {
                $attribute['term'][sanitize_title( $option )] = $option;
            }
        }
        unset(  $attribute['value'] );
        $attribute['name'] = wc_attribute_label( $attribute['name'] );
        $filter_attributes[$item] = $attribute;
    }

    foreach( $attributes as $item => $attribute ) {
        if ( $attribute['is_taxonomy'] ) {
            $tax = get_taxonomy( $attribute['name'] );
            $attribute_name = $tax->labels->name;
            $options = wp_get_post_terms( $parent_data['id'], $attribute['name'], array('fields' => 'names') );
            $attribute['term_value'] = implode(' | ', $options );
            $attribute['label'] = $tax->label;
            $attribute['data_attr_name'] = wc_sanitize_taxonomy_name( str_replace( 'pa_', '', $attribute['name'] ) );;
            $modified_attribute[] = $attribute;
        } else {
            $attribute_name = $attribute['name'];
            $options = array_map( 'trim', explode('|', $attribute['value'] ) );
            $modified_attribute[] = $attribute;
        }
    }
}

// Get variations
$args = array(
    'post_type'     => 'product_variation',
    'post_status'   => array( 'private', 'publish' ),
    'numberposts'   => -1,
    'orderby'       => 'menu_order',
    'order'         => 'asc',
    'post_parent'   => $post_id
);

$loop = 0;
$variations = get_posts( $args );

if ( $variations )  {

?>

<table class="dokan-table dokan-variations-table">
    <thead>
        <tr>
            <th></th>
            <th><?php _e( 'Image', 'dokan' ) ?></th>
            <th><?php _e( 'Variant', 'dokan' ) ?></th>
            <th><?php _e( 'Price', 'dokan' ) ?></th>
            <th><?php _e( 'SKU', 'dokan' ) ?></th>
            <th><?php _e( 'Action', 'dokan' ) ?></th>
        </tr>
    </thead>
    <tbody>
    <?php

        foreach ( $variations as $variation ) {

            $variation_id           = absint( $variation->ID );
            $variation_post_status  = esc_attr( $variation->post_status );
            $variation_data         = get_post_meta( $variation_id );

            $shipping_classes = get_the_terms( $variation_id, 'product_shipping_class' );
            $shipping_class = ( $shipping_classes && ! is_wp_error( $shipping_classes ) ) ? current( $shipping_classes )->term_id : '';

            $variation_fields = array(
                '_sku',
                '_stock',
                '_manage_stock',
                '_stock_status',
                '_regular_price',
                '_sale_price',
                '_weight',
                '_length',
                '_width',
                '_height',
                '_download_limit',
                '_download_expiry',
                '_downloadable_files',
                '_downloadable',
                '_virtual',
                '_thumbnail_id',
                '_sale_price_dates_from',
                '_sale_price_dates_to'
            );

            foreach ( $variation_fields as $field ) {
                $$field = isset( $variation_data[ $field ][0] ) ? maybe_unserialize( $variation_data[ $field ][0] ) : '';
            }

            $_backorders = isset( $variation_data['_backorders'][0] ) ? $variation_data['_backorders'][0] : null;
            $_tax_class = isset( $variation_data['_tax_class'][0] ) ? $variation_data['_tax_class'][0] : null;
            $image_id   = absint( $_thumbnail_id );
            $image      = $image_id ? wp_get_attachment_thumb_url( $image_id ) : '';

            unset( $variation_data['_sale_price_dates_from'] );
            unset( $variation_data['_sale_price_dates_to'] );
            unset( $variation_data['_downloadable_files'] );

            $variation_data['_sale_price_dates_from'][0]  = ( !empty( $_sale_price_dates_from ) ) ? date_i18n( 'Y-m-d', $_sale_price_dates_from ) : '';
            $variation_data['_sale_price_dates_to'][0]    = ( !empty( $_sale_price_dates_from ) ) ? date_i18n( 'Y-m-d', $_sale_price_dates_to ) : '';
            $variation_data['thumbnail_url'][0]           = $image;
            $variation_data['variation_id'][0]            = $variation_id;
            $variation_data['variation_backorders'][0]    = $_backorders;
            $variation_data['variation_taxclass'][0]      = $_tax_class;
            $variation_data['variation_shippingclass'][0] = $shipping_class;
            $variation_data['variation_attributes'][0]    = $filter_attributes;
            $variation_data['tax_class_options'][0]       = $tax_class_options;
            $variation_data['variation_post_status'][0]   = $variation_post_status;
            $variation_data['_downloadable_files'][0]     = $_downloadable_files;
            $variation_data['placeholder_image'][0]       = esc_url( wc_placeholder_img_src() );
            $variation_data['post_id'][0]                 = $post_id;

            // Locale formatting
            $_regular_price = wc_format_localized_price( $_regular_price );
            $_sale_price    = wc_format_localized_price( $_sale_price );
            $_weight        = wc_format_localized_decimal( $_weight );
            $_length        = wc_format_localized_decimal( $_length );
            $_width         = wc_format_localized_decimal( $_width );
            $_height        = wc_format_localized_decimal( $_height );

            // Stock BW compat
            if ( '' !== $_stock ) {
                $_manage_stock = 'yes';
            }
            ?>
            <tr data-variation_data='<?php echo json_encode( $variation_data ); ?>'>
            <?php
                include 'variation-item.php';
                $loop++;
            ?>
            </tr>
            <?php
        }

    }
?>
    </tbody>
</table>

<div class="dokan-variation-action-wrapper">
    <a href="#doakn-single-attribute-wrapper" data-effect="mfp-zoom-out" data-product_attributes='<?php echo json_encode( $modified_attribute ); ?>' data-predefined_attr='<?php echo json_encode( $attribute_taxonomies ); ?>' class="dokan-btn dokan_add_new_attribute dokan-btn-theme dokan-left"><?php _e( 'Add Options', 'dokan' ); ?></a>
    <a href="#variation-edit-popup" data-post_id='<?php echo $post_id ?>' class="dokan-btn dokan_add_new_variation dokan-btn-theme dokan-right"><?php _e( 'Add New Variation', 'dokan' ); ?></a>
    <span class="dokan-loading dokan-hide"></span>
    <div class="dokan-clearfix"></div>
</div>



