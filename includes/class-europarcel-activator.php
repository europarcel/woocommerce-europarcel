<?php

/**
 * Fired during plugin activation
 *
 * @link       https://europarcel.com
 * @since      1.0.0
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class Europarcel_Activator {

	/**
	 * Plugin activation handler
	 *
	 * Handles any setup tasks required when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Store plugin version
		if (!get_option('europarcel_version')) {
			add_option('europarcel_version', '1.0.0');
		}
		
		flush_rewrite_rules();
	}

}
