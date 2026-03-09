<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * EuroParcel Main Plugin Class
 *
 * Main plugin class that handles initialization, dependencies loading,
 * and WordPress/WooCommerce hooks registration. Manages the checkout
 * handler and AJAX functionality for both checkout types.
 *
 * @link       https://eawb.ro
 * @since      1.0.8
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

/**
 * EuroParcel Main Class
 *
 * Handles plugin initialization and hook registration.
 * Manages checkout detection and AJAX handlers for
 * both classic and blocks checkout types.
 *
 * @since      1.0.8
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class EuroParcelComWC_Main {

	/**
	 * The plugin name
	 *
	 * @since    1.0.8
	 * @access   protected
	 * @var      string    $plugin_name    The plugin identifier name
	 */
	protected $plugin_name;

	/**
	 * The plugin version
	 *
	 * @since    1.0.8
	 * @access   protected
	 * @var      string    $version    The current plugin version
	 */
	protected $version;

	/**
	 * The checkout handler instance
	 *
	 * @since    1.0.8
	 * @access   protected
	 * @var      EuroparcelCheckout    $checkout_handler    Handles checkout functionality
	 */
	protected $checkout_handler;

	/**
	 * Initialize the main plugin class
	 *
	 * Sets the plugin name and version, loads dependencies,
	 * and defines WooCommerce hooks for checkout functionality.
	 *
	 * @since    1.0.8
	 */
	public function __construct() {
		if (defined('EUROPARCELCOM_WC_VERSION')) {
			$this->version = EUROPARCELCOM_WC_VERSION;
		} else {
			$this->version = '1.0.8';
		}
		$this->plugin_name = 'europarcel-com';

		$this->load_dependencies();
		$this->define_woocommerce_hooks();
	}

	/**
	 * Load plugin dependencies
	 *
	 * Loads the checkout handler class required for
	 * managing checkout functionality and locker selection.
	 *
	 * @since    1.0.8
	 */
	private function load_dependencies() {
            $plugin_path=plugin_dir_path(dirname(__FILE__));
		require_once EUROPARCELCOM_WC_ROOT_PATH . '/includes/class-europarcel-checkout.php';
	}

	/**
	 * Define WooCommerce hooks
	 *
	 * Initializes the checkout handler and registers all necessary
	 * WordPress hooks for AJAX functionality and checkout integration.
	 *
	 * @since    1.0.8
	 */
	private function define_woocommerce_hooks() {
		$this->checkout_handler = new EuroParcelComWC_Checkout();

		// Register AJAX handlers for locker functionality
		add_action('wp_ajax_europarcelcomwc_get_locker_carriers', array($this->checkout_handler, 'wp_ajax_europarcelcomwc_get_locker_carriers'));
		add_action('wp_ajax_nopriv_europarcelcomwc_get_locker_carriers', array($this->checkout_handler, 'wp_ajax_europarcelcomwc_get_locker_carriers'));
		add_action('wp_ajax_europarcelcomwc_update_locker_shipping', array($this->checkout_handler, 'wp_ajax_europarcelcomwc_update_locker_shipping'));
		add_action('wp_ajax_nopriv_europarcelcomwc_update_locker_shipping', array($this->checkout_handler, 'wp_ajax_europarcelcomwc_update_locker_shipping'));

		// Smart initialization for both checkout types
		add_action('wp_footer', array($this->checkout_handler, 'smart_init'));

		// Classic checkout integration
		add_action('woocommerce_review_order_after_shipping', array($this->checkout_handler, 'classic_checkout_button'));
	}

	/**
	 * Run the plugin
	 *
	 * All hooks are registered in the constructor,
	 * so this method is available for future use if needed.
	 *
	 * @since    1.0.8
	 */
	public function run() {
		// All hooks are already registered in constructor
	}

	/**
	 * Get the plugin name
	 *
	 * @since     1.0.8
	 * @return    string    The plugin name
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get the plugin version
	 *
	 * @since     1.0.8
	 * @return    string    The plugin version number
	 */
	public function get_version() {
		return $this->version;
	}
}
