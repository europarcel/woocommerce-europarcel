<?php

namespace EawbShipping;

defined('ABSPATH') || exit;
include_once EAWB_ROOT_PATH . '/lib/eawb-constants.php';
require_once EAWB_ROOT_PATH . '/lib/eawb-request-data.php';
require_once EAWB_ROOT_PATH . '/includes/eawb-http-request.php';

class EawbCustomer {

    public function getCustomerInfo() {
        try {
            $response = \EawbShipping\EawbHttpRequest::get('public/account/profile');
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
            $response = \EawbShipping\EawbHttpRequest::get('public/addresses/billing', $data);
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
            $response = \EawbShipping\EawbHttpRequest::get('public/addresses/shipping', $data);
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

    public function getPrices($package) {
        $data = new \EawbShipping\EawbRequestData();
        $settings = get_option('woocommerce_eawb_shipping_settings');
        if ($settings['enabled']!='yes' || !$settings['eawb_customer']) {
            return false;
        }
        $delivery_address = [
            'email' => $settings['eawb_customer']['email'],
            'phone' => $settings['eawb_customer']['phone'],
            'contact' => $settings['eawb_customer']['name'],
            'company' => isset($settings['eawb_customer']['company'])?$settings['eawb_customer']['company']:$settings['eawb_customer']['name'],
            'country_code' => $package['destination']['country'],
            'county_name' => WC()->countries->get_states($package['destination']['country'])[$package['destination']['state']],
            'locality_name' => $package['destination']['city'],
            'street_name' => $package['destination']['address'],
            'street_number' => '1',
            'street_details' => ''
        ];
        $data->setDeliveryAddress($delivery_address);
        try {
            $response = \EawbShipping\EawbHttpRequest::post('public/orders/prices', $data->getData());
        } catch (\Exception $ex) {
            return false;
        }
        if (is_array($response) && isset($response['data'])) {
            $services_config = \EawbShipping\EawbConstants::getSettingsServices($settings['available_services']);
            $available_services_hth = null; // home to home
            $available_services_htl = null; // home to locker
            foreach ($services_config as $serv_conf) {
                foreach ($response['data'] as $service) {
                    if ($serv_conf['carrier_id']==$service['carrier_id'] && $serv_conf['service_id']==$service['service_id'] && $service['service_id']==1) {
                        $available_services_hth[]=$service;
                    }
                    if ($serv_conf['carrier_id']==$service['carrier_id'] && $serv_conf['service_id']==$service['service_id'] && $service['service_id']==2) {
                        $available_services_htl[]=$service;
                    }
                }
            }
        }
        if ($settings['courier_choice_method']=='low_price') {
            usort($available_services_hth,array($this,"sort_by_price"));
            usort($available_services_htl,array($this,"sort_by_price"));
        } 
        return [$available_services_hth,$available_services_htl];

    }
    private function sort_by_price($fp,$lp) {
        if ($fp['price']['total'] == $lp['price']['total']) {
            return 0;
        }
        return ($fp['price']['total'] < $lp['price']['total']) ? -1 : 1;
    }
}
