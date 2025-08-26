<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://europarcel.com
 * @since      1.0.0
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     Europarcel <support@europarcel.com>
 */
class Europarcel_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Add any deactivation tasks here
		// Examples:
		// - Clear scheduled cron jobs
		// - Clean up temporary data
		// - Clear caches
		
		// Clear any scheduled hooks if you add them in the future
		// wp_clear_scheduled_hook('europarcel_daily_sync');
		
		// Flush rewrite rules
		flush_rewrite_rules();
	}

}
