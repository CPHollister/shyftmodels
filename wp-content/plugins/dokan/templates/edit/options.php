<div class="dokan-form-horizontal">
    <div class="dokan-form-group">
        <label class="dokan-w4 dokan-control-label" for="_purchase_note"><?php _e( 'Purchase Note', 'dokan' ); ?></label>
        <div class="dokan-w6 dokan-text-left">
            <?php dokan_post_input_box( $post->ID, '_purchase_note', array(), 'textarea' ); ?>
        </div>
    </div>

    <div class="dokan-form-group">
        <label class="dokan-w4 dokan-control-label" for="_enable_reviews"><?php _e( 'Reviews', 'dokan' ); ?></label>
        <div class="dokan-w4 dokan-text-left">
            <?php $_enable_reviews = ( $post->comment_status == 'open' ) ? 'yes' : 'no'; ?>
            <?php dokan_post_input_box( $post->ID, '_enable_reviews', array('value' => $_enable_reviews, 'label' => __( 'Enable Reviews', 'dokan' ) ), 'checkbox' ); ?>
        </div>
    </div>

    <div class="dokan-form-group">
        <label class="dokan-w4 dokan-control-label" for="_purchase_note"><?php _e( 'Visibility', 'dokan' ); ?></label>
        <div class="dokan-w6 dokan-text-left">
            <?php dokan_post_input_box( $post->ID, '_visibility', array( 'options' => array(
                'visible' => __( 'Catalog or Search', 'dokan' ),
                'catalog' => __( 'Catalog', 'dokan' ),
                'search' => __( 'Search', 'dokan' ),
                'hidden' => __( 'Hidden', 'dokan ')
            ) ), 'select' ); ?>
        </div>
    </div>
    
    <div class="dokan-form-group">
        <label class="dokan-w4 dokan-control-label" for="_enable_reviews"><?php _e( 'Sold Individually', 'dokan' ); ?></label>
        <div class="dokan-w7 dokan-text-left">
            <?php dokan_post_input_box( $post->ID, '_sold_individually', array('label' => __( 'Enable this to only allow one of this item to be bought in a single order', 'dokan' ) ), 'checkbox' ); ?>
        </div>
    </div>

</div> <!-- .form-horizontal -->