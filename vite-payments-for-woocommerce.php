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

if (!defined('VPFW_SUCCESS'))     define('VPFW_SUCCESS', 1);
if (!defined('VPFW_FAILURE'))     define('VPFW_FAILURE', -1);

/**
 * Return an instance of the WC_Vite_Gateway_Plugin.
 *
 * @return WC_Vite_Gateway_Plugin
 */
function wc_vite_pay_gateway()
{
	static $plugin;

	if (!isset($plugin))
	{
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
 * WC_Vite_Gateway class
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
			$this->id = 'vite-payments-for-woocommerce';
			// Let woo know we have custom fields
			$this->has_fields = false;
			// Title shown in admin payment settings
			$this->method_title = 'Vite Payments for Woocommerce';
			// Displayed on the options page
			$this->method_description = 'Accept Vite payments on your Woocommerce store.';
			// Gateway currently supports simple payments but can be used for subscriptions, refunds, saved payment methods, etc.
			$this->supports = array('products');
            // Used for callback to verify payments
            $this->paymentStatus = 0;
            $this->timeRemaining = 0;

			// Load and initialize admin config
			$this->init_form_fields();

			// This action hook saves the settings
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

			// Set gateway ID Woocommerce filters
			add_filter('woocommerce_payment_gateways_setting_columns', array($this, 'vite_add_payment_gateway_column'));
			add_action('woocommerce_payment_gateways_setting_column_id', array($this, 'vite_populate_gateway_column'));

            // Load our scripts
            add_action( 'wp_enqueue_scripts', array($this, 'vpfw_enqueue_scripts'));
		}


		/**
		 * Plugin options (admin settings)
		 */
		public function init_form_fields()
		{
			$this->form_fields = include VPFW_DIR . 'includes/settings/settings-vpfw.php';
            $this->init_settings();

			$this->title = $this->get_option('title');
			$this->enabled = $this->get_option('enabled');
			$this->testmode = 'yes' === $this->get_option('testmode');
			$this->address_default = $this->testmode ? $this->get_option('test_wallet_address') : $this->get_option('live_wallet_address');
			$this->node_url = $this->testmode ? $this->get_option('test_node_url') : $this->get_option('node_url');
			$this->http_url = $this->testmode ? $this->get_option('test_http_url') : $this->get_option('http_url');
			$this->token_default = $this->get_option('token_default');
			$this->default_memo = $this->get_option('default_memo');
			$this->paymentTimeout = $this->timeRemaining = $this->get_option('payment_timeout');
			$this->qrCodeSize = $this->get_option('qr_code_size');
		}

        public function vpfw_enqueue_scripts()
        {
            // Only load our scripts on cart or checkout pages
            if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order']))
            {
            	return;
            } 
         
            // Only load our scripts is VPFW is enabled
            if ($this->enabled === 'no')
            {
            	return;
            }

            //
            $vpfw_css_to_load = VPFW_URL . 'assets/css/vite-pay-for-wc.css';
            $vpfw_js_to_load = VPFW_URL . 'assets/js/vite-pay-for-wc.js';

            //
            wp_register_script( 'vpfw_js_script', $vpfw_js_to_load, array( 'jquery') );
            //
            wp_localize_script( 'vpfw_js_script', 'vpfw_ajax_data', array( 'ajaxURL' => admin_url('admin-ajax.php'), 
                                                                            'nonce' => $this->nonce,
                                                                            'txAmountUSD' => $this->get_order_total(),
                                                                            'tokenDefault' => $this->token_default,
                                                                            'addressDefault' => $this->address_default,
                                                                            'nodeURL' => $this->node_url,
                                                                            'httpURL' => $this->http_url,
                                                                            'allowMultipleTokens' => true,
                                                                            'shouldDisplayMemo' => true,
                                                                            'defaultMemo' => $this->default_memo,
                                                                            'paymentTimeout' => $this->paymentTimeout,
                                                                            'qrCodeSize' => $this->qrCodeSize  ));   
            //
            wp_enqueue_script('vpfw_js_script');
            wp_enqueue_style('vpfw_css_style', $vpfw_css_to_load);
        }


		/**
		 * Get and set gateway icons using woo filter.
		 * @return string
		 */
		public function get_icon()
		{
			if ($this->get_option('show_icons') === 'no')
			{
				return '';
			}

			$image_path = VPFW_DIR . 'assets/img';
			$icon_html  = '';
			$methods    = get_option('vite_payment_methods', array('vite', 'ethereum'));

			// Load icon for each available payment method.
			foreach ($methods as $m)
			{
				$path = realpath($image_path . '/' . $m . '.png');
				if ($path && dirname($path) === $image_path && is_file($path))
				{
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
            // We only want to load scripts on the checkout page
			if (!is_checkout())
			{
				return;
			}


            $description_html = '<div class="vitepayDiv">';
            $description_html .= '<form class="vitepayForm">';
            $description_html .= '<p>The transaction will expire in "'. $this->setInterval($this->timeRemaining) .'"</p>';
            //<QRCode size="'.$this->qrCodeSize.'" class"vitepayQRCode" value="'..'" />
            //<TransactionForm />
            //<div className={styles.vitepayForm} style = {{display: status === 0 ? 'block' : 'none'}}>
            //    {allowMultipleTokens && (<label>
            //        {options.length > 0 ? (
            //            <StyledSelect
            //                class="vitepayDropdown"}"
            //                options={options}
            //                values={[options.find(opt => opt.tokenId === tokenId)]}
            //                ref={inputToken}
            //                onChange={(e) => { setTokenId(e[0]?.tokenId); }}
            //            />
            //        ) : <p>Loading ....</p>}    
            //    </label>)}
            //</div>
            $description_html .= '</form>';
            $description_html .= '</div>';

            //switch ($this->paymentStatus)
            //{
            //    case VPFW_SUCCESS:
            //        // {(state === 1) && <p>Transaction Complete&#33;</p>}
            //        break;
            //    case VPFW_FAILURE:
            //        // {state === -1 && <p>Transaction has failed</p>}
            //        break;
            //    default:
            //        break;
            //}


			// Apply the tx QR code to the gateway description seen in checkout by customer
			return apply_filters('woocommerce_gateway_description', $description_html, $this->id);

			// TODO
			// Need to get exchange rate other than Vite (token default)
			// Do we need to add a spread to handle volatility and lock in price?
		}


        /**
		 * Set countdown timer for tx expiration
		 * @return Ev::iteration
		 */
        public function setInterval($seconds)
        {
            // Create and launch timer repeating every second
            return $w2 = new EvTimer(0, 1, function ($w)
            {
                // '.floor($this->timeRemaining / 60).'m '.($this->timeRemaining - floor($this->timeRemaining / 60) * 60)).'s';
                return Ev::iteration();
            
                // Stop the watcher after seconds passed in
                Ev::iteration() == $seconds and $w->stop();
                // Stop the watcher if further calls cause more than 2x iterations
                Ev::iteration() >= (2 * $seconds) and $w->stop();
            });
        }

        /**
		 * Generate QR code for new transaction 
		 * @return img
		 */
        public function getQRCode()
        {
            // vite:${address}?tti=${tokenId}&amount=${amount}&data=${encode(memo).replaceAll("=", "")}
        }



		/**
		 * Get order total in USD
		 * @return float
		 */
		function get_order_total()
		{
			$order_total = 0;
			$order_id = absint(get_query_var('order-pay'));

			// Gets order total from "pay for order" page.
			if (0 < $order_id)
			{
				// Grab the order total (usd)
				$order = wc_get_order($_GET['id']);
				if ($order)
				{
					$order_total = (float) $order->get_total();
				}
			}
			else if (0 < WC()->cart->total)
			{
				$order_total = (float) WC()->cart->total;
			}

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
		 * Post results after payment is complete
		 *
		 * @return string
		 */
		function post_vpfw_result()
		{
            if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'post_vpfw_result_nonce'))
            {
               return;
            }

            $posted_result = $_REQUEST['result'];

            if ($posted_result) // Success
            {
				global $woocommerce;
				$order_id = absint(get_query_var('order-pay'));
				
				// Gets order total from "pay for order" page.
				if (0 < $order_id)
				{
					// Grab the order total (usd)
					$order = wc_get_order($_GET['id']);
					if ($order)
					{
						// Received the payment
						$order->payment_complete();
						$order->reduce_order_stock();
						// Note to customer
						wc_add_notice('Transaction Confirmed', 'success');
						// Empty the cart
						$woocommerce->cart->empty_cart();
					}
				}

				// We don't know for sure whether this is a URL for this site,
				// so we use wp_safe_redirect() to avoid an open redirect.
				wp_safe_redirect( $this->get_return_url($order) );
            }
            else // Failure
            {
                // Handle payment failure
                wc_add_notice('Vite Payment Failure, Try Again or Contact Store Owner.', 'error');
            }
            
            if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            {
                header("Location: ".$_SERVER["HTTP_REFERER"]);
                die();
                return;
            }
        
            die();
            return $result['type'] = $posted_result ? 'success' : 'error';
		}


	} // End of class WC_Vite_Gateway

} // End of function vpfw_init_gateway_class()
