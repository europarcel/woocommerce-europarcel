<?php
/*
Plugin Name: Eawb Shipping
Plugin URI: https://eawb.ro/
Description: Metodă personalizată de transport pentru WooCommerce
Version: 1.0.0
Author: Alin
Author URI: https://eawb.ro/
License: GPL-3.0+ pe naiba
Text Domain: woocommerce-shipping-plugin
Domain Path: /languages
*/

defined('ABSPATH') || exit;
define ('EAWB_API_URL','https://api.europarcel.com/api/'); 
define('EAWB_ROOT_PATH', dirname(__FILE__));

// Verifică dacă WooCommerce este activ
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

add_action('woocommerce_shipping_init', 'eawb_shipping_init');

function eawb_shipping_init() {
    if (!class_exists('WC_Eawb_Shipping')) {
        require_once plugin_dir_path(__FILE__) . 'includes/eawb-shipping.php';
    }
}

add_filter('woocommerce_shipping_methods', 'add_eawb_shipping');

function add_eawb_shipping($methods) {
    $methods['eawb_shipping'] = 'WC_Eawb_Shipping';
    error_log('[EAWB] Registered methods: ' . print_r($methods, true));
    return $methods;
}

add_action('admin_enqueue_scripts', function() {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] == 'wc-settings') {
        wp_enqueue_style('eawb-admin', plugins_url('assets/css/admin.css', __FILE__));
        wp_enqueue_script('eawb-admin', plugins_url('assets/js/admin.js', __FILE__), array('jquery', 'select2'), '1.0', true);
    }
});