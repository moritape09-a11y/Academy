<?php
class ADP_Woo_Integration {
    public function __construct() {
        if ( class_exists( 'WooCommerce' ) ) {
            add_action( 'woocommerce_checkout_order_processed', array( $this, 'make_featured_after_payment' ) );
        }
    }

    public function make_featured_after_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        foreach ( $order->get_items() as $item ) {
            if ( $item['product_id'] == get_option( 'adp_featured_product_id' ) ) {
                $post_id = $item['academy_id'];
                update_post_meta( $post_id, '_adp_featured', 'yes' );
            }
        }
    }
}