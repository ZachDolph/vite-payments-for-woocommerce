<?php
/*
	Plugin Name: Vite Payments for Woocommerce
	Author: Zach Dolph
	Donate link: https://thisplaceishome.com/donate-to-the-dev/
	Description: Vite Payments for Woocommerce provides smart contract payment functionality utilizing Vite's Solidity++.
	Tags: smartcontracts, blockchain, vite, soliditypp, woocommerce
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
if (!defined('VPFW_URL'))     define('VPFW_URL', plugin_dir_url( __FILE__ ));


/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'vpfw_add_gateway_class' );
function vpfw_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Vite_Gateway'; // your class name is here
	return $gateways;
}


/*
 * The class itself, note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'vpfw_init_gateway_class' );
function vpfw_init_gateway_class() {

	class WC_Vite_Gateway extends WC_Payment_Gateway {
    // Set our window object vars for react app
    private $amountDefault='1';
    private $tokenDefault='tti_5649544520544f4b454e6e40';
    private $defaultMemo='123abcd';
    private $addressDefault='vite_10a86218cf37c795ebbdf8a7da643d92e22d860d2b747e049e';
    private $nodeURL='wss://buidl.vite.net/gvite/ws';
    private $httpURL='https://buidl.vite.net/gvite/http';
    private $paymentTimeout='900';
    private $onPaymentSuccess='vite-payment-success';
    private $onPaymentFailure='vite-payment-failure';


 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
      // Set our window object vars for react app
			// Payment gateway plugin ID
			$this->id = 'vite-payments-for-woocommerce';
			// URL of the checkout page icon displayed near gateway name
			$this->icon = '';
			// Custom Forms
			$this->has_fields = true;
			$this->method_title = 'Vite Payments for Woocommerce Gateway';
			// Displayed on the options page
			$this->method_description = 'Accept Vite payments on your Woocommerce store.';
			// Gateway currently supports simple payments but can be used for subscriptions, refunds, saved payment methods, etc.
			$this->supports = array(
				'products'
			);
			// Method with all the options fields
			$this->init_form_fields();
			// Load the settings.
			$this->init_settings();
			$this->title = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->enabled = $this->get_option( 'enabled' );
			$this->testmode = 'yes' === $this->get_option( 'testmode' );
			$this->addressDefault = $this->testmode ? $this->get_option( 'test_contract_address' ) : $this->get_option( 'live_contract_address' );
      $this->nodeURL = $this->testmode ? $this->get_option( 'test_node_url' ) : $this->get_option( 'node_url' );
      $this->httpURL = $this->testmode ? $this->get_option( 'test_http_url' ) : $this->get_option( 'http_url' );
      $this->tokenDefault = $this->get_option( 'token_default' );
      $this->defaultMemo = $this->get_option( 'default_memo' );
      $this->paymentTimeout = $this->get_option( 'payment_timeout' );

      // This action hook saves the settings
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			
      // Could enqueue js here if custom scripts are ever needed
			//add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

			// Register webhooks here for payment status callback
			add_action( 'woocommerce_api_vite-payment-success', array( $this, 'vite_payment_success_hook' ) );
			add_action( 'woocommerce_api_vite-payment-failure', array( $this, 'vite_payment_failure_hook' ) );

      // Add our shortcode for use in wordpress/woocommerce site
      add_shortcode('vitepay-react-app', array( $this, 'vitepayapp_shortcode' ));

 		}

		/**
 		 * Plugin options (admin settings)
 		 */
 		public function init_form_fields(){

			$this->form_fields = array(
				'enabled' => array(
					'title' => 'Enable/Disable',
					'label' => 'Enable Vite Payments for Woocommerce',
					'type'  => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title' => array(
					'title' => 'Title',
					'type'  => 'text',
					'description' => 'Title displayed to user during checkout.',
					'default'     => 'Vite (Smart Contract)',
					'desc_tip'    => true
				),
				'description' => array(
					'title' => 'Description',
					'type'  => 'textarea',
					'description' => 'Description displayed to user during checkout.',
					'default'     => 'Pay with Vite using smart contracts.'
				),
				'testmode' => array(
					'title' => 'Test Mode',
					'label' => 'Enable Test Mode',
					'type'  => 'checkbox',
					'description' => 'Enable test mode on the Vite payment gateway using test address.',
					'default'     => 'yes',
					'desc_tip'    => true
				),
				'test_contract_address' => array(
					'title' => 'Test Contract Address',
					'type'  => 'text',
          'default'     => 'vite_10a86218cf37c795ebbdf8a7da643d92e22d860d2b747e049e'
				),
				'live_contract_address' => array(
					'title' => 'Live Contract Address',
					'type'  => 'text',
          'default'     => 'vite_10a86218cf37c795ebbdf8a7da643d92e22d860d2b747e049e'
				),
        'amount_default' => array(
					'title' => 'Amount Default',
					'type'  => 'text',
					'default'     => '1'
        ),
        'token_default' => array(
					'title' => 'Token Default',
					'type'  => 'text',
					'default'     => 'tti_5649544520544f4b454e6e40'
        ),
        'default_memo' => array(
					'title' => 'Default Memo',
					'type'  => 'text',
					'default'     => '123abcd'
        ),
        'node_url' => array(
					'title' => 'Live Node URL',
					'type'  => 'text',
					'default'     => 'wss://buidl.vite.net/gvite/ws'
        ),
        'http_url' => array(
					'title' => 'Live HTTP URL',
					'type'  => 'text',
					'default'     => 'http://buidl.vite.net/gvite/http'
        ),
        'test_node_url' => array(
					'title' => 'Test Node URL',
					'type'  => 'text',
					'default'     => 'wss://buidl.vite.net/gvite/ws'
        ),
        'test_http_url' => array(
					'title' => 'Test HTTP URL',
					'type'  => 'text',
					'default'     => 'http://buidl.vite.net/gvite/http'
        ),
        'payment_timeout' => array(
					'title' => 'Payment Timeout',
					'type'  => 'text',
					'default'     => '900'
        )
			);

	 	}


		/*
		 * Payment Processing
		 */
		public function process_payment( $order_id )
		{
			global $woocommerce;

			// Grab the order details
			$order = wc_get_order( $order_id );
      $this->amountDefault = $order->get_total();

      // Could add query args if we wanted
			//$args = array();
      
      // TODO
      // Need to handle exchange rate here and get price for Vite in USD
      // Do we need to add a spread to handle volatility and lock in price?
      // Should we embed the vitepay module in an iframe or send users to a new page and redirect back upon success
		}

		/*
		 * Payment success callback
		 */
		public function vite_payment_success_hook()
		{
      global $woocommerce;
      $order = wc_get_order( $_GET['id'] );
      // Received the payment
      $order->payment_complete();
      $order->reduce_order_stock();				
      // Note to customer
      $order->add_order_note( 'Vite Payment Successful! Thank you!', false );				
      // Empty cart
      $woocommerce->cart->empty_cart();				
      // Redirect to the thank you page
      return array(
      	'result' => 'success',
      	'redirect' => $this->get_return_url( $order )
      );

	 	}

		/*
		 * Payment failure callback
		 */
		public function vite_payment_failure_hook()
		{
      wc_add_notice( 'Vite Payment Failure, Try Again or Contact Store Owner.', 'error' );
      return;
	 	}


    /*
     * Shortcode for vitepay react app
     */
    public function vitepayapp_shortcode() {
    
    	return '&lt;div id="vitepay-react-app" >&lt;/div><script src="'. VPFW_URL . 'includes/vitepay-react-app/build/static/js/main.e96acc02.js"></script><script>window.amountDefault='. $this->amountDefault .' window.tokenDefault='. $this->tokenDefault .' window.defaultMemo='. $this->defaultMemo .' window.addressDefault='. $this->addressDefault .' window.nodeURL='. $this->nodeURL .' window.httpURL='. $this->httpURL .' window.paymentTimeout='. $this->paymentTimeout .' window.onPaymentSuccess='. $this->onPaymentSuccess .' window.onPaymentFailure='. $this->onPaymentFailure .'</script>';
    }
 	}
}
