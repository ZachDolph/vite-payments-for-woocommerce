<?php

/**
 * Vite Payments for Woocommerce Plugin Class
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class WC_Vite_Gateway_Plugin
{

	/**
	 * Instance of WC_Vite_Gateway_Settings.
	 *
	 * @var WC_Vite_Gateway_Settings
	 */
	public $settings;
	public $onPaymentSuccess = 'vite-payment-success';
	public $onPaymentFailure = 'vite-payment-failure';

	/**
	 * Constructor.
	 *
	 * @param string $file    Filepath of main plugin file
	 * @param string $version Plugin version
	 */
	public function __construct($file, $version)
	{
		$this->file    = $file;
		$this->version = $version;
	}

	/**
	 * Run the plugin.
	 */
	public function _run()
	{
		register_activation_hook($this->file, array($this, 'activate'));
		$this->_load_handlers();
	}

	/**
	 * Handle updates.
	 *
	 * @param string $new_version The plugin's new version.
	 */
	private function run_updater($new_version)
	{
		// Map old settings to settings API
		if (get_option('enabled')) {
			$settings_array                               = (array) get_option('vite_payments_for_woocommerce_settings', array());
			$settings_array['enabled']                    = get_option('enabled') ? 'yes' : 'no';
			update_option('vite_payments_for_woocommerce_settings', $settings_array);
			delete_option('enabled');
		}
	}


	/**
	 * Callback for activation hook.
	 */
	public function activate()
	{
		if (!isset($this->settings)) {
			require_once VPFW_DIR . 'includes/settings/class-vpfw-settings.php';
			$settings = new WC_Vite_Gateway_Settings();
		} else {
			$settings = $this->settings;
		}
	}

	/**
	 * Load handlers.
	 */
	protected function _load_handlers()
	{

		// Load handlers.
		require_once VPFW_DIR . 'includes/class-vpfw-settings.php';

		$this->settings       = new WC_Vite_Gateway_Settings();
	}
}
