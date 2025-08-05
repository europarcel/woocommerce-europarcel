<?php

namespace EawbShipping;

defined('ABSPATH') || exit;
include_once EAWB_ROOT_PATH . '/lib/eawb-customer.php';

class EawbOrderHandler {

    public function __construct() {
        //add_action('woocommerce_checkout_order_processed', [$this, 'handle_new_order'], 10, 3);
        //add_action('woocommerce_order_status_changed', [$this, 'handle_status_change'], 10, 4);
    }

    public function handle_new_order($order_id, $posted_data, $order) {
        $shipping_method = $order->get_items('shipping');
        if ($order->has_shipping_method('eawb_shipping')) {
            $this->create_shipment($order);
        }
    }

    public function handle_status_change($order_id, $old_status, $new_status, $order) {
        $shipping_methods = $order->get_shipping_methods();
        $shipping_method = reset($shipping_methods);
        if (!$shipping_method || strpos($shipping_method->get_method_id(), 'eawb_shipping') === false) {
            return;
        }
        $carrier_id = $shipping_method->get_meta('carrier_id');
        $service_id = $shipping_method->get_meta('service_id');
        $this->create_shipment($order,$carrier_id,$service_id);
    }

    private function create_shipment($order,$carrier_id,$service_id) {
        try {
            $customer = new \EawbShipping\EawbCustomer();
            $response = $customer->postOrder($order,$carrier_id,$service_id); //$response = $api->create_shipment($order);
            update_post_meta($order->get_id(), '_eawb_awb_number', $response['awb_number']);
            update_post_meta($order->get_id(), '_eawb_shipment_data', $response);
        } catch (Exception $e) {
            $order->add_order_note(
                    __('Eroare la generare AWB: ', 'eawb') . $e->getMessage()
            );
            //$order->update_status('on-hold');
        }
    }
}
