<?php
$action = isset( $_GET['action'] ) ? $_GET['action'] : 'listing';

if ( $action == 'edit' ) {
    include dirname( __FILE__ ) . '/product-edit.php';
} else {
    include dirname( __FILE__ ) . '/products-listing.php';
}