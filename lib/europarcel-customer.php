<?php

namespace EawbShipping;

defined('ABSPATH') || exit;
include_once EAWB_ROOT_PATH . '/lib/europarcel-constants.php';
require_once EAWB_ROOT_PATH . '/lib/europarcel-request-data.php';
require_once EAWB_ROOT_PATH . '/includes/class-europarcel-http-request.php';

class EawbCustomer {

    private int $instance_id;

    public function __construct($instance_id) {
        $this->instance_id = $instance_id;
    }

    public function getCustomerInfo() {
        try {
            $http_request = new \EawbShipping\EawbHttpRequest($this->instance_id);
            $response = $http_request->get('public/account/profile');
        } catch (\Exception $ex) {
            return null;
        }
        if (is_array($response) && isset($response['data']['name'])) {
            return $response['data'];
        }
        return null;
    }

    public function getCutomerBillingAdresses() {
        try {
            $data = [
                'page' => 1,
                'per_page' => 200
            ];
            $http_request = new \EawbShipping\EawbHttpRequest($this->instance_id);
            $response = $http_request->get('public/addresses/billing', $data);
            if (is_array($response) && isset($response['list'])) {
                $ret[0] = "";
                foreach ($response['list'] as $adress) {
                    if ($adress['address_type'] == 'individual') {
                        $ret[$adress['id']] = $adress['contact'] . ', ' . $adress['locality_name'] . ', ' . $adress['street_no'];
                    } else {
                        $ret[$adress['id']] = $adress['company'] . ', ' . $adress['locality_name'] . ', ' . $adress['street_no'];
                    }
                }
                return $ret;
            } else {
                return [];
            }
        } catch (\Exception $ex) {
            return [];
        }
    }

    public function getPickUpAdresses() {
        try {
            $data = [
                'page' => 1,
                'per_page' => 200
            ];
            $http_request = new \EawbShipping\EawbHttpRequest($this->instance_id);
            $response = $http_request->get('public/addresses/shipping', $data);
            if (is_array($response) && isset($response['list'])) {
                $ret[0] = "";
                foreach ($response['list'] as $adress) {
                    if ($adress['address_type'] == 'individual') {
                        $ret[$adress['id']] = $adress['contact'] . ', ' . $adress['locality_name'] . ', ' . $adress['street_no'];
                    } else {
                        $ret[$adress['id']] = $adress['company'] . ', ' . $adress['locality_name'] . ', ' . $adress['street_no'];
                    }
                }
                return $ret;
            } else {
                return [];
            }
        } catch (\Exception $ex) {
            return [];
        }
    }

    public function getPrices($package, $allow_locker) {
        $data = new \EawbShipping\EawbRequestData($this->instance_id, $allow_locker);
        $settings = get_option('woocommerce_eawb_shipping_' . $this->instance_id . '_settings');
        if ($settings['enabled'] != 'yes' || !$settings['eawb_customer']) {
            return false;
        }
        if (!$package['destination']['city']) {
            return false;
        }
        $delivery_address = [
            'email' => $settings['eawb_customer']['email'],
            'phone' => $settings['eawb_customer']['phone'],
            'contact' => $settings['eawb_customer']['name'],
            'company' => isset($settings['eawb_customer']['company']) ? $settings['eawb_customer']['company'] : $settings['eawb_customer']['name'],
            'country_code' => $package['destination']['country'],
            'county_name' => WC()->countries->get_states($package['destination']['country'])[$package['destination']['state']],
            'locality_name' => $package['destination']['city'],
            'street_name' => $package['destination']['address'] ? $package['destination']['address'] : 'principala',
            'street_number' => '1',
            'street_details' => ''
        ];
        $data->setDeliveryAddress($delivery_address);
        try {
            $http_request = new \EawbShipping\EawbHttpRequest($this->instance_id);
            $response = $http_request->post('public/orders/prices', $data->getData());
        } catch (\Exception $ex) {
            return false;
        }
        if (is_array($response) && isset($response['data'])) {
            $services_config = \EawbShipping\EawbConstants::getSettingsServices($settings['available_services']);
            $available_services_hth = []; // home to home
            $available_services_htl = []; // home to locker
            foreach ($services_config as $serv_conf) {
                foreach ($response['data'] as $service) {
                    if ($serv_conf['carrier_id'] == $service['carrier_id'] && $serv_conf['service_id'] == $service['service_id'] && $service['service_id'] == 1) {
                        $available_services_hth[] = $service;
                    }
                    if ($serv_conf['carrier_id'] == $service['carrier_id'] && $serv_conf['service_id'] == $service['service_id'] && $service['service_id'] == 2) {
                        $available_services_htl[] = $service;
                    }
                }
            }
        }
        //if ($settings['courier_choice_method'] == 'low_price') {
        usort($available_services_hth, array($this, "sort_by_price"));
        usort($available_services_htl, array($this, "sort_by_price"));
        //}
        return [$available_services_hth, $available_services_htl];
    }

    public function postOrder($order, $carrier_id, $service_id) {

        get_option('woocommerce_eawb_shipping_' . $this->instance_id . '_settings');
        if ($settings['enabled'] != 'yes' || !$settings['eawb_customer']) {
            return false;
        }
        $address_to = $order->get_address('shipping');

        if (!is_array($address_to) || $address_to['city']) {
            return false;
        }
        $data = new \EawbShipping\EawbRequestData();
        $data->setCarrierId($carrier_id);
        $data->setServiceId($service_id);
        $delivery_address = [
            'email' => 'de_facut@eawb.ro',
            'phone' => $address_to['phone'],
            'contact' => $address_to['last_name'] . ' ' . $address_to['first_name'],
            'company' => $address_to['company'],
            'country_code' => $address_to['country'],
            'county_name' => WC()->countries->get_states($address_to['country'])[$address_to['state']],
            'locality_name' => $address_to['city'],
            'street_name' => $address_to['address_1'],
            'street_number' => '',
            'street_details' => $address_to['address_2']
        ];
        $data->setDeliveryAddress($delivery_address);
        try {
            $http_request = new \EawbShipping\EawbHttpRequest($this->instance_id);
            $response = $http_request->post('public/orders', $data->getData());
            return $response;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function get_lockers() {
        $cariers_ids=$this->get_locker_carriers();
        $data = [
            'country_code' => $_POST['country'],
            'carrier_id' => implode(",", $cariers_ids),
            'locality_name' => $_POST['city'],
            'county_name' => $_POST['state']
        ];

        try {
            $http_request = new \EawbShipping\EawbHttpRequest($this->instance_id);
            $lockers = $http_request->get('public/locations/fixedlocations', $data);
            return $lockers;
        } catch (\Exception $ex) {
            return false;
        }
    }

    private function sort_by_price($fp, $lp) {
        if ($fp['price']['total'] == $lp['price']['total']) {
            return 0;
        }
        return ($fp['price']['total'] < $lp['price']['total']) ? -1 : 1;
    }
    public function get_locker_carriers() {
        $settings = get_option('woocommerce_eawb_shipping_' . $this->instance_id . '_settings');
            
            if (!$settings || !isset($settings['available_services'])) {
                return [];
            }
            
            $all_services = \EawbShipping\EawbConstants::getSettingsServices($settings['available_services']);
            $locker_services = array_filter($all_services, function($service) {
                return $service['service_id'] == 2;
            });
            
            if (empty($locker_services)) {
                return [];
            }
            return array_column($locker_services,'carrier_id');
    }
}
