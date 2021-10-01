<?php
// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = array(
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

return apply_filters( 'vite_payments_for_woocommerce_checkout_settings', $settings );

// phpcs:enable
