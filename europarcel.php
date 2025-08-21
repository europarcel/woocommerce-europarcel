<?php
/*
  Plugin Name: EuroParcel WooCommerce Integration
  Plugin URI: https://europarcel.com/
  Description: Connect your WooCommerce store with EuroParcel shipping platform
  Version: 1.0.0
  Author: EuroParcel
  Author URI: https://europarcel.com/
  License: GPL-2.0+
  Text Domain: europarcel
  Domain Path: /languages
  WC requires at least: 5.0
  WC tested up to: 8.9
 */

defined('ABSPATH') || exit;

// Plugin constants
define('EUROPARCEL_VERSION', '1.0.0');
define('EAWB_API_URL', 'https://api.europarcel.com/api/');
define('EAWB_ROOT_PATH', dirname(__FILE__));

// Verifică dacă WooCommerce este activ
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

//require_once EAWB_ROOT_PATH . '/includes/class-europarcel-ajax.php';
// AJAX handlers moved to EuroparcelCheckout class
add_action('woocommerce_shipping_init', 'eawb_shipping_init');

function eawb_shipping_init() {
    if (!class_exists('WC_Eawb_Shipping')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-europarcel-shipping.php';
        add_filter('woocommerce_shipping_methods', 'add_eawb_shipping');
    }
}

//add_filter('woocommerce_shipping_methods', 'add_eawb_shipping');

function add_eawb_shipping($methods) {
    $methods['eawb_shipping'] = 'WC_Eawb_Shipping';
    error_log('[EAWB] Registered methods: ' . print_r($methods, true));
    return $methods;
}

add_action('admin_enqueue_scripts', function () {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] == 'wc-settings') {
        wp_enqueue_style('eawb-admin', plugins_url('assets/css/admin.css', __FILE__));
        wp_enqueue_script('eawb-admin', plugins_url('assets/js/admin.js', __FILE__), array('jquery', 'select2'), '1.0', true);
    }
});

function init_europarcel_checkout() {
    require_once EAWB_ROOT_PATH . '/includes/class-europarcel-checkout.php';
    new EuroparcelCheckout();
}

// Initialize checkout early to ensure AJAX handlers are registered
add_action('init', 'init_europarcel_checkout');

