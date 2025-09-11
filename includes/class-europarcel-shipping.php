<?php

/**
 * EuroParcel WooCommerce Shipping Method
 *
 * Main shipping method class that extends WooCommerce's shipping functionality.
 * Handles configuration, pricing calculations, and integration with EuroParcel API.
 * Supports both standard delivery and locker delivery options.
 *
 * @link       https://eawb.ro
 * @since      1.0.0
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

defined('ABSPATH') || exit;
require_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-customer.php';
require_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-custom-fields.php';
include_once EUROPARCEL_ROOT_PATH . '/includes/class-europarcel-constants.php';

/**
 * WooCommerce EuroParcel Shipping Method Class
 *
 * Extends WC_Shipping_Method to provide EuroParcel shipping integration.
 * Handles admin configuration, shipping calculations, free shipping rules,
 * and both standard and locker delivery options.
 *
 * @since      1.0.0
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class WC_Europarcel_Shipping extends WC_Shipping_Method {

	/**
	 * Initialize the shipping method
	 *
	 * Sets up the shipping method with basic configuration including
	 * instance ID, method title, description, and supported features.
	 *
	 * @since    1.0.0
	 * @param    int    $instance_id    WooCommerce shipping zone instance ID
	 */
	public function __construct($instance_id = 0) {
		$this->instance_id = absint($instance_id);
		$this->id = 'europarcel_shipping';
		
		if ($this->instance_id) {
			$this->id .= '_' . $this->instance_id;
		}
		
		$this->method_title = __('Europarcel Shipping', 'europarcel');
		$this->method_description = __('Premium courier shipping integration with support for home delivery and locker services', 'europarcel');
		$this->supports = array('shipping-zones', 'instance-settings');
		$this->init();

		$this->title = $this->get_option('title', $this->method_title);
	}

	/**
	 * Initialize shipping method settings
	 *
	 * Loads the shipping method settings from WordPress options
	 * and initializes the admin form fields.
	 *
	 * @since    1.0.0
	 */
	public function init() {
		if (!$this->instance_id) {
			return;
		}
		
		$this->settings = get_option('woocommerce_europarcel_shipping_' . $this->instance_id . '_settings');
		$this->init_form_fields();
	}

	/**
	 * Initialize admin form fields
	 *
	 * Creates the admin configuration form fields including API connection,
	 * service selection, pricing options, and free shipping rules.
	 * Dynamically loads customer information when API key is provided.
	 *
	 * @since    1.0.0
	 */
	public function init_form_fields() {
        $this->form_fields = array(
            'section_basic' => array(
                'title' => __('Basic Settings', 'europarcel'),
                'type' => 'title',
                'description' => __('Configure your connection and basic plugin settings', 'europarcel'),
                'class' => 'wc-settings-sub-title',
            ),
            'title' => array(
                'title' => __('Method Title', 'europarcel'),
                'type' => 'text',
                'description' => __('The shipping method title displayed in the admin shipping zones configuration', 'europarcel'),
                'default' => __('Europarcel Shipping', 'europarcel'),
                'desc_tip' => false,
            ),
            'api_key' => array(
                'title' => __('API Key', 'europarcel'),
                'type' => 'text',
                'description' => __('Your API key for accessing the shipping services.<br><a href="https://www.eawb.ro/dashboard/integrations" target="_blank" class="button button-secondary" style="margin-top: 5px;">Get Your API Key Here →</a>', 'europarcel'),
                'desc_tip' => false,
            ),
        );
		$post_data = $this->get_post_data();
		if (isset($post_data['woocommerce_' . $this->id . '_api_key'])) {
			$this->process_admin_options();
		}
		if (!isset($this->settings['api_key']) || empty($this->settings['api_key'])) {
			return;
		}
		
		$customer = new \EuroparcelShipping\EuroparcelCustomer($this->instance_id);
		$customer_info = $customer->getCustomerInfo();
		
		if (!$customer_info) {
			$this->form_fields = array_merge($this->form_fields, array(
				'europarcel_customer' => array(
					'title' => __('Connection Error', 'europarcel'),
					'type' => 'title',
					'description' => __('<div class="notice notice-error inline"><p><strong>Unable to connect to eAWB.</strong> Please verify your API key is correct and try again.</p></div>', 'europarcel'),
			)));
			return;
		} else {
			$this->update_option('europarcel_customer', $customer_info);
		}
        $shipping_classes = get_terms(array('taxonomy' => 'product_shipping_class', 'hide_empty' => false));
        $view_shipping_classes = [];
        foreach ($shipping_classes as $shipping_class) {
            $view_shipping_classes[$shipping_class->slug] = $shipping_class->name;
        }
        $this->form_fields = array_merge($this->form_fields, array(
            'customer_info' => array(
                'title' => __('✅ Connected: ' . esc_html($customer_info['name']), 'europarcel'),
                'type' => 'title',
            ),
            'section_service_config' => array(
                'title' => __('Service Configuration', 'europarcel'),
                'type' => 'title',
                'description' => __('Configure your shipping addresses and available services', 'europarcel'),
                'class' => 'wc-settings-sub-title',
            ),
            'default_shipping' => array(
                'title' => __('Primary Sender Address', 'europarcel'),
                'type' => 'select',
                'description' => __('Default sender address for eAWB order imports<br><a href="https://www.eawb.ro/dashboard/addresses?tab=sender" target="_blank" class="button button-secondary" style="margin-top: 5px;">Manage Sender Addresses →</a>', 'europarcel'),
                'desc_tip' => false,
                'default' => "",
                'options' => $customer->getPickupAddresses(),
            ),
            'default_billing' => array(
                'title' => __('Primary Billing Address', 'europarcel'),
                'type' => 'select',
                'description' => __('Default billing address for eAWB order imports<br><a href="https://www.eawb.ro/dashboard/addresses?tab=billing" target="_blank" class="button button-secondary" style="margin-top: 5px;">Manage Billing Addresses →</a>', 'europarcel'),
                'desc_tip' => false,
                'default' => "",
                'options' => $customer->getCustomerBillingAddresses(),
            ),
            'available_services' => array(
                'title' => __('Available Shipping Services', 'europarcel'),
                'type' => 'multiselect',
                'description' => __('Select the shipping services that will be available to your customers during checkout', 'europarcel'),
                'desc_tip' => false,
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px;height:450px;',
                'default' => array(),
                'options' => \EuroparcelShipping\EuroparcelConstants::getAvailableServices()
            ),
            'excluded_locker_classes' => array(
                'title' => __('Excluded Shipping Classes for Lockers', 'europarcel'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px',
                'default' => array(),
                'description' => __('Select shipping classes that should not have locker delivery options available (e.g., large products or items unsuitable for lockers)', 'europarcel'),
                'desc_tip' => false,
                'options' => $view_shipping_classes
            ),
            'section_home_delivery' => array(
                'title' => __('Home Delivery Settings', 'europarcel'),
                'type' => 'title',
                'description' => __('Configure address delivery options and pricing', 'europarcel'),
                'class' => 'wc-settings-sub-title',
            ),
            'title_for_h2h' => array(
                'title' => __('Home Delivery Display Name', 'europarcel'),
                'type' => 'text',
                'default' => 'Livrare la adresa',
                'description' => __('The shipping method name shown to customers at checkout for home delivery', 'europarcel'),
                'desc_tip' => false,
            ),
            'fixed_price_h2h' => array(
                'title' => __('Home Delivery Fixed Price', 'europarcel'),
                'type' => 'number',
                'default' => 15,
                'description' => __('Set the fixed shipping cost for address deliveries', 'europarcel'),
                'desc_tip' => false,
            ),
            'free_shipping_amount_to_home' => array(
                'title' => __('Free Home Delivery Minimum Order Amount', 'europarcel'),
                'type' => 'number',
                'default' => 0,
                'description' => __('Minimum order amount required to qualify for free home delivery (leave empty for no free shipping)', 'europarcel'),
                'desc_tip' => false,
            ),
            'free_shipping_classes_to_home' => array(
                'title' => __('Free Home Delivery Shipping Classes', 'europarcel'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px',
                'default' => array(),
                'description' => __('Select shipping classes that will always have free home delivery regardless of order amount', 'europarcel'),
                'desc_tip' => false,
                'options' => $view_shipping_classes
            ),
            'section_locker_delivery' => array(
                'title' => __('Locker Delivery Settings', 'europarcel'),
                'type' => 'title',
                'description' => __('Configure locker delivery options and pricing', 'europarcel'),
                'class' => 'wc-settings-sub-title',
            ),
            'title_for_h2l' => array(
                'title' => __('Locker Delivery Display Name', 'europarcel'),
                'type' => 'text',
                'default' => 'Transport la lockerul ales prin Europarcel',
                'description' => __('The shipping method name shown to customers at checkout for locker delivery', 'europarcel'),
                'desc_tip' => false,
            ),
            'fixed_price_h2l' => array(
                'title' => __('Locker Delivery Fixed Price', 'europarcel'),
                'type' => 'number',
                'default' => 15,
                'description' => __('Set the fixed shipping cost for locker deliveries', 'europarcel'),
                'desc_tip' => false,
            ),
            'free_shipping_amount_to_locker' => array(
                'title' => __('Free Locker Delivery Minimum Order Amount', 'europarcel'),
                'type' => 'number',
                'default' => 0,
                'description' => __('Minimum order amount required to qualify for free locker delivery (leave empty for no free shipping)', 'europarcel'),
                'desc_tip' => false,
            ),
            'free_shipping_classes_to_locker' => array(
                'title' => __('Free Locker Delivery Shipping Classes', 'europarcel'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 450px',
                'default' => array(),
                'description' => __('Select shipping classes that will always have free locker delivery regardless of order amount', 'europarcel'),
                'desc_tip' => false,
                'options' => $view_shipping_classes
            ),
        ));
    }

	/**
	 * Process admin form options
	 *
	 * Handles the processing and saving of admin configuration options.
	 * Validates nonce for security and processes each form field.
	 *
	 * @since    1.0.0
	 * @return   bool    True if options were saved successfully, false otherwise
	 */
	public function process_admin_options() {
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'woocommerce-settings')) {
			return false;
		}
		
		$post_data = $this->get_post_data();
		$form_fields = $this->get_form_fields();

		foreach ($form_fields as $key => $field) {
			try {
				$this->settings[$key] = $this->get_field_value($key, $field, $post_data);
			} catch (Exception $e) {
				$this->add_error($e->getMessage());
			}
		}

		$settings_key = $this->plugin_id . $this->id . '_settings';
		$settings_value = apply_filters('woocommerce_shipping_' . $this->id . '_settings_values', $this->settings, $this);

		if (get_option($settings_key)) {
			return update_option($settings_key, $settings_value, 'yes');
		} else {
			return add_option($settings_key, $settings_value);
		}
	}

	/**
	 * Calculate shipping costs for a package
	 *
	 * Main shipping calculation method that handles coupon validation,
	 * free shipping rules, product class exclusions, and locker availability.
	 * Creates shipping rates for both standard and locker delivery options.
	 *
	 * @since    1.0.0
	 * @param    array    $package    WooCommerce package data with contents and destination
	 */
	public function calculate_shipping($package = array()) {
		// Check for coupon-based free shipping first
		$applied_coupons = WC()->cart->get_applied_coupons();
		foreach ($applied_coupons as $coupon_code) {
			$coupon = new WC_Coupon($coupon_code);
			if ($coupon->get_free_shipping()) {
				$this->add_rate(array(
					'id' => $this->id . '_free',
					'label' => 'Transport gratuit cupon',
					'cost' => 0,
					'package' => $package,
					'meta_data' => [
						'carrier_id' => 0,
						'service_id' => 0,
					]
				));
				return;
			}
		}
		$customer = new \EuroparcelShipping\EuroparcelCustomer($this->instance_id);
		if (!$customer->settings) {
			return false;
		}

		$contents = $package['contents'];
		$allow_locker_shipping = true;
		$has_free_shipping_to_home = false;
		$has_free_shipping_to_locker = false;
		$products_free_shipping_to_home = 0;
		$products_free_shipping_to_locker = 0;
		// Analyze package contents for shipping class rules
		foreach ($contents as $product) {
			if (!$product['data']->needs_shipping()) {
				continue;
			}

			$product_shipping_class = $product['data']->get_shipping_class();

			// Check free shipping to home by product class
			if (isset($customer->settings['free_shipping_classes_to_home']) && $customer->settings['free_shipping_classes_to_home']) {
				if (in_array($product_shipping_class, $customer->settings['free_shipping_classes_to_home'])) {
					$products_free_shipping_to_home++;
				}
			}

			// Check free shipping to locker by product class
			if (isset($customer->settings['free_shipping_classes_to_locker']) && $customer->settings['free_shipping_classes_to_locker']) {
				if (in_array($product_shipping_class, $customer->settings['free_shipping_classes_to_locker'])) {
					$products_free_shipping_to_locker++;
				}
			}

			// Check if locker delivery is excluded for this product class
			if (isset($customer->settings['excluded_locker_classes']) && $customer->settings['excluded_locker_classes']) {
				if (in_array($product_shipping_class, $customer->settings['excluded_locker_classes'])) {
					$allow_locker_shipping = false;
				}
			}
		}
		// Determine if all products qualify for free shipping by class
		if (count($contents) == $products_free_shipping_to_home) {
			$has_free_shipping_to_home = true;
		}
		if (count($contents) == $products_free_shipping_to_locker) {
			$has_free_shipping_to_locker = true;
		}

		// Check free shipping by order amount to home
		if (isset($customer->settings['free_shipping_amount_to_home']) && $customer->settings['free_shipping_amount_to_home'] > 0) {
			$package_amount = WC()->cart->cart_contents_total + WC()->cart->tax_total;
			if ($package_amount >= $customer->settings['free_shipping_amount_to_home']) {
				$has_free_shipping_to_home = true;
			}
		}

		// Check free shipping by order amount to locker
		if (isset($customer->settings['free_shipping_amount_to_locker']) && $customer->settings['free_shipping_amount_to_locker'] > 0) {
			$package_amount = WC()->cart->cart_contents_total + WC()->cart->tax_total;
			if ($package_amount >= $customer->settings['free_shipping_amount_to_locker']) {
				$has_free_shipping_to_locker = true;
			}
		}
		// Add standard delivery option (home to home)
		$home_label = isset($customer->settings['title_for_h2h']) ? $customer->settings['title_for_h2h'] : 'Livrare la adresa';
		if (!empty($customer->get_home_carriers())) {
			if ($has_free_shipping_to_home) {
				$this->add_rate(array(
					'id' => $this->id . '_free_h2h',
					'label' => $home_label,
					'cost' => 0,
					'package' => $package,
					'meta_data' => [
						'carrier_id' => 0,
						'service_id' => 1,
					]
				));
			} else {
				$this->add_rate(array(
					'id' => $this->id . '_fixed_h2h',
					'label' => $home_label,
					'cost' => $customer->settings['fixed_price_h2h'],
					'package' => $package,
					'meta_data' => [
						'carrier_id' => 0,
						'service_id' => 1,
					]
				));
			}
		}

		// Add locker delivery option (home to locker)
		$customer_locker_carriers = $customer->get_locker_carriers();
		if ($allow_locker_shipping && !empty($customer_locker_carriers)) {
			$locker_info = WC()->session->get('locker_info');
			$user_id = get_current_user_id();

			// Check for saved user locker preferences
			if ($user_id) {
				$user_lockers = get_user_meta($user_id, '_europarcel_carrier_lockers', true);
				if (is_array($user_lockers)) {
					foreach ($user_lockers as $carrier_id => $locker) {
						if (in_array($carrier_id, $customer_locker_carriers) && is_array($locker)) {
							$locker_info = [
								'locker_id' => $locker['locker_id'],
								'carrier_id' => $locker['carrier_id'],
								'instance_id' => $this->instance_id,
								'carrier_name' => $locker['carrier_name'],
								'locker_name' => $locker['locker_name'],
								'locker_address' => $locker['locker_address']
							];
							break;
						}
					}
				}
			}

			$meta_data = [
				'service_id' => 2,
				'is_locker' => true,
				'fixed_location_id' => $locker_info ? $locker_info['locker_id'] : 0,
				'carrier_id' => $locker_info ? $locker_info['carrier_id'] : 0,
			];

			$locker_label = isset($customer->settings['title_for_h2l']) ? $customer->settings['title_for_h2l'] : 'Livrare la locker';

			if ($has_free_shipping_to_locker) {
				$this->add_rate(array(
					'id' => $this->id . '_free_locker_' . $this->instance_id,
					'label' => $locker_label,
					'cost' => 0,
					'package' => $package,
					'meta_data' => $meta_data
				));
			} else {
				$this->add_rate(array(
					'id' => $this->id . '_fixed_locker_' . $this->instance_id,
					'label' => $locker_label,
					'cost' => $customer->settings['fixed_price_h2l'],
					'package' => $package,
					'meta_data' => $meta_data
				));
			}
		}
    }
}
