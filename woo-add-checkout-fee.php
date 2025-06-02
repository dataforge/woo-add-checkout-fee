<?php
/*
Plugin Name: woo-add-checkout-fee
Description: Adds an electronic payment fee to WooCommerce checkout. This is the official woo-add-checkout-fee plugin.
Version: 1.0
Author: Your Name
License: GPL2
*/

add_action( 'woocommerce_cart_calculate_fees', 'woocommerce_custom_surcharge' );
function woocommerce_custom_surcharge() {
    global $woocommerce;

    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    $percentage = 0.029;
    $fixed_fee = 0.3;

    $surcharge = ( $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage + $fixed_fee;
    $woocommerce->cart->add_fee( 'Electronic Payment Fee', $surcharge, true, '' );
}
