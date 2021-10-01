<?php
/**
 * Cart handler.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Vite_Gateway_Cart_Handler handles button display in the frontend.
 */
class WC_Vite_Gateway_Cart_Handler {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! wc_vite_pay_gateway()->settings->is_enabled() ) {
			return;
		}

		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'display_vpfw_button' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}


	/**
	 * Display vite payment for woocommerce button on the cart page.
	 */
	public function display_vpfw_button() {

		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		if ( ! isset( $gateways['WC_Vite_Gateway'] )) {
			return;
		}

    // This is broken - meant to change button style when gateway is selected in order review (checkout) FIXME
		$vpfw_checkout_img_url = apply_filters( 'vite-payments_checkout_button_img_url', VPFW_URL . 'assets/img/vpfw-checkout-logo-large.png'  );
		?>
		<div id="woo_vpfw_button_checkout">
      <a id="vpfw_checkout_button" class="vpfw-checkout-buttons__button">
      	<img src="<?php echo esc_url( $vpfw_checkout_img_url ); ?>" alt="<?php esc_attr_e( 'Check out with Vite', 'vite-payments-for-woocommerce' ); ?>" style="width: auto; height: auto;" onclick="processVPFWCheckout('VitePayAppID')">
      </a>
		</div>
    <?php echo do_shortcode("[vitepay-react-app]"); ?>
		<?php
	}


	/**
	 * Frontend scripts
	 */
	public function enqueue_scripts() {

		$settings = wc_vite_pay_gateway()->settings;

		wp_enqueue_style( 'vpfw-frontend', VPFW_URL . 'assets/css/vpfw-frontend.css', array(), VPFW_VERSION );

		$is_cart     = is_cart() && ! WC()->cart->is_empty(); // && 'yes' === $settings->cart_checkout_enabled;
		$is_product  = ( is_product() || wc_post_content_has_shortcode( 'product_page' ) ); // && 'yes' === $settings->checkout_on_single_product_enabled;
		$is_checkout = is_checkout();
		$page        = $is_cart ? 'cart' : ( $is_product ? 'product' : ( $is_checkout ? 'checkout' : null ) );

    // Enqueue our scripts here based on where we are (cart, checkout, single-product)
    if ( $is_cart ) {
			wp_enqueue_script( 'vpfw-frontend-in-context-checkout.js', VPFW_URL . 'assets/js/vpfw-frontend-in-context-checkout.js', array( 'jquery' ), VPFW_VERSION, true );
		}
	}
}
