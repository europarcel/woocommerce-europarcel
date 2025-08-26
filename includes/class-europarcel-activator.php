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
 * @author     Europarcel <support@europarcel.com>
 */
class Europarcel_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Add any activation tasks here
		// Examples:
		// - Create database tables
		// - Set default options
		// - Schedule cron jobs
		// - Create necessary directories
		
		// For now, just ensure the plugin version is stored
		if (!get_option('europarcel_version')) {
			add_option('europarcel_version', '1.0.0');
		}
		
		// Flush rewrite rules to ensure any custom endpoints work
		flush_rewrite_rules();
	}

}
