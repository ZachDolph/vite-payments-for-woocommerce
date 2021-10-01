<?php
/**
 * Checkout handler.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Vite_Gateway_Checkout_Handler handles button display in the frontend.
 */
class WC_Vite_Gateway_Checkout_Handler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! wc_vite_pay_gateway()->settings->is_enabled() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Frontend scripts
	 */
	public function enqueue_scripts() {

		$settings = wc_vite_pay_gateway()->settings;

		wp_enqueue_style( 'vpfw-frontend', VPFW_URL . 'assets/css/vpfw-frontend.css', array(), VPFW_VERSION );

		$is_cart     = is_cart() && ! WC()->cart->is_empty();
		//$is_product  = ( is_product() || wc_post_content_has_shortcode( 'product_page' ) );
		//$is_checkout = is_checkout();
		//$page        = $is_cart ? 'cart' : ( $is_product ? 'product' : ( $is_checkout ? 'checkout' : null ) );

    // Enqueue our scripts here based on where we are (cart, checkout, single-product)
    if ( $is_cart ) {
			wp_enqueue_script( 'vpfw-frontend-in-context-checkout.js', VPFW_URL . 'assets/js/vpfw-frontend-in-context-checkout.js', array( 'jquery' ), VPFW_VERSION, true );
		}
	}
}
