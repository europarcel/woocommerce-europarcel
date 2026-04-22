<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin activation
 *
 * @link       https://europarcel.com
 * @since      1.0.9
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.9
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class EuroParcelComWC_Activator {

	/**
	 * Plugin activation handler
	 *
	 * Handles any setup tasks required when the plugin is activated.
	 *
	 * @since    1.0.9
	 */
	public static function activate() {
		// Store/refresh plugin version. Uses update_option so upgrades actually
		// bump the stored value — add_option is a no-op if the option exists.
		update_option('EUROPARCELCOM_WC_VERSION', EUROPARCELCOM_WC_VERSION);

		flush_rewrite_rules();
	}

}
