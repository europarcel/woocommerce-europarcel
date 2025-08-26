<?php

require_once dirname(__DIR__) . '/lib/europarcel-customer.php';
require_once dirname(__DIR__) . '/lib/europarcel-constants.php';

class EuroparcelCheckout {

    public function __construct() {
        //add_action('woocommerce_store_api_checkout_update_order_meta', [$this, 'save_locker_to_order'], 10, 1);
        add_action('wp_footer', [$this, 'maybe_display_locker_in_block']);
        //add_action('woocommerce_cart_calculate_fees', [$this, 'update_rate_meta_locker'], 20, 1);
        // Add AJAX endpoints for locker functionality
        add_action('wp_ajax_get_locker_carriers', [$this, 'ajax_get_locker_carriers']);
        add_action('wp_ajax_nopriv_get_locker_carriers', [$this, 'ajax_get_locker_carriers']);
        
        // Add update locker shipping endpoint
        add_action('wp_ajax_update_locker_shipping', [$this, 'wp_ajax_update_locker_shipping']);
        add_action('wp_ajax_nopriv_update_locker_shipping', [$this, 'wp_ajax_update_locker_shipping']);
        // Filter shipping rate labels to show selected locker info
        //add_filter('woocommerce_cart_shipping_method_full_label', [$this, 'modify_locker_shipping_label'], 10, 2);
    }

    public function maybe_display_locker_in_block() {
        if (!is_checkout() && !has_block('woocommerce/checkout'))
            return;

        // Only load the simplified locker selector
        wp_enqueue_script('europarcel-locker-selector', plugins_url('assets/js/locker-selector.js', dirname(__DIR__) . '/europarcel.php'), array('jquery'), '2.1', true);

        // Localize script with AJAX parameters
        wp_localize_script('europarcel-locker-selector', 'europarcel_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('europarcel_locker_nonce'),
            'plugin_url' => plugins_url('', dirname(__DIR__) . '/europarcel.php')
        ]);
    }
/*
    public function save_locker_to_order($order) {
        if (!empty($_POST['eawb_locker_id'])) {
            // Save basic locker information
            $order->update_meta_data('_eawb_locker_id', sanitize_text_field($_POST['eawb_locker_id']));
            $order->update_meta_data('_eawb_locker_instance', sanitize_text_field($_POST['eawb_locker_instance']));

            // Save carrier ID (required for order processing)
            if (!empty($_POST['eawb_carrier_id'])) {
                $order->update_meta_data('_eawb_carrier_id', sanitize_text_field($_POST['eawb_carrier_id']));
            }

            // Save complete locker data for display purposes
            if (!empty($_POST['eawb_locker_data'])) {
                $locker_data = json_decode(stripslashes($_POST['eawb_locker_data']), true);
                if (is_array($locker_data)) {
                    $order->update_meta_data('_eawb_locker_name', sanitize_text_field($locker_data['name']));
                    $order->update_meta_data('_eawb_locker_address', sanitize_text_field($locker_data['address']));
                    $order->update_meta_data('_eawb_carrier_name', sanitize_text_field($locker_data['carrier_name']));

                    // Also save the complete data as JSON for future use
                    $order->update_meta_data('_eawb_locker_data', wp_json_encode($locker_data));
                }
            }
        }
    }
*/
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
            $customer = new \EawbShipping\EawbCustomer($instance_id);
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
            );

            WC()->session->set('locker_info', $locker_info);

            // Declanșează recalcularea coșului
            //WC()->cart->calculate_totals();

            wp_send_json_success('Locker actualizat cu succes');
        } else {
            wp_send_json_error('Date lipsă');
        }
        wp_die();
    }

    function update_rate_meta_locker($cart) {
        if (!isset($_POST['action']) ||  $_POST['action']!='update_locker_shipping') {
            return;
        }
        
        if (isset(WC()->session) && WC()->session->get('locker_info')) {
            $locker_info = WC()->session->get('locker_info');

            // Iterează prin metodele de transport disponibile
            $shipping_packages = WC()->shipping()->get_packages();
            $need_update=false;
            foreach ($shipping_packages as $package_key => $package) {
                foreach ($package['rates'] as $rate_id => $rate) {
                    // Verifică dacă este metoda de transport dorită
                    if ($rate->method_id === 'eawb_shipping_'.$locker_info['instance_id'] && str_contains($rate->id,'locker')) {
                        // Adaugă sau actualizează meta data pentru rată
                        $rate->add_meta_data('locker_id', $locker_info['locker_id']);
                        $rate->add_meta_data('carrier_id', $locker_info['carrier_id']);
                        // Adaugă orice alte informații relevante
                        $need_update=true;
                    }
                }
            }
            if ($need_update) {
                WC()->session->set('shipping_for_package_' . key($shipping_packages), $package['rates']);
            }
        }
    }

    /*
      public function modify_locker_shipping_label($label, $method) {
      // Only modify locker shipping methods
      if (!isset($method->get_meta_data()['is_locker']) || !$method->get_meta_data()['is_locker']) {
      return $label;
      }

      // Check if there's selected locker data in session or POST
      $locker_data = null;

      // Try to get from POST data (during checkout process)
      if (!empty($_POST['eawb_locker_data'])) {
      $locker_data = json_decode(stripslashes($_POST['eawb_locker_data']), true);
      }

      // If we have locker data, enhance the label
      if ($locker_data && is_array($locker_data)) {
      $carrier_logo = $this->get_carrier_logo($locker_data['carrier_id']);
      $logo_url = plugins_url('assets/images/carriers-logo/' . $carrier_logo, dirname(__DIR__) . '/europarcel.php');

      $enhanced_label = $label . '<div style="margin-top: 8px; padding: 10px; background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 13px;">';
      $enhanced_label .= '<div style="display: flex; align-items: center; gap: 10px;">';
      $enhanced_label .= '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($locker_data['carrier_name']) . '" style="width: 40px; height: 26px; object-fit: contain;" onerror="this.style.display=\'none\'">';
      $enhanced_label .= '<div>';
      $enhanced_label .= '<div style="font-weight: 600; color: #1e40af;">' . esc_html($locker_data['name']) . '</div>';
      $enhanced_label .= '<div style="color: #64748b; font-size: 12px;">' . esc_html($locker_data['address']) . '</div>';
      $enhanced_label .= '</div></div></div>';

      return $enhanced_label;
      }

      return $label;
      }

      private function get_carrier_logo($carrier_id) {
      $carrier_logos = [
      1 => 'cargus-ship-go-200.webp',  // Cargus = Ship & Go
      2 => 'dpd-200.webp',             // DPD = DPD
      3 => 'fanbox-200.webp',          // FAN Courier = Fanbox
      4 => 'gls-200.webp',             // GLS = GLS
      6 => 'sameday-200.webp'          // Sameday = EasyBox
      ];

      return $carrier_logos[$carrier_id] ?? 'default-carrier-logo.png';
      }

     */
}
