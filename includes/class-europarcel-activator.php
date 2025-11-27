<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin activation
 *
 * @link       https://europarcel.com
 * @since      1.0.4
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.4
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
	 * @since    1.0.4
	 */
	public static function activate() {
		// Store plugin version
		if (!get_option('EUROPARCELCOM_WC_VERSION')) {
			add_option('EUROPARCELCOM_WC_VERSION', '1.0.4');
		}
		
		flush_rewrite_rules();
	}

}
