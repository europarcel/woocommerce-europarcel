<?php

/**
 * EuroParcel Customer Handler
 *
 * Manages customer interactions with the EuroParcel API including
 * account information, billing/shipping addresses, pricing calculations,
 * and carrier services. Handles both standard and locker delivery options.
 *
 * @link       https://eawb.ro
 * @since      1.0.0
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

namespace EuroparcelShipping;

defined('ABSPATH') || exit;
include_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-constants.php';
require_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-request-data.php';
require_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-http-request.php';

/**
 * EuroParcel Customer Class
 *
 * Handles customer account operations and shipping calculations.
 * Manages API interactions for pricing, address validation,
 * and carrier service availability.
 *
 * @since      1.0.0
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class EuroparcelCustomer {

	/**
	 * The WooCommerce shipping instance ID
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int    $instance_id    WooCommerce shipping method instance ID
	 */
	private int $instance_id;

	/**
	 * The shipping method settings
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      array    $settings    WooCommerce shipping method settings
	 */
	public $settings;

	/**
	 * Initialize the customer handler
	 *
	 * Sets up the customer handler with WooCommerce shipping instance
	 * configuration and loads the associated settings.
	 *
	 * @since    1.0.0
	 * @param    int    $instance_id    WooCommerce shipping method instance ID
	 */
	public function __construct($instance_id) {
		$this->instance_id = $instance_id;
		$this->settings = get_option('woocommerce_europarcel_shipping_' . $this->instance_id . '_settings');
	}

	/**
	 * Get customer account information
	 *
	 * Retrieves the customer's account profile information from
	 * the EuroParcel API including name, contact details, and account status.
	 *
	 * @since    1.0.0
	 * @return   array|null    Customer account data or null on failure
	 */
	public function getCustomerInfo() {
		try {
			$http_request = new \EuroparcelShipping\EuroparcelHttpRequest($this->instance_id);
			$response = $http_request->get('public/account/profile');
		} catch (\Exception $ex) {
			return null;
		}

		if (is_array($response) && isset($response['data']['name'])) {
			return $response['data'];
		}

		return null;
	}

	/**
	 * Get customer billing addresses
	 *
	 * Retrieves all billing addresses associated with the customer's
	 * EuroParcel account for use in shipping calculations.
	 *
	 * @since    1.0.0
	 * @return   array    Array of billing addresses with ID as key and formatted address as value
	 */
	public function getCustomerBillingAddresses() {
		try {
			$data = [
				'page' => 1,
				'per_page' => 200
			];
			$http_request = new \EuroparcelShipping\EuroparcelHttpRequest($this->instance_id);
			$response = $http_request->get('public/addresses/billing', $data);

			if (is_array($response) && isset($response['list'])) {
				$addresses = [];
				$addresses[0] = '';
				
				foreach ($response['list'] as $address) {
					if ($address['address_type'] == 'individual') {
						$addresses[$address['id']] = $address['contact'] . ', ' . $address['locality_name'] . ', ' . $address['street_no'];
					} else {
						$addresses[$address['id']] = $address['company'] . ', ' . $address['locality_name'] . ', ' . $address['street_no'];
					}
				}
				return $addresses;
			}

			return [];
		} catch (\Exception $ex) {
			return [];
		}
	}

	/**
	 * Get customer pickup addresses
	 *
	 * Retrieves all pickup/shipping addresses associated with the customer's
	 * EuroParcel account for use as pickup locations.
	 *
	 * @since    1.0.0
	 * @return   array    Array of pickup addresses with ID as key and formatted address as value
	 */
	public function getPickupAddresses() {
		try {
			$data = [
				'page' => 1,
				'per_page' => 200
			];
			$http_request = new \EuroparcelShipping\EuroparcelHttpRequest($this->instance_id);
			$response = $http_request->get('public/addresses/shipping', $data);

			if (is_array($response) && isset($response['list'])) {
				$addresses = [];
				$addresses[0] = '';
				
				foreach ($response['list'] as $address) {
					if ($address['address_type'] == 'individual') {
						$addresses[$address['id']] = $address['contact'] . ', ' . $address['locality_name'] . ', ' . $address['street_no'];
					} else {
						$addresses[$address['id']] = $address['company'] . ', ' . $address['locality_name'] . ', ' . $address['street_no'];
					}
				}
				return $addresses;
			}

			return [];
		} catch (\Exception $ex) {
			return [];
		}
	}

	/**
	 * Get shipping prices for a package
	 *
	 * Calculates shipping costs for both standard delivery (home to home)
	 * and locker delivery (home to locker) options based on package details
	 * and destination address.
	 *
	 * @since    1.0.0
	 * @param    array    $package        WooCommerce package data with destination details
	 * @param    bool     $allow_locker   Whether to include locker delivery options
	 * @return   array|false    Array containing [standard_services, locker_services] or false on failure
	 */
	public function getPrices($package, $allow_locker) {
		$data = new \EuroparcelShipping\EuroparcelRequestData($this->instance_id, $allow_locker);
		
		if (!$this->settings['europarcel_customer']) {
			return false;
		}
		
		if (!$package['destination']['city']) {
			return false;
		}
		
		$delivery_address = [
			'email' => $this->settings['europarcel_customer']['email'],
			'phone' => $this->settings['europarcel_customer']['phone'],
			'contact' => $this->settings['europarcel_customer']['name'],
			'company' => isset($this->settings['europarcel_customer']['company']) ? $this->settings['europarcel_customer']['company'] : $this->settings['europarcel_customer']['name'],
			'country_code' => $package['destination']['country'],
			'county_name' => WC()->countries->get_states($package['destination']['country'])[$package['destination']['state']],
			'locality_name' => $package['destination']['city'],
			'street_name' => $package['destination']['address'] ? $package['destination']['address'] : 'principala',
			'street_number' => '1',
			'street_details' => ''
		];
		$data->setDeliveryAddress($delivery_address);
		
		try {
			$http_request = new \EuroparcelShipping\EuroparcelHttpRequest($this->instance_id);
			$response = $http_request->post('public/orders/prices', $data->getData());
		} catch (\Exception $ex) {
			return false;
		}
		
		if (is_array($response) && isset($response['data'])) {
			// Ensure available_services is an array, convert string to array if needed
			$available_services = $this->settings['available_services'];
			if (!is_array($available_services)) {
				$available_services = !empty($available_services) ? [$available_services] : [];
			}

			if (empty($available_services)) {
				return false;
			}

			$services_config = \EuroparcelShipping\EuroparcelConstants::getSettingsServices($available_services);
			$standard_services = []; // Standard delivery (home to home)
			$locker_services = [];   // Locker delivery (home to locker)
			
			foreach ($services_config as $service_config) {
				foreach ($response['data'] as $service) {
					// Standard delivery service (service_id = 1)
					if ($service_config['carrier_id'] == $service['carrier_id'] && 
						$service_config['service_id'] == $service['service_id'] && 
						$service['service_id'] == 1) {
						$standard_services[] = $service;
					}
					// Locker delivery service (service_id = 2)
					if ($service_config['carrier_id'] == $service['carrier_id'] && 
						$service_config['service_id'] == $service['service_id'] && 
						$service['service_id'] == 2) {
						$locker_services[] = $service;
					}
				}
			}
		}
		
		// Sort by price if configured
		if (isset($this->settings['courier_choice_method']) && $this->settings['courier_choice_method'] == 'low_price') {
			usort($standard_services, array($this, 'sort_by_price'));
			usort($locker_services, array($this, 'sort_by_price'));
		}
		
		return [$standard_services, $locker_services];
	}

	/**
	 * Sort services by price (ascending)
	 *
	 * Comparison function for sorting shipping services by total price.
	 * Used with usort() to arrange services from lowest to highest cost.
	 *
	 * @since    1.0.0
	 * @param    array    $first_service     First service for comparison
	 * @param    array    $second_service    Second service for comparison
	 * @return   int      -1, 0, or 1 for sorting comparison
	 */
	private function sort_by_price($first_service, $second_service) {
		if ($first_service['price']['total'] == $second_service['price']['total']) {
			return 0;
		}
		return ($first_service['price']['total'] < $second_service['price']['total']) ? -1 : 1;
	}

	/**
	 * Get available locker carriers
	 *
	 * Retrieves all carrier IDs that support locker delivery
	 * based on the configured available services.
	 *
	 * @since    1.0.0
	 * @return   array    Array of carrier IDs that support locker delivery
	 */
	public function get_locker_carriers() {
		if (!$this->settings || !isset($this->settings['available_services'])) {
			return [];
		}

		// Ensure available_services is an array, convert string to array if needed
		$available_services = $this->settings['available_services'];
		if (!is_array($available_services)) {
			$available_services = !empty($available_services) ? [$available_services] : [];
		}

		if (empty($available_services)) {
			return [];
		}

		$all_services = \EuroparcelShipping\EuroparcelConstants::getSettingsServices($available_services);
		$locker_services = array_filter($all_services, function ($service) {
			return $service['service_id'] == 2; // Locker delivery service
		});

		if (empty($locker_services)) {
			return [];
		}

		return array_column($locker_services, 'carrier_id');
	}

	/**
	 * Get available standard delivery carriers
	 *
	 * Retrieves all carrier IDs that support standard home delivery
	 * based on the configured available services.
	 *
	 * @since    1.0.0
	 * @return   array|false    Array of carrier IDs that support standard delivery or false if none available
	 */
	public function get_home_carriers() {
		if (!$this->settings || !isset($this->settings['available_services'])) {
			return false;
		}

		// Ensure available_services is an array, convert string to array if needed
		$available_services = $this->settings['available_services'];
		if (!is_array($available_services)) {
			$available_services = !empty($available_services) ? [$available_services] : [];
		}

		if (empty($available_services)) {
			return false;
		}

		$all_services = \EuroparcelShipping\EuroparcelConstants::getSettingsServices($available_services);
		$standard_services = array_filter($all_services, function ($service) {
			return $service['service_id'] == 1; // Standard delivery service
		});

		if (empty($standard_services)) {
			return false;
		}

		return array_column($standard_services, 'carrier_id');
	}

}
