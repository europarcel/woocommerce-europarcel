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
 * @since             1.0.9
 * @package           Europarcel
 *
 * @wordpress-plugin
 * Plugin Name:       EuroParcel Integration for WooCommerce
 * Description:       Connect your WooCommerce store with eAWB shipping platform
 * Version:           1.0.9
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

define('EUROPARCELCOM_WC_VERSION', '1.0.9');

/**
 * Plugin constants
 */
define('EUROPARCELCOM_WC_API_URL', 'https://api.europarcel.com/api/');
define('EUROPARCELCOM_WC_ROOT_PATH', dirname(__FILE__));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-europarcel-activator.php
 */
require_once EUROPARCELCOM_WC_ROOT_PATH . '/includes/class-europarcel-activator.php';
register_activation_hook(__FILE__, array('EuroParcelComWC_Activator', 'activate'));

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-europarcel-deactivator.php
 */
require_once EUROPARCELCOM_WC_ROOT_PATH . '/includes/class-europarcel-deactivator.php';
register_deactivation_hook(__FILE__, array('EuroParcelComWC_Deactivator', 'deactivate'));

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
 * @since    1.0.9
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Initialize the shipping method
 * 
 * @since    1.0.9
 */
add_action('woocommerce_shipping_init', 'europarcelcom_wc_shipping_init');

/**
 * Load the shipping method class
 * 
 * @since    1.0.9
 */
function europarcelcom_wc_shipping_init() {
    if (!class_exists('EuroParcelComWC_Shipping_Method')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-europarcel-shipping.php';
        add_filter('woocommerce_shipping_methods', 'europarcelcom_wc_shipping_add');
    }
}

/**
 * Register the shipping method with WooCommerce
 * 
 * @since    1.0.9
 * @param    array    $methods    Existing shipping methods
 * @return   array                Updated shipping methods
 */
function europarcelcom_wc_shipping_add($methods) {
    $methods['europarcelcom_wc_shipping'] = 'EuroParcelComWC_Shipping_Method';
    return $methods;
}

/**
 * Enqueue admin styles and scripts
 * 
 * @since    1.0.9
 */
add_action('admin_enqueue_scripts', function () {
    $current_screen = get_current_screen();
    if (is_admin() && $current_screen && strpos($current_screen->id, 'woocommerce_page_wc-settings') !== false) {
        wp_enqueue_style('europarcel-admin', plugins_url('assets/css/europarcel-admin.css', __FILE__), array(), '1.0.9');
        wp_enqueue_script('europarcel-admin', plugins_url('assets/js/europarcel-admin.js', __FILE__), array('jquery', 'select2'), '1.0.9', true);
    }
});

/**
 * Initialize the main plugin class
 * 
 * @since    1.0.9
 */
function europarcelcom_wc_plugin_run() {
    require_once EUROPARCELCOM_WC_ROOT_PATH . '/includes/class-europarcel-main.php';
    $plugin = new EuroParcelComWC_Main();
    $plugin->run();
}

europarcelcom_wc_plugin_run();

/**
 * Locker selection validation - Classic checkout
 *
 * @since    1.0.9
 */
add_action('woocommerce_after_checkout_validation', function($data, $errors) {
    $message = EuroParcelComWC_Checkout::check_locker_selection();
    if ($message) {
        $errors->add('europarcel_locker_required', $message);
    }
}, 10, 2);

/**
 * Locker selection validation - Blocks checkout (Store API)
 *
 * @since    1.0.9
 */
add_action('woocommerce_store_api_checkout_update_order_from_request', function($order, $request) {
    $message = EuroParcelComWC_Checkout::check_locker_selection();
    if ($message) {
        throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
            'europarcel_locker_required',
            $message,
            400
        );
    }
}, 10, 2);

/**
 * Add plugin row meta links (Documentation, Video Tutorial)
 * 
 * @since    1.0.9
 * @param    array     $links    Existing meta links
 * @param    string    $file     Plugin file path
 * @return   array               Updated meta links
 */
add_filter('plugin_row_meta', function($links, $file) {
    if (plugin_basename(__FILE__) === $file) {
        $links[] = '<a href="https://www.eawb.ro/docs/woocommerce" target="_blank">' . esc_html__('Documentation', 'europarcel-com') . '</a>';
        $links[] = '<a href="https://www.eawb.ro/docs/woocommerce" target="_blank">' . esc_html__('Video tutorial', 'europarcel-com') . '</a>';
    }
    return $links;
}, 10, 2);

