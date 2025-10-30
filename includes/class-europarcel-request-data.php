<?php

/**
 * EuroParcel Request Data Handler
 *
 * Handles the preparation and management of shipping request data
 * for EuroParcel API calls. Manages carrier configuration, addressing,
 * content details, and extra options for shipping requests.
 *
 * @link       https://eawb.ro
 * @since      1.0.1
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

namespace EuroparcelShipping;

defined('ABSPATH') || exit;
include_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-constants.php';

/**
 * EuroParcel Request Data Class
 *
 * Prepares and manages shipping request data for API calls.
 * Handles carrier selection, addressing, parcel details, and extra options
 * based on WooCommerce shipping instance configuration.
 *
 * @since      1.0.1
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class EuroparcelRequestData {

	/**
	 * The WooCommerce shipping instance ID
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      int    $instance_id    WooCommerce shipping method instance ID
	 */
	private int $instance_id;

	/**
	 * The shipping request data array
	 *
	 * Contains all data required for EuroParcel API shipping requests
	 * including carrier info, addresses, content details, and extra options.
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      array    $request_data    Complete shipping request data
	 */
	private $request_data = [
		'carrier_id' => 0,
		'service_id' => 0,
		'billing_to' => [
			'billing_address_id' => null
		],
		'address_from' => [
			'address_id' => null
		],
		'address_to' => [
			'email' => '',
			'phone' => '',
			'contact' => '',
			'company' => '',
			'country_code' => 'RO',
			'county_name' => '',
			'locality_name' => '',
			'street_name' => '',
			'street_number' => '',
			'street_details' => ''
		],
		'content' => [
			'envelopes_count' => 0,
			'pallets_count' => 0,
			'parcels_count' => 0,
			'total_weight' => 0,
			'parcels' => [
				[
					'size' => [
						'weight' => 1,
						'width' => 15,
						'height' => 15,
						'length' => 15
					],
					'sequence_no' => 1
				]
			]
		],
		'extra' => [
			'sms_sender' => false,
			'open_package' => false,
			'sms_recipient' => false,
			'parcel_content' => 'diverse',
			'internal_identifier' => 'Comanda X',
			'return_package' => false,
			'insurance_amount' => null,
			'insurance_amount_currency' => 'RON',
			'return_of_documents' => false,
			'bank_repayment_amount' => null,
			'bank_repayment_currency' => 'RON',
			'bank_holder' => '',
			'bank_iban' => ''
		],
	];

	/**
	 * Initialize the request data handler
	 *
	 * Sets up shipping request data based on WooCommerce shipping instance
	 * configuration. Configures carriers, services, addresses, and default
	 * parcel specifications.
	 *
	 * @since    1.0.1
	 * @param    int     $instance_id     WooCommerce shipping method instance ID
	 * @param    bool    $allow_locker    Whether to allow locker delivery services
	 */
	public function __construct(int $instance_id, $allow_locker = false) {
		$this->instance_id = $instance_id;
		$settings = get_option('woocommerce_europarcel_shipping_' . $instance_id . '_settings');

		if (!$settings || !isset($settings['available_services'])) {
			return;
		}

		// Ensure available_services is an array, convert string to array if needed
		$available_services = $settings['available_services'];
		if (!is_array($available_services)) {
			$available_services = !empty($available_services) ? [$available_services] : [];
		}

		if (empty($available_services)) {
			return;
		}

		$services_config = \EuroparcelShipping\EuroparcelConstants::getSettingsServices($available_services);
		
		if (count($services_config) == 1) {
			$this->request_data['carrier_id'] = intval($services_config[0]['carrier_id']);
			$this->request_data['service_id'] = intval($services_config[0]['service_id']);
		} else {
			$carriers = count(array_unique(array_column($services_config, 'carrier_id')));
			$services = count(array_unique(array_column($services_config, 'service_id')));
			$this->request_data['carrier_id'] = ($carriers == 1) ? intval($services_config[0]['carrier_id']) : 0;
			
			if ($allow_locker) {
				$this->request_data['service_id'] = ($services == 1) ? intval($services_config[0]['service_id']) : 0;
			} else {
				$this->request_data['service_id'] = 1; // Standard delivery
			}
		}

		$this->request_data['billing_to']['billing_address_id'] = intval($settings['default_billing'] ?? 0);
		$this->request_data['address_from']['address_id'] = intval($settings['default_shipping'] ?? 0);
		$this->request_data['content']['parcels_count'] = 1;
		$this->request_data['content']['total_weight'] = 1;
		$this->request_data['content']['parcels'] = [
			[
				'size' => [
					'weight' => 1,
					'width' => 15,
					'height' => 15,
					'length' => 15
				],
				'sequence_no' => 1
			]
		];
	}

	/**
	 * Set the carrier ID for the shipping request
	 *
	 * @since    1.0.1
	 * @param    int    $carrier_id    The carrier ID from EuroParcel
	 */
	public function setCarrierId($carrier_id) {
		$this->request_data['carrier_id'] = intval($carrier_id);
	}

	/**
	 * Set the service ID for the shipping request
	 *
	 * @since    1.0.1
	 * @param    int    $service_id    The service ID (1=standard, 2=locker)
	 */
	public function setServiceId($service_id) {
		$this->request_data['service_id'] = intval($service_id);
	}

	/**
	 * Set the delivery address for the shipping request
	 *
	 * @since    1.0.1
	 * @param    array    $delivery_address    Complete delivery address data
	 */
	public function setDeliveryAddress($delivery_address) {
		$this->request_data['address_to'] = $delivery_address;
	}

	/**
	 * Get the complete request data array
	 *
	 * Returns the prepared shipping request data ready for EuroParcel API calls.
	 *
	 * @since    1.0.1
	 * @return   array    Complete shipping request data
	 */
	public function getData() {
		return $this->request_data;
	}
}
