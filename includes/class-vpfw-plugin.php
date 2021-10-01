<?php
/**
 * PayPal Checkout Plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Vite_Gateway_Plugin {

	/**
	 * Instance of WC_Vite_Gateway_Settings.
	 *
	 * @var WC_Vite_Gateway_Settings
	 */
	public $settings;

	/**
	 * Constructor.
	 *
	 * @param string $file    Filepath of main plugin file
	 * @param string $version Plugin version
	 */
	public function __construct( $file, $version ) {
		$this->file    = $file;
		$this->version = $version;
	}

  /**
	 * Run the plugin.
	 */
	protected function _run() {
    register_activation_hook( $this->file, array( $this, 'activate' ) );
		require_once $this->includes_path . 'functions.php';
		$this->_load_handlers();
	}

	/**
	 * Handle updates.
	 *
	 * @param string $new_version The plugin's new version.
	 */
	private function run_updater( $new_version ) {
		// Map old settings to settings API
		if ( get_option( 'vpfw_enabled' ) ) {
			$settings_array                               = (array) get_option( 'vite_payments_for_woocommerce_settings', array() );
			$settings_array['enabled']                    = get_option( 'vpfw_enabled' ) ? 'yes' : 'no';
			update_option( 'vite_payments_for_woocommerce_settings', $settings_array );
			delete_option( 'vpfw_enabled' );
		}

	}


	/**
	 * Callback for activation hook.
	 */
	public function activate() {
		if ( ! isset( $this->settings ) ) {
			require_once VPFW_DIR . 'includes/settings/class-vpfw-settings.php';
			$settings = new WC_Vite_Gateway_Settings();
		} else {
			$settings = $this->settings;
		}
	}

	/**
	 * Load handlers.
	 */
	protected function _load_handlers() {

		// Load handlers.
		require_once VPFW_DIR . 'includes/class-vpfw-settings.php';
		require_once VPFW_DIR . 'includes/class-vpfw-cart-handler.php';

		$this->settings       = new WC_Vite_Gateway_Settings();
		$this->cart           = new WC_Vite_Gateway_Cart_Handler();
	}


}
