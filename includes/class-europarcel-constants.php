<?php

/**
 * EuroParcel Constants Handler
 *
 * Defines constants and configuration arrays for EuroParcel shipping services.
 * Manages carrier mappings, service IDs, and available shipping options
 * for both standard delivery and locker services.
 *
 * @link       https://eawb.ro
 * @since      1.0.0
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

namespace EuroparcelShipping;

defined('ABSPATH') || exit;

/**
 * EuroParcel Constants Class
 *
 * Provides static methods for retrieving carrier service configurations
 * and mappings. Handles the translation and organization of available
 * shipping services for both admin configuration and API requests.
 *
 * @since      1.0.0
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class EuroparcelConstants {

	/**
	 * Get available shipping services
	 *
	 * Returns an array of all available shipping services with their
	 * display names. Includes both standard delivery and locker services.
	 * All strings are internationalized for translation support.
	 *
	 * @since    1.0.0
	 * @return   array    Array of service keys and their translated display names
	 */
	public static function getAvailableServices() {
		return [
			'cargus_national' => __('Cargus - Delivery to address', 'europarcel-com'),
			'dpd_standard' => __('DPD - Delivery to address', 'europarcel-com'),
			'fan_courier' => __('Fan Courier - Delivery to address', 'europarcel-com'),
			'gls_national' => __('GLS - Delivery to address', 'europarcel-com'),
			'sameday' => __('SameDay - Delivery to address', 'europarcel-com'),
			'bookurier' => __('Bookurier - Delivery to address', 'europarcel-com'),
			'easybox' => __('Sameday EasyBox - Delivery to locker', 'europarcel-com'),
			'fanbox' => __('Fan Courier FANbox - Delivery to locker', 'europarcel-com'),
			'dpdbox' => __('DPD Box - Delivery to locker', 'europarcel-com'),
		];
	}
	/**
	 * Get service settings configuration
	 *
	 * Converts an array of service keys into complete carrier configuration
	 * arrays with carrier IDs and service IDs for API requests.
	 * 
	 * Service ID meanings:
	 * - 1: Standard delivery (home to home)
	 * - 2: Locker delivery (home to locker)
	 *
	 * @since    1.0.0
	 * @param    array    $services    Array of service keys to convert
	 * @return   array    Array of carrier configuration arrays
	 */
	public static function getSettingsServices(array $services) {
		$carrier_settings = [
			'cargus_national' => [
				'carrier' => 'cargus_national',
				'carrier_id' => 1,
				'service_id' => 1
			],
			'dpd_standard' => [
				'carrier' => 'dpd_standard',
				'carrier_id' => 2,
				'service_id' => 1
			],
			'fan_courier' => [
				'carrier' => 'fan_courier',
				'carrier_id' => 3,
				'service_id' => 1
			],
			'gls_national' => [
				'carrier' => 'gls_national',
				'carrier_id' => 4,
				'service_id' => 1
			],
			'sameday' => [
				'carrier' => 'sameday',
				'carrier_id' => 6,
				'service_id' => 1
			],
			'bookurier' => [
				'carrier' => 'bookurier',
				'carrier_id' => 5,
				'service_id' => 1
			],
			'easybox' => [
				'carrier' => 'easybox',
				'carrier_id' => 6,
				'service_id' => 2
			],
			'fanbox' => [
				'carrier' => 'fanbox',
				'carrier_id' => 3,
				'service_id' => 2
			],
			'dpdbox' => [
				'carrier' => 'dpdbox',
				'carrier_id' => 2,
				'service_id' => 2
			],
		];

		$service_configurations = [];
		foreach ($services as $service) {
			if (isset($carrier_settings[$service])) {
				$service_configurations[] = $carrier_settings[$service];
			}
		}

		return $service_configurations;
	}
}
