<?php
// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned

if (!defined('ABSPATH')) {
  exit;
}

$settings = array(
  'enabled' => array(
    'title' => __('Enable/Disable', 'woocommerce'),
    'label' => 'Enable Vite Payments for Woocommerce',
    'type'  => 'checkbox',
    'description' => '',
    'default'     => 'no'
  ),
  'title'        => array(
    'title'       => __('Title', 'woocommerce'),
    'type'        => 'text',
    'description' => __('This controls the title which the user sees during checkout.', 'vite-payments-for-woocommerce'),
    'default'     => __('Vite and other tokens on the Vite protocol', 'vite'),
    'desc_tip'    => true,
  ),
  'description'  => array(
    'title'       => __('Description', 'woocommerce'),
    'type'        => 'textarea',
    'description' => 'Description displayed to user during checkout.',
    'default'     => 'Pay with Vite and other tokens on the Vite protocol.'
  ),
  'testmode'     => array(
    'title'       => __('Test Mode', 'vite-payments-for-woocommerce'),
    'label'       => 'Enable Test Mode',
    'type'        => 'checkbox',
    'description' => 'Enable test mode on the Vite payment gateway using test address.',
    'default'     => 'yes',
    'desc_tip'    => true
  ),
  'test_wallet_address' => array(
    'title'              => __('Test Wallet Address', 'vite-payments-for-woocommerce'),
    'type'               => 'text',
    'default'            => 'vite_10a86218cf37c795ebbdf8a7da643d92e22d860d2b747e049e'
  ),
  'live_wallet_address' => array(
    'title'              => __('Live Wallet Address', 'vite-payments-for-woocommerce'),
    'type'               => 'text',
    'default'            => 'vite_10a86218cf37c795ebbdf8a7da643d92e22d860d2b747e049e'
  ),
  'amount_default' => array(
    'title' => __('Amount Default', 'vite-payments-for-woocommerce'),
    'type'  => 'text',
    'default'     => '1'
  ),
  'token_default' => array(
    'title' => __('Token Default', 'vite-payments-for-woocommerce'),
    'type'  => 'text',
    'default'     => 'tti_5649544520544f4b454e6e40'
  ),
  'default_memo' => array(
    'title' => __('Default Memo', 'vite-payments-for-woocommerce'),
    'type'  => 'text',
    'default'     => '123abcd'
  ),
  'node_url' => array(
    'title'   => __('Live Node URL', 'vite-payments-for-woocommerce'),
    'type'    => 'text',
    'default' => 'wss://buidl.vite.net/gvite/ws'
  ),
  'http_url' => array(
    'title'   => __('Live HTTP URL', 'vite-payments-for-woocommerce'),
    'type'    => 'text',
    'default' => 'http://buidl.vite.net/gvite/http'
  ),
  'test_node_url' => array(
    'title'        => __('Test Node URL', 'vite-payments-for-woocommerce'),
    'type'         => 'text',
    'default'      => 'wss://buidl.vite.net/gvite/ws'
  ),
  'test_http_url' => array(
    'title'        => __('Test HTTP URL', 'vite-payments-for-woocommerce'),
    'type'         => 'text',
    'default'      => 'http://buidl.vite.net/gvite/http'
  ),
  'payment_timeout' => array(
    'title'          => __('Payment Timeout', 'vite-payments-for-woocommerce'),
    'type'           => 'text',
    'default'        => '900'
  ),
  'show_icons'    => array(
    'title'        => __('Show icons', 'vite-payments-for-woocommerce'),
    'type'         => 'checkbox',
    'label'        => __('Display token icons on checkout page.', 'vite-payments-for-woocommerce'),
    'default'      => 'yes'
  )
);

return apply_filters('vite_payments_for_woocommerce_checkout_settings', $settings);

// phpcs:enable
