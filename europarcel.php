<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://eawb.ro
 * @since             1.0.3
 * @package           Europarcel
 *
 * @wordpress-plugin
 * Plugin Name:       EuroParcel Integration for WooCommerce
 * Description:       Connect your WooCommerce store with EuroParcel shipping platform
 * Version:           1.0.3
 * Author:            EuroParcel
 * Author URI:        https://eawb.ro/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       europarcel-com
 * Domain Path:       /languages
 * WC requires at least: 5.0
 * WC tested up to:      8.9
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Currently plugin version.
 * Start at version 1.0.3 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('EUROPARCEL_VERSION', '1.0.3');

/**
 * Plugin constants
 */
define('EUROPARCEL_API_URL', 'https://api.europarcel.com/api/');
define('EUROPARCEL_ROOT_PATH', dirname(__FILE__));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-europarcel-activator.php
 */
require_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-activator.php';
register_activation_hook(__FILE__, array('Europarcel_Activator', 'activate'));

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-europarcel-deactivator.php
 */
require_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-deactivator.php';
register_deactivation_hook(__FILE__, array('Europarcel_Deactivator', 'deactivate'));

// Check if WooCommerce is active
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    return;
}

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS)
 * 
 * @since    1.0.3
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Initialize the shipping method
 * 
 * @since    1.0.3
 */
add_action('woocommerce_shipping_init', 'europarcel_shipping_init');

/**
 * Load the shipping method class
 * 
 * @since    1.0.3
 */
function europarcel_shipping_init() {
    if (!class_exists('Europarcel_Plugin_Shipping_Method')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-europarcel-shipping.php';
        add_filter('woocommerce_shipping_methods', 'europarcel_shipping_add');
    }
}

/**
 * Register the shipping method with WooCommerce
 * 
 * @since    1.0.3
 * @param    array    $methods    Existing shipping methods
 * @return   array                Updated shipping methods
 */
function europarcel_shipping_add($methods) {
    $methods['europarcel_shipping'] = 'Europarcel_Plugin_Shipping_Method';
    return $methods;
}

/**
 * Enqueue admin styles and scripts
 * 
 * @since    1.0.3
 */
add_action('admin_enqueue_scripts', function () {
    $current_screen = get_current_screen();
    if (is_admin() && $current_screen && strpos($current_screen->id, 'woocommerce_page_wc-settings') !== false) {
        wp_enqueue_style('europarcel-admin', plugins_url('assets/css/europarcel-admin.css', __FILE__), array(), '1.0.3');
        wp_enqueue_script('europarcel-admin', plugins_url('assets/js/europarcel-admin.js', __FILE__), array('jquery', 'select2'), '1.0.3', true);
    }
});

/**
 * Initialize the main plugin class
 * 
 * @since    1.0.3
 */
function europarcel_plugin_run() {
    require_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-main.php';
    $plugin = new Europarcel_Main();
    $plugin->run();
}

europarcel_plugin_run();

