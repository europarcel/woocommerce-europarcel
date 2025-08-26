<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://europarcel.com
 * @since      1.0.0
 *
 * @package    Europarcel
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Europarcel uninstall cleanup
 * 
 * Remove all data created by the plugin
 */

// Delete plugin options
delete_option('europarcel_version');

// Delete all shipping method settings
// Get all shipping zones
$zones = WC_Shipping_Zones::get_zones();
foreach ($zones as $zone) {
    if (isset($zone['shipping_methods'])) {
        foreach ($zone['shipping_methods'] as $method) {
            if ($method->id === 'eawb_shipping') {
                // Delete method settings
                delete_option('woocommerce_eawb_shipping_' . $method->instance_id . '_settings');
            }
        }
    }
}

// Also check "Rest of the World" zone
$zone_0 = WC_Shipping_Zones::get_zone(0);
if ($zone_0) {
    $methods = $zone_0->get_shipping_methods();
    foreach ($methods as $method) {
        if ($method->id === 'eawb_shipping') {
            delete_option('woocommerce_eawb_shipping_' . $method->instance_id . '_settings');
        }
    }
}

// Delete any transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_europarcel_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_europarcel_%'");

// Delete any custom meta data from orders
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_eawb_%'");

// If using HPOS (High Performance Order Storage), also clean order meta from the new tables
if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && 
    \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
    $wpdb->query("DELETE FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key LIKE '_eawb_%'");
}

// Clear any scheduled hooks
wp_clear_scheduled_hook('europarcel_daily_sync');

// Flush rewrite rules to clean up any custom endpoints
flush_rewrite_rules();
