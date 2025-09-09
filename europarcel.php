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
 * @since             1.0.0
 * @package           Europarcel
 *
 * @wordpress-plugin
 * Plugin Name:       EuroParcel WooCommerce Integration
 * Plugin URI:        https://eawb.ro/
 * Description:       Connect your WooCommerce store with EuroParcel shipping platform
 * Version:           1.0.0
 * Author:            EuroParcel
 * Author URI:        https://eawb.ro/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       europarcel
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
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('EUROPARCEL_VERSION', '1.0.0');

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
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS)
 * 
 * @since    1.0.0
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Initialize the shipping method
 * 
 * @since    1.0.0
 */
add_action('woocommerce_shipping_init', 'europarcel_shipping_init');

/**
 * Load the shipping method class
 * 
 * @since    1.0.0
 */
function europarcel_shipping_init() {
    if (!class_exists('WC_Europarcel_Shipping')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-europarcel-shipping.php';
        add_filter('woocommerce_shipping_methods', 'add_europarcel_shipping');
    }
}

/**
 * Register the shipping method with WooCommerce
 * 
 * @since    1.0.0
 * @param    array    $methods    Existing shipping methods
 * @return   array                Updated shipping methods
 */
function add_europarcel_shipping($methods) {
    $methods['europarcel_shipping'] = 'WC_Europarcel_Shipping';
    return $methods;
}

/**
 * Enqueue admin styles and scripts
 * 
 * @since    1.0.0
 */
add_action('admin_enqueue_scripts', function () {
    if (is_admin() && isset($_GET['page']) && 'wc-settings' === sanitize_text_field($_GET['page'])) {
        wp_enqueue_style('europarcel-admin', plugins_url('assets/css/europarcel-admin.css', __FILE__));
        wp_enqueue_script('europarcel-admin', plugins_url('assets/js/europarcel-admin.js', __FILE__), array('jquery', 'select2'), '1.0', true);
    }
});

/**
 * Initialize the main plugin class
 * 
 * @since    1.0.0
 */
function run_europarcel() {
    require_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-main.php';
    $plugin = new Europarcel_Main();
    $plugin->run();
}

run_europarcel();

