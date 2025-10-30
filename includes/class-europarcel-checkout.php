<?php

/**
 * EuroParcel Checkout Handler
 *
 * Handles checkout functionality for both WooCommerce Classic and Blocks
 * checkout types. Manages locker selection, AJAX handlers, and script
 * enqueuing for the checkout process.
 *
 * @link       https://eawb.ro
 * @since      1.0.2
 *
 * @package    Europarcel
 * @subpackage Europarcel/includes
 */

require_once dirname(__DIR__) . '/includes/class-europarcel-customer.php';
require_once dirname(__DIR__) . '/includes/class-europarcel-constants.php';

/**
 * EuroParcel Checkout Class
 *
 * Manages checkout integration for both Classic and Blocks checkout.
 * Handles smart initialization, script enqueuing, and AJAX functionality
 * for locker selection and shipping updates.
 *
 * @since      1.0.2
 * @package    Europarcel
 * @subpackage Europarcel/includes
 * @author     EuroParcel <cs@europarcel.com>
 */
class EuroparcelCheckout {

	/**
	 * Whether the current checkout is blocks-based
	 *
	 * @since    1.0.2
	 * @access   private
	 * @var      bool    $is_blocks_checkout    True if blocks checkout detected
	 */
	private $is_blocks_checkout = false;

	/**
	 * Initialize the checkout handler
	 *
	 * Constructor kept minimal - actual initialization happens
	 * via smart_init method to detect checkout type first.
	 *
	 * @since    1.0.2
	 */
	public function __construct() {
		// Constructor kept minimal - initialization happens via smart_init
	}

	/**
	 * Smart initialization - detects checkout type
	 *
	 * Automatically detects whether the current checkout is Classic
	 * or Blocks-based and initializes the appropriate functionality.
	 *
	 * @since    1.0.2
	 */
	public function smart_init() {
		if (!is_checkout()) {
			return;
		}

		$this->is_blocks_checkout = has_block('woocommerce/checkout');

		if ($this->is_blocks_checkout) {
			$this->init_blocks_checkout();
		} else {
			$this->init_classic_checkout();
		}
	}

	/**
	 * Initialize blocks checkout
	 *
	 * Enqueues scripts and localizes data for WooCommerce Blocks checkout.
	 *
	 * @since    1.0.2
	 */
	private function init_blocks_checkout() {
		wp_enqueue_script('europarcel-modal', plugins_url('assets/js/europarcel-modal.js', dirname(__DIR__) . '/europarcel.php'), array('jquery'), '1.0', true);
        wp_enqueue_script('europarcel-locker-selector', plugins_url('assets/js/europarcel-locker-selector.js', dirname(__DIR__) . '/europarcel.php'), array('jquery', 'europarcel-modal'), '2.8', true);
		$this->localize_script_data();
	}

	/**
	 * Initialize classic checkout
	 *
	 * Enqueues scripts and localizes data for WooCommerce Classic checkout.
	 *
	 * @since    1.0.2
	 */
	private function init_classic_checkout() {
		wp_enqueue_script('europarcel-modal', plugins_url('assets/js/europarcel-modal.js', dirname(__DIR__) . '/europarcel.php'), array('jquery'), '1.0', true);
        wp_enqueue_script('europarcel-locker-selector', plugins_url('assets/js/europarcel-locker-selector.js', dirname(__DIR__) . '/europarcel.php'), array('jquery', 'europarcel-modal'), '2.8', true);
		$this->localize_script_data();
	}

	/**
	 * Prepare and localize script data
	 *
	 * Prepares AJAX data including user lockers, available carriers,
	 * and checkout type information for JavaScript usage.
	 *
	 * @since    1.0.2
	 */
	private function localize_script_data() {
        $user_id = get_current_user_id();
        $user_lockers = null;
        $instances_lockers = [];
        $order_lockers = [];
        
        if ($user_id) {
            $user_lockers = get_user_meta($user_id, '_europarcel_carrier_lockers', true);
            if (is_array($user_lockers)) {
                foreach ($user_lockers as $key => $locker) {
                    $order_lockers[] = $locker['carrier_id'];
                }
            }
        }
        
        // Get all shipping method instances from shipping zones
        $shipping_zones = WC_Shipping_Zones::get_zones();
        $shipping_zones[] = new WC_Shipping_Zone(0); // Add default/worldwide zone
        
        foreach ($shipping_zones as $zone_data) {
            if (is_array($zone_data)) {
                $zone = new WC_Shipping_Zone($zone_data['id']);
            } else {
                $zone = $zone_data;
            }
            
            $shipping_methods = $zone->get_shipping_methods();
            foreach ($shipping_methods as $method) {
                if (strpos($method->id, 'europarcel_shipping') === 0 && $method->enabled === 'yes') {
                    $settings = get_option('woocommerce_europarcel_shipping_' . $method->instance_id . '_settings', []);
                    if (isset($settings['available_services'])) {
                        // Ensure available_services is an array, convert string to array if needed
                        $available_services = $settings['available_services'];
                        if (!is_array($available_services)) {
                            $available_services = !empty($available_services) ? [$available_services] : [];
                        }

                        if (!empty($available_services)) {
                            $method_services = \EuroparcelShipping\EuroparcelConstants::getSettingsServices($available_services);
                        $locker_services = array_filter($method_services, function ($service) {
                            return $service['service_id'] == 2;
                        });
                            if (!empty($locker_services)) {
                                $instances_lockers[$method->instance_id] = array_column($locker_services, 'carrier_id');
                            }
                        }
                    }
                }
            }
        }
        
        // Localize script with AJAX parameters
        $script_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('europarcel_locker_nonce'),
            'user_lockers' => $user_lockers,
            'order_lockers' => $order_lockers,
            'instances_lockers' => $instances_lockers,
            'plugin_url' => plugins_url('', dirname(__DIR__) . '/europarcel.php'),
            'checkout_type' => $this->is_blocks_checkout ? 'blocks' : 'classic',
            'i18n' => [
                'select_locker' => __('Select Locker', 'europarcel-com'),
                'modify_locker' => __('Modify Locker', 'europarcel-com'),
                'loading' => __('Loading...', 'europarcel-com'),
                'no_carriers_configured' => __('No couriers configured for locker delivery.', 'europarcel-com'),
                'locker_selected' => __('✓ Locker selected -', 'europarcel-com'),
                'locker_selection_title' => __('Delivery locker selection', 'europarcel-com')
            ]
        ];
        
        wp_localize_script('europarcel-locker-selector', 'europarcel_ajax', $script_data);
    }

	/**
	 * AJAX handler for getting locker carriers
	 *
	 * Retrieves available locker carriers for a shipping instance.
	 * Validates nonce and returns carrier data via JSON response.
	 *
	 * @since    1.0.2
	 */
	public function ajax_get_locker_carriers() {
        try {
            // Verify nonce for security
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'europarcel_locker_nonce')) {
                wp_send_json_error(['message' => __('Security check failed. Please refresh the page and try again.', 'europarcel-com')]);
                return;
            }
            // Get instance ID from the request
            $instance_id = isset($_POST['instance_id']) ? intval($_POST['instance_id']) : 1;
            // Create customer instance and get locker carriers
            $customer = new \EuroparcelShipping\EuroparcelCustomer($instance_id);
            $locker_carriers = $customer->get_locker_carriers();

            wp_send_json_success([
                'carriers' => $locker_carriers
            ]);
        } catch (\Exception $e) {
            // Silently handle error - return empty carriers array
            wp_send_json_success(['carriers' => []]);
        }
    }

	/**
	 * AJAX handler for updating locker shipping
	 *
	 * Updates the selected locker information in session and user meta.
	 * Validates nonce and sanitizes all input data.
	 *
	 * @since    1.0.2
	 */
	public function wp_ajax_update_locker_shipping() {
        // Verify nonce for security
        if (!isset($_POST['security']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'])), 'europarcel_locker_nonce')) {
            wp_send_json_error(['message' => __('Security check failed. Please refresh the page and try again.', 'europarcel-com')]);
            return;
        }

        if (isset($_POST['locker_id']) && isset($_POST['carrier_id']) && isset($_POST['instance_id'])) {
            $locker_info = array(
                'locker_id' => sanitize_text_field(wp_unslash($_POST['locker_id'])),
                'carrier_id' => sanitize_text_field(wp_unslash($_POST['carrier_id'])),
                'instance_id' => sanitize_text_field(wp_unslash($_POST['instance_id'])),
                'carrier_name' => isset($_POST['carrier_name']) ? sanitize_text_field(wp_unslash($_POST['carrier_name'])) : '',
                'locker_name' => isset($_POST['locker_name']) ? sanitize_text_field(wp_unslash($_POST['locker_name'])) : '',
                'locker_address' => isset($_POST['locker_address']) ? sanitize_text_field(wp_unslash($_POST['locker_address'])) : ''
            );
            WC()->session->set('locker_info', $locker_info);
            $user_id = get_current_user_id();
            if ($user_id) {
                $user_lockers = get_user_meta($user_id, '_europarcel_carrier_lockers', true);
                if (is_array($user_lockers) && $locker_info['instance_id']) {
                    $user_lockers = [$locker_info['instance_id'] => $locker_info] + $user_lockers;
                    update_user_meta($user_id, '_europarcel_carrier_lockers', $user_lockers);
                } else {
                    update_user_meta($user_id, '_europarcel_carrier_lockers', [$locker_info['instance_id'] => $locker_info]);
                    $user_lockers = get_user_meta($user_id, '_europarcel_carrier_lockers', true);
                }
            }
            $order_lockers = [];
            if (is_array($user_lockers)) {
                foreach ($user_lockers as $key => $locker) {
                    $order_lockers[] = $locker['carrier_id'];
                }
            }
            wp_send_json_success(['user_locker' => $user_lockers, 'order_lockers' => $order_lockers]);
        } else {
            // Silently handle missing data
            wp_send_json_success(['user_locker' => [], 'order_lockers' => []]);
        }
        wp_die();
    }

	/**
	 * Display locker selection button for classic checkout
	 *
	 * Called by woocommerce_review_order_after_shipping hook to display
	 * the locker selection button in classic checkout when applicable.
	 *
	 * @since    1.0.2
	 */
	public function classic_checkout_button() {
        if (!is_checkout()) {
            return;
        }

        $shipping_methods = WC()->session->get('chosen_shipping_methods');
        
        if (!is_array($shipping_methods) || empty($shipping_methods[0])) {
            return;
        }

        $shipping_method = explode(':', $shipping_methods[0]);
        
        // Check if it's a europarcel shipping method
        // Handle both 'europarcel_shipping' and 'europarcel_shipping_X' formats
        if (empty($shipping_method[0]) || strpos($shipping_method[0], 'europarcel_shipping') !== 0) {
            return;
        }

        // Get the instance ID - could be from session format or embedded in method ID
        $instance_id = '1'; // default
        if (isset($shipping_method[1])) {
            // From session format: europarcel_shipping:1
            $instance_id = $shipping_method[1];
        } else {
            // From embedded format: europarcel_shipping_1
            $parts = explode('_', $shipping_method[0]);
            if (count($parts) >= 3 && is_numeric(end($parts))) {
                $instance_id = end($parts);
            }
        }
        
        // Check if this specific instance has locker services enabled
        $settings = get_option('woocommerce_europarcel_shipping_' . $instance_id . '_settings', []);
        if (empty($settings['available_services'])) {
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

        $method_services = \EuroparcelShipping\EuroparcelConstants::getSettingsServices($available_services);
        $locker_services = array_filter($method_services, function ($service) {
            return $service['service_id'] == 2; // Locker service
        });

        // Only show if this shipping method has locker carriers
        if (empty($locker_services)) {
            return;
        }

        // 🎯 Output button
        echo '<tr class="europarcel-locker-selection">';
        echo '<th></th>';
        echo '<td>';
        echo '<button type="button" class="button alt wp-element-button" onclick="openLockerSelector()" style="width: 100%">' . esc_html__('Select Locker', 'europarcel-com') . '</button>';
        echo '<div class="europarcel-location-details" id="europarcel-location-details" style="display: none;"></div>';
        echo '</td>';
        echo '</tr>';
    }

}
