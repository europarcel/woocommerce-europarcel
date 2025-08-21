<?php

require_once dirname(__DIR__) . '/lib/europarcel-customer.php';
require_once dirname(__DIR__) . '/lib/europarcel-constants.php';

class EuroparcelCheckout {

    public function __construct() {
        add_action('woocommerce_checkout_create_order', [$this, 'save_locker_to_order'], 10, 2);
        add_action('wp_footer', [$this, 'maybe_display_locker_in_block']);
        
        // AJAX endpoints for locker data
        add_action('wp_ajax_eawb_get_lockers', [$this, 'ajax_get_lockers']);
        add_action('wp_ajax_nopriv_eawb_get_lockers', [$this, 'ajax_get_lockers']);
        add_action('wp_ajax_eawb_get_locker_services', [$this, 'ajax_get_locker_services']);
        add_action('wp_ajax_nopriv_eawb_get_locker_services', [$this, 'ajax_get_locker_services']);
    }

    public function maybe_display_locker_in_block() {
        if (!is_checkout() && !has_block('woocommerce/checkout')) return;
        
        // Styles
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_style('europarcel-modal-css', plugins_url('assets/css/europarcel-modal.css', dirname(__DIR__) . '/europarcel.php'), array(), '1.0');
        wp_enqueue_style('eawb-locker-css', plugins_url('assets/css/locker-delivery.css', dirname(__DIR__) . '/europarcel.php'), array(), '1.0');
        
        // Scripts - Load in dependency order
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
        wp_enqueue_script('europarcel-modal-utils', plugins_url('assets/js/europarcel-modal-utils.js', dirname(__DIR__) . '/europarcel.php'), array(), '1.0', true);
        wp_enqueue_script('europarcel-map-handler', plugins_url('assets/js/europarcel-map-handler.js', dirname(__DIR__) . '/europarcel.php'), array('leaflet-js'), '1.0', true);
        wp_enqueue_script('europarcel-ui-components', plugins_url('assets/js/europarcel-ui-components.js', dirname(__DIR__) . '/europarcel.php'), array('europarcel-modal-utils'), '1.0', true);
        wp_enqueue_script('europarcel-modal-core', plugins_url('assets/js/europarcel-modal-core.js', dirname(__DIR__) . '/europarcel.php'), array('europarcel-map-handler', 'europarcel-ui-components'), '1.0', true);
        wp_enqueue_script('europarcel-locker-selector', plugins_url('assets/js/locker-selector.js', dirname(__DIR__) . '/europarcel.php'), array('jquery', 'europarcel-modal-core'), '1.0', true);
        wp_localize_script('europarcel-locker-selector', 'EuroparcelLockerData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('europarcel_locker_nonce'),
            'carrierPins' => array(
                '1' => plugins_url('assets/images/cargus-pin.png', dirname(__DIR__) . '/europarcel.php'),      // Cargus
                '2' => plugins_url('assets/images/dpd-pin.png', dirname(__DIR__) . '/europarcel.php'),         // DPD  
                '3' => plugins_url('assets/images/fancourier-pin.png', dirname(__DIR__) . '/europarcel.php'),  // FanCourier
                '4' => plugins_url('assets/images/gls-pin.png', dirname(__DIR__) . '/europarcel.php'),         // GLS
                '6' => plugins_url('assets/images/sameday-pin.png', dirname(__DIR__) . '/europarcel.php'),     // SameDay
            ),
        ));
    }

    public function save_locker_to_order($order, $data) {
        if (!empty($_POST['eawb_locker_id'])) {
            $order->update_meta_data('_eawb_locker_id', sanitize_text_field($_POST['eawb_locker_id']));
            $order->update_meta_data('_eawb_locker_instance', sanitize_text_field($_POST['eawb_locker_instance']));
        }
    }

    public function ajax_get_lockers() {
        if (!wp_verify_nonce($_POST['security'], 'europarcel_locker_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

         if (isset($_POST['instance_id']) || !$_POST['instance_id'] )  {
             $instance_id = $_POST['instance_id'];
         } else {
             wp_send_json_error('No lockers found');
         }
        
        try {
            $customer = new \EawbShipping\EawbCustomer($instance_id);
            $lockers = $customer->get_lockers();
            
            if ($lockers && is_array($lockers) && !empty($lockers)) {
                wp_send_json_success($lockers);
            } else {
                wp_send_json_error('No lockers found');
            }
        } catch (\Exception $e) {
            wp_send_json_error('Error fetching lockers: ' . $e->getMessage());
        }
    }

    public function ajax_get_locker_services() {
        if (!wp_verify_nonce($_POST['security'], 'europarcel_locker_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $instance_id = isset($_POST['instance_id']) ? intval($_POST['instance_id']) : 1;
        
        try {
            $settings = get_option('woocommerce_eawb_shipping_' . $instance_id . '_settings');
            
            if (!$settings || !isset($settings['available_services'])) {
                wp_send_json_error('No services configured');
                return;
            }
            
            $all_services = \EawbShipping\EawbConstants::getSettingsServices($settings['available_services']);
            $locker_services = array_filter($all_services, function($service) {
                return $service['service_id'] == 2;
            });
            
            if (empty($locker_services)) {
                wp_send_json_error('No locker services available');
                return;
            }
            
            wp_send_json_success(array_values($locker_services));
            
        } catch (\Exception $e) {
            wp_send_json_error('Error fetching services: ' . $e->getMessage());
        }
    }
    
}
