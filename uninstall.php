<?php

/**
 * Plugin uninstall handler
 *
 * Handles cleanup when the plugin is uninstalled.
 * Removes plugin data and options from the database.
 *
 * @link       https://eawb.ro
 * @since      1.0.1
 *
 * @package    Europarcel
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Clean up plugin data
delete_option('europarcel_version');
