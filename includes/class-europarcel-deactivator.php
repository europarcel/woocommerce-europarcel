<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin deactivation
 *
 * @link       https://europarcel.com
 * @since      1.0.3
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.3
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class EuroParcelComWC_Deactivator {

	/**
	 * Plugin deactivation handler
	 *
	 * Handles any cleanup tasks required when the plugin is deactivated.
	 *
	 * @since    1.0.3
	 */
	public static function deactivate() {
		// Nothing to do on deactivation
	}

}
