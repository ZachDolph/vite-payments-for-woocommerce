<?php
/*
	Plugin Name: Vite Payments for Woocommerce
	Author: Zach Dolph
	Donate link: https://thisplaceishome.com/donate-to-the-dev/
	Description: Vite Payments for Woocommerce provides payment functionality utilizing the Vite protocol.
	Tags: blockchain, vite, woocommerce, payment-gateway
	Requires at least: 4.5
	Tested up to: 5.7.2
	Stable tag: 1.0.0
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
	Domain Path: /languages
*/

/*
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version
	2 of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	with this program. If not, visit: https://www.gnu.org/licenses/

	Copyright 2021 Monzilla Media. All rights reserved.
*/

if (!defined('ABSPATH')) die();

if (!defined('VPFW_VERSION')) define('VPFW_VERSION', '1.0.0');
if (!defined('VPFW_FILE'))    define('VPFW_FILE', plugin_basename(__FILE__));
if (!defined('VPFW_DIR'))     define('VPFW_DIR', plugin_dir_path(__FILE__));
if (!defined('VPFW_URL'))     define('VPFW_URL', plugin_dir_url(__FILE__));


/**
 * Return an instance of the WC_Vite_Gateway_Plugin.
 *
 * @return WC_Vite_Gateway_Plugin
 */
function wc_vite_pay_gateway()
{
	static $plugin;

	if (!isset($plugin)) {
		require_once 'includes/class-vpfw-plugin.php';
		$plugin = new WC_Vite_Gateway_Plugin(__FILE__, VPFW_VERSION);
	}

	return $plugin;
}
wc_vite_pay_gateway()->_run();


/**
 * This action hook registers our PHP class as a WooCommerce payment gateway
 * @return
 */
add_filter('woocommerce_payment_gateways', 'vpfw_add_gateway_class');
function vpfw_add_gateway_class($gateways)
{
	$gateways[] = 'WC_Vite_Gateway';
	return $gateways;
}


/**
 * The class itself, note that it is inside plugins_loaded action hook
 * @return
 */
add_action('plugins_loaded', 'vpfw_init_gateway_class');
function vpfw_init_gateway_class()
{
	class WC_Vite_Gateway extends WC_Payment_Gateway
	{
		/**
		 * WC_Vite_Gateway class constructor
		 */
		public function __construct()
		{
			// Payment gateway plugin ID
			$this->id = 'vite_pay_for_woo';
			// Let woo know we have custom fields
			$this->has_fields = false;
			// Title shown in admin payment settings
			$this->method_title = 'Vite Payments for Woocommerce';
			// Displayed on the options page
			$this->method_description = 'Accept Vite payments on your Woocommerce store.';
			// Gateway currently supports simple payments but can be used for subscriptions, refunds, saved payment methods, etc.
			$this->supports = array('products');

			// Load and initialize admin config
			$this->init_form_fields();
			$this->init_settings();

			$this->title = $this->get_option('title');
			$this->enabled = $this->get_option('enabled');
			$this->testmode = 'yes' === $this->get_option('testmode');
			$this->addressDefault = $this->testmode ? $this->get_option('test_wallet_address') : $this->get_option('live_wallet_address');
			$this->nodeURL = $this->testmode ? $this->get_option('test_node_url') : $this->get_option('node_url');
			$this->httpURL = $this->testmode ? $this->get_option('test_http_url') : $this->get_option('http_url');
			$this->tokenDefault = $this->get_option('token_default');
			$this->defaultMemo = $this->get_option('default_memo');
			$this->paymentTimeout = $this->get_option('payment_timeout');

			// This action hook saves the settings
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

			// Register webhooks here for payment status callback
			add_action('woocommerce_api_vite_payment_success', array($this, 'vite_payment_success_hook'));
			add_action('woocommerce_api_vite_payment_failure', array($this, 'vite_payment_failure_hook'));

			// Set gateway ID Woocommerce filters
			add_filter('woocommerce_payment_gateways_setting_columns', array($this, 'vite_add_payment_gateway_column'));
			add_action('woocommerce_payment_gateways_setting_column_id', array($this, 'vite_populate_gateway_column'));

			//wp_enqueue_style('vpfw-checkout-style', VPFW_URL . 'assets/css/vpfw-frontend.css');
			wp_enqueue_style('vpfw-app-container-style', VPFW_URL . 'includes/vitepay-react-app/build/static/css/main.6e23ac53.css');
		}


		/**
		 * Plugin options (admin settings)
		 */
		public function init_form_fields()
		{
			$this->form_fields = include VPFW_DIR . 'includes/settings/settings-vpfw.php';
		}


		/**
		 * Get and set gateway icons using woo filter.
		 * @return string
		 */
		public function get_icon()
		{
			if ($this->get_option('show_icons') === 'no') {
				return '';
			}

			$image_path = VPFW_DIR . 'assets/img';
			$icon_html  = '';
			$methods    = get_option('vite_payment_methods', array('vite', 'ethereum'));

			// Load icon for each available payment method.
			foreach ($methods as $m) {
				$path = realpath($image_path . '/' . $m . '.png');
				if ($path && dirname($path) === $image_path && is_file($path)) {
					$url        = WC_HTTPS::force_https_url(plugins_url('/assets/img/' . $m . '.png', __FILE__));
					$icon_html .= '<img width="26" src="' . esc_attr($url) . '" alt="' . esc_attr__($m, 'vite') . '" />';
				}
			}

			return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
		}


		/**
		 * Set gateway description to display tx QR code
		 * @return string
		 */
		public function get_description()
		{
			// Get the order total in token default
			$order_total = $this->get_order_total();

			// Use shortcode_unautop to prevent <p> from being added by wordpress
			$description_html = '<div>';
			$description_html .= shortcode_unautop(do_shortcode('[vitepay_react_app]'));
			$description_html .= '</div>';

			// 
			$description_html .= '<script type="application/javascript">
			window.txData = new Array()
			window.txData["addressDefault"] = "' . $this->addressDefault . '"
			window.txData["siteURL"] = "' . VPFW_URL . '"
			window.txData["nodeURL"] = "' . $this->nodeURL . '"
			window.txData["httpURL"] = "' . $this->httpURL . '"
			window.txData["txAmount"] = "' . $order_total . '"
			window.txData["tokenDefault"] = "' . $this->tokenDefault . '"
			window.txData["defaultMemo"] = "' . $this->defaultMemo . '"
			window.txData["paymentTimeout"] = ' . $this->paymentTimeout . '
			window.txData["allowMultipleTokens"] = true
			window.txData["displayMemo"] = true
			</script>';

			// Apply the tx QR code to the gateway description seen in checkout by customer
			return apply_filters('woocommerce_gateway_description', $description_html, $this->id);

			// TODO
			// Need to get exchange rate other than Vite (token default)
			// Do we need to add a spread to handle volatility and lock in price?
		}


		/**
		 * Get order total in token default
		 * @return float
		 */
		function get_order_total()
		{
			$order_total = 0;
			$order_id = absint(get_query_var('order-pay'));

			// Gets order total from "pay for order" page.
			if (0 < $order_id) {
				// Grab the order total (usd)
				$order = wc_get_order($_GET['id']);
				if ($order) {
					$order_total = (float) $order->get_total();
				}
			} elseif (0 < WC()->cart->total) {
				$order_total = (float) WC()->cart->total;
			}

			$coinGeckoURL = "https://api.coingecko.com/api/v3/simple/price?ids=vite&vs_currencies=usd";

			$curlObj = curl_init($coinGeckoURL);
			curl_setopt($curlObj, CURLOPT_URL, $coinGeckoURL);
			curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, true);

			$curlResp = curl_exec($curlObj);
			curl_close($curlObj);

			$jsonResp = json_decode($curlResp, true);
			$currentPriceInUSD = $jsonResp['vite']['usd'];

			if ($currentPriceInUSD > 0)
				$order_total /= (float) $currentPriceInUSD;

			return $order_total;
		}


		/**
		 * Add our gateway id column
		 * @return
		 */
		function vite_add_payment_gateway_column($default_columns)
		{
			$default_columns = array_slice($default_columns, 0, 2) + array('id' => 'ID') + array_slice($default_columns, 2, 3);
			return $default_columns;
		}


		/**
		 * Print our gateway id column
		 */
		function vite_populate_gateway_column($gateway)
		{
			echo '<td style="width:10%">' . $gateway->id . '</td>';
		}


		/**
		 * Payment success callback
		 * @return array
		 */
		public function vite_payment_success_hook()
		{
			global $woocommerce;
			$order_id = absint(get_query_var('order-pay'));

			// Gets order total from "pay for order" page.
			if (0 < $order_id) {
				// Grab the order total (usd)
				$order = wc_get_order($_GET['id']);
				if ($order) {
					// Received the payment
					$order->payment_complete();
					$order->reduce_order_stock();
					// Note to customer
					$order->add_order_note('Vite Payment Successful! Thank you!', false);
					// Empty the cart
					$woocommerce->cart->empty_cart();

					// Redirect to the thank you page
					return array(
						'result' => 'success',
						'redirect' => $this->get_return_url($order)
					);
				}
			}

			// Redirect to the thank you page
			return array('result' => 'success');
		}


		/**
		 * Payment failure callback
		 */
		public function vite_payment_failure_hook()
		{
			wc_add_notice('Vite Payment Failure, Try Again or Contact Store Owner.', 'error');
		}
	}
}


// Add our shortcode for use in wordpress/woocommerce site
add_shortcode('vitepay_react_app', 'vitepay_react_app_function');

/**
 * Payment success callback
 * @return string
 */
function vitepay_react_app_function()
{
	$description_html = '<div style="margin-left:auto; position: relative; width: 400px; height: 400px;">';
	$description_html .= '<div id="vitepay-react-app">Loading...</div>';
	$description_html .= '</div>';
	$description_html .= '<script src="' . VPFW_URL . 'includes/vitepay-react-app/build/static/js/main.c0c31606.js"></script>';

	return $description_html;
}
