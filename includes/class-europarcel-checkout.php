<?php

require_once dirname(__DIR__) . '/lib/europarcel-customer.php';
require_once dirname(__DIR__) . '/lib/europarcel-constants.php';

class EuroparcelCheckout {

    public function __construct() {
        //add js 
        add_action('wp_footer', [$this, 'add_locker_in_block']);
        //set ajax for checkout
        add_action('wp_ajax_get_locker_carriers', [$this, 'ajax_get_locker_carriers']);
        add_action('wp_ajax_nopriv_get_locker_carriers', [$this, 'ajax_get_locker_carriers']);
        // Add update locker shipping endpoint
        add_action('wp_ajax_update_locker_shipping', [$this, 'wp_ajax_update_locker_shipping']);
        add_action('wp_ajax_nopriv_update_locker_shipping', [$this, 'wp_ajax_update_locker_shipping']);
    }

    public function add_locker_in_block() {
        if (!is_checkout() && !has_block('woocommerce/checkout')) {
            return;
        }
        wp_enqueue_script('europarcel-locker-selector', plugins_url('assets/js/locker-selector.js', dirname(__DIR__) . '/europarcel.php'), array('jquery'), '2.1', true);
        $user_id = get_current_user_id();
        //if current clent is logged in and have saved lockers get from user meta
        $user_lockers = null;
        $instances_lockers = [];
        $order_lockers = [];
        if ($user_id) {
            $user_lockers = get_user_meta($user_id, '_europarcel_carrier_lockers', true);
            if (is_array($user_lockers)) {
                foreach ($user_lockers as $key => $locker) {
                    $order_lockers[] = $key;
                }
            }
        }
        $shipping_methods = WC()->shipping()->get_shipping_methods();
        foreach ($shipping_methods as $method) {
            $settings = $method->settings;
            if (get_class($method) == 'WC_Europarcel_Shipping' && isset($settings['available_services'])) {
                $method_services = \EuroparcelShipping\EuroparcelConstants::getSettingsServices($settings['available_services']);
                $locker_services = array_filter($method_services, function ($service) {
                    return $service['service_id'] == 2;
                });
                $instances_lockers[$method->instance_id] = array_column($locker_services, 'carrier_id');
            }
        }
        // Localize script with AJAX parameters
        wp_localize_script('europarcel-locker-selector', 'europarcel_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('europarcel_locker_nonce'),
            'user_lockers' => $user_lockers,
            'order_lockers' => $order_lockers,
            'instances_lockers' => $instances_lockers,
            'plugin_url' => plugins_url('', dirname(__DIR__) . '/europarcel.php')
        ]);
    }

    public function ajax_get_locker_carriers() {
        try {
            // Verify nonce for security
            if (!wp_verify_nonce($_POST['nonce'], 'europarcel_locker_nonce')) {
                wp_send_json_error(['message' => 'Security check failed']);
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
            wp_send_json_error([
                'message' => 'Failed to get locker carriers: ' . $e->getMessage()
            ]);
        }
    }

    public function wp_ajax_update_locker_shipping() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['security'], 'europarcel_locker_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        if (isset($_POST['locker_id']) && isset($_POST['carrier_id']) && isset($_POST['instance_id'])) {
            $locker_info = array(
                'locker_id' => sanitize_text_field($_POST['locker_id']),
                'carrier_id' => sanitize_text_field($_POST['carrier_id']),
                'instance_id' => sanitize_text_field($_POST['instance_id']),
                'carrier_name' => sanitize_text_field($_POST['carrier_name']),
                'locker_name' => sanitize_text_field($_POST['locker_name']),
                'locker_address' => sanitize_text_field($_POST['locker_address'])
            );
            WC()->session->set('locker_info', $locker_info);
            $user_id = get_current_user_id();
            if ($user_id) {
                $user_lockers = get_user_meta($user_id, '_europarcel_carrier_lockers', true);
                if ($user_lockers && $locker_info['carrier_id']) {
                    $user_lockers = [$locker_info['carrier_id'] => $locker_info] + $user_lockers;
                    update_user_meta($user_id, '_europarcel_carrier_lockers', $user_lockers);
                } else {
                    update_user_meta($user_id, '_europarcel_carrier_lockers', [$locker_info['carrier_id'] => $locker_info]);
                    $user_lockers = get_user_meta($user_id, '_europarcel_carrier_lockers', true);
                }
            }
            $order_lockers = [];
            if (is_array($user_lockers)) {
                foreach ($user_lockers as $key => $locker) {
                    $order_lockers[] = $key;
                }
            }
            wp_send_json_success(['user_locker' => $user_lockers, 'order_lockers' => $order_lockers]);
        } else {
            wp_send_json_error('Date lipsă');
        }
        wp_die();
    }
}
